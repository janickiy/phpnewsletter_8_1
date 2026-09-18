<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use App\Models\User;
use App\Services\SendMailService;
use App\Services\SubscriberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubscriberProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    private Project $own;
    private Project $other;
    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();
        $owner = User::query()->create(['name' => 'Owner', 'login' => 'owner', 'role' => User::ROLE_ADMIN, 'password' => 'password']);
        $this->moderator = User::query()->create(['name' => 'Moderator', 'login' => 'moderator', 'role' => User::ROLE_MODERATOR, 'password' => 'password']);
        $this->own = Project::query()->create(['name' => 'Assigned', 'owner_id' => $owner->id, 'status' => 1]);
        $this->other = Project::query()->create(['name' => 'Other', 'owner_id' => $owner->id, 'status' => 1]);
        $this->own->members()->attach($this->moderator, ['role' => User::ROLE_MODERATOR]);
        $this->actingAs($this->moderator);
    }

    public function test_moderator_can_manage_assigned_subscribers_but_cannot_move_or_access_foreign_records(): void
    {
        $own = $this->subscriber($this->own, 'own@example.test');
        $other = $this->subscriber($this->other, 'other@example.test');
        $this->get(route('admin.subscribers.edit', $own->id))->assertOk();
        $this->get(route('admin.subscribers.edit', $other->id))->assertNotFound();
        $this->delete(route('admin.subscribers.destroy', $other->id))->assertNotFound();
        $this->put(route('admin.subscribers.update'), ['id' => $other->id, 'project_ids' => [$this->own->id], 'email' => 'changed@example.test'])->assertForbidden();
        $this->put(route('admin.subscribers.update'), ['id' => $own->id, 'project_ids' => [$this->other->id], 'email' => $own->email])->assertSessionHasErrors('project_ids.0');
        $this->put(route('admin.subscribers.update'), ['id' => $own->id, 'project_ids' => [$this->own->id], 'email' => $own->email, 'name' => 'Updated'])->assertSessionHasNoErrors();
        $this->assertSame('Updated', $own->fresh()->name);
        $this->assertSame([$this->other->id], $other->projects()->pluck('projects.id')->all());
    }

    public function test_create_rejects_foreign_assignments_and_uses_the_default_when_project_is_missing(): void
    {
        $category = Category::query()->create(['project_id' => $this->other->id, 'name' => 'Foreign']);
        $this->post(route('admin.subscribers.store'), ['project_ids' => [$this->other->id], 'email' => 'one@example.test'])->assertSessionHasErrors('project_ids.0');
        $this->post(route('admin.subscribers.store'), ['project_ids' => [$this->own->id], 'email' => 'one@example.test', 'categoryId' => [$category->id]])->assertSessionHasErrors('categoryId.0');
        $this->post(route('admin.subscribers.store'), ['email' => 'one@example.test'])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $subscriber = Subscribers::query()->where('email', 'one@example.test')->sole();
        $this->assertSame([Project::DEFAULT_ID], $subscriber->projects()->pluck('projects.id')->all());

        $this->post(route('admin.subscribers.store'), ['project_ids' => [$this->own->id], 'email' => 'one@example.test'])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertDatabaseCount('subscribers', 1);
        $this->assertEqualsCanonicalizing([Project::DEFAULT_ID, $this->own->id], $subscriber->projects()->pluck('projects.id')->all());
    }

    public function test_bulk_operations_are_atomic_and_delete_all_only_removes_accessible_projects(): void
    {
        $own = $this->subscriber($this->own, 'own@example.test');
        $other = $this->subscriber($this->other, 'other@example.test');
        foreach ([0, 2] as $action) {
            $this->post(route('admin.subscribers.status'), ['action' => $action, 'activate' => [$own->id, $other->id]])->assertForbidden();
            $this->assertSame(1, (int) $own->fresh()->active);
            $this->assertNotNull($other->fresh());
        }
        $this->get(route('admin.subscribers.remove_all'))->assertStatus(405);
        $this->post(route('admin.subscribers.remove_all'))->assertRedirect();
        $this->assertDatabaseHas('subscribers', ['id' => $own->id]);
        $this->assertDatabaseMissing('project_subscriber', ['subscriber_id' => $own->id]);
        $this->assertDatabaseHas('subscribers', ['id' => $other->id]);
    }

    public function test_import_same_email_does_not_change_foreign_project_or_its_categories(): void
    {
        $foreign = $this->subscriber($this->other, 'same@example.test');
        $foreignCategory = Category::query()->create(['project_id' => $this->other->id, 'name' => 'Foreign category']);
        Subscriptions::query()->create(['subscriber_id' => $foreign->id, 'category_id' => $foreignCategory->id]);
        $category = Category::query()->create(['project_id' => $this->own->id, 'name' => 'Own category']);
        $file = UploadedFile::fake()->createWithContent('subscribers.txt', "Same same@example.test\n");
        $request = Request::create('/import', 'POST', ['project_ids' => [$this->own->id], 'categoryId' => [$category->id]], [], ['import' => $file]);
        $this->assertSame(1, app(SubscriberService::class)->importFromText($request));
        $this->assertDatabaseCount('subscribers', 1);
        $this->assertDatabaseHas('subscriptions', ['subscriber_id' => $foreign->id, 'category_id' => $foreignCategory->id]);
        $this->assertDatabaseHas('project_subscriber', ['project_id' => $this->own->id, 'subscriber_id' => $foreign->id]);
        $this->assertDatabaseHas('project_subscriber', ['project_id' => $this->other->id, 'subscriber_id' => $foreign->id]);
        $this->assertDatabaseHas('subscriptions', ['subscriber_id' => $foreign->id, 'category_id' => $category->id]);
        $this->post(route('admin.subscribers.import_subscribers'), ['project_ids' => [$this->other->id], 'import' => $file])->assertSessionHasErrors('project_ids.0');
        $this->post(route('admin.subscribers.import_subscribers'), ['project_ids' => [$this->own->id], 'categoryId' => [$foreignCategory->id], 'import' => $file])->assertSessionHasErrors('categoryId.0');
    }

    #[DataProvider('exportFormats')]
    public function test_every_export_format_is_project_scoped(string $type, string $compression): void
    {
        $this->subscriber($this->own, 'allowed@example.test');
        $this->subscriber($this->other, 'secret@example.test');
        $response = $this->post(route('admin.subscribers.export_subscribers'), ['project_ids' => [$this->own->id], 'export_type' => $type, 'compress' => $compression])->assertOk();
        $contents = $response->streamedContent();
        if ($compression === 'zip') {
            $contents = $this->unzip($contents);
        }
        if ($type === 'excel') {
            $contents = $this->unzip($contents, 'xl/worksheets/sheet1.xml');
        }
        $this->assertStringContainsString('allowed@example.test', $contents);
        $this->assertStringNotContainsString('secret@example.test', $contents);
        $this->post(route('admin.subscribers.export_subscribers'), ['project_ids' => [$this->other->id], 'export_type' => $type, 'compress' => $compression])->assertSessionHasErrors('project_ids.0');
    }

    public static function exportFormats(): array
    {
        return [['text', 'none'], ['text', 'zip'], ['excel', 'none'], ['excel', 'zip']];
    }

    public function test_public_subscription_and_category_list_require_an_active_project(): void
    {
        auth()->logout();
        $this->mock(SendMailService::class)->shouldReceive('sendFrontendSubscriberEmails')->once();
        $ownCategory = Category::query()->create(['project_id' => $this->own->id, 'name' => 'Public category']);
        $foreignCategory = Category::query()->create(['project_id' => $this->other->id, 'name' => 'Foreign category']);
        $this->getJson(route('frontend.categories', ['project_id' => $this->own->id]))->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $ownCategory->id);
        $this->postJson(route('frontend.addsub'), ['email' => 'public@example.test'])->assertUnprocessable()->assertJsonValidationErrors('project_id', 'errors');
        $this->postJson(route('frontend.addsub'), ['project_id' => $this->own->id, 'email' => 'public@example.test', 'categoryId' => [$foreignCategory->id]])->assertUnprocessable();
        $this->postJson(route('frontend.addsub'), ['project_id' => $this->own->id, 'email' => 'public@example.test', 'categoryId' => [$ownCategory->id]])->assertOk();
        $this->own->update(['status' => 0]);
        $this->getJson(route('frontend.categories', ['project_id' => $this->own->id]))->assertNotFound();
        $this->postJson(route('frontend.addsub'), ['project_id' => $this->own->id, 'email' => 'blocked@example.test'])->assertUnprocessable();
    }

    private function subscriber(Project $project, string $email): Subscribers
    {
        return $this->subscriberFixture(['email' => $email, 'name' => 'Subscriber', 'active' => 1, 'token' => bin2hex(random_bytes(16))], [$project->id]);
    }

    private function unzip(string $contents, ?string $filename = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'project_export_');
        file_put_contents($path, $contents);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path));
        $result = $filename === null ? $zip->getFromIndex(0) : $zip->getFromName($filename);
        $zip->close();
        unlink($path);
        $this->assertIsString($result);
        return $result;
    }
}
