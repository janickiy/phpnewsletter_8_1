<?php

namespace Tests\Feature;

use App\DTO\Create\SubscriberCreateData;
use App\Models\Category;
use App\Models\Project;
use App\Models\Subscribers;
use App\Models\User;
use App\Repositories\SubscriberRepository;
use App\Services\SubscriberService;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DefaultProjectSubscribersTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_role_can_add_subscribers_to_the_default_project_without_selecting_a_project(): void
    {
        $category = Category::query()->create(['project_id' => Project::DEFAULT_ID, 'name' => 'Default category']);

        foreach ([User::ROLE_ADMIN, User::ROLE_PROJECT_ADMIN, User::ROLE_MODERATOR] as $role) {
            $this->actingAs($this->user($role));
            foreach ([[], ['project_ids' => []], ['project_ids' => [Project::DEFAULT_ID]]] as $index => $selection) {
                $email = $role.'-'.$index.'@example.test';
                $this->post(route('admin.subscribers.store'), [
                    ...$selection, 'email' => $email, 'categoryId' => [$category->id],
                ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');

                $subscriber = Subscribers::query()->where('email', $email)->sole();
                $this->assertSame([Project::DEFAULT_ID], $subscriber->projects()->pluck('projects.id')->all());
                $this->assertSame([$category->id], $subscriber->subscriptions()->pluck('category_id')->all());
            }
        }
    }

    public function test_repository_defaults_preserve_existing_identity_and_other_project_memberships(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $project = Project::query()->create(['name' => 'Private project', 'owner_id' => $admin->id, 'status' => 1]);
        $subscriber = $this->subscriberFixture([
            'email' => 'shared@example.test', 'name' => 'Saved name', 'token' => 'saved-token',
            'active' => 0, 'timeSent' => '2026-01-02 03:04:05',
        ], [$project->id]);
        $this->actingAs($this->user(User::ROLE_MODERATOR));

        $added = app(SubscriberRepository::class)->add(new SubscriberCreateData(
            email: $subscriber->email, name: 'Replacement name', active: 1,
            token: 'replacement-token', timeSent: now(),
        ));

        $this->assertSame($subscriber->id, $added->id);
        $this->assertEqualsCanonicalizing([Project::DEFAULT_ID, $project->id], $added->projects()->pluck('projects.id')->all());
        $this->assertSame('Saved name', $added->name);
        $this->assertSame('saved-token', $added->token);
        $this->assertSame(0, (int) $added->active);
        $this->assertSame('2026-01-02 03:04:05', (string) $added->timeSent);
    }

    public function test_create_and_import_forms_default_to_zero_but_edit_can_still_clear_memberships(): void
    {
        $category = Category::query()->create(['project_id' => Project::DEFAULT_ID, 'name' => 'Default category']);
        Category::query()->create(['project_id' => null, 'name' => 'Unassigned category']);
        $this->actingAs($this->user(User::ROLE_MODERATOR));

        foreach (['admin.subscribers.create', 'admin.subscribers.import'] as $route) {
            $response = $this->get(route($route))->assertOk()->assertSee($category->name);
            $document = new DOMDocument;
            @$document->loadHTML($response->getContent());
            $page = new DOMXPath($document);
            $this->assertSame(1, $page->query('//select[@id="project_ids"]/option[@value="0" and @selected]')->length);
            $this->assertSame(0, $page->query('//select[@id="project_ids" and @required]')->length);
        }

        $subscriber = $this->subscriberFixture([
            'email' => 'clear@example.test', 'name' => 'Clear projects', 'token' => 'clear-token', 'active' => 1,
        ], [Project::DEFAULT_ID]);
        $this->put(route('admin.subscribers.update'), [
            'id' => $subscriber->id, 'email' => $subscriber->email, 'project_ids' => [],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame(0, $subscriber->projects()->count());
        $this->assertModelExists($subscriber);
    }

    public function test_text_and_spreadsheet_imports_use_the_default_project_when_selection_is_empty(): void
    {
        $this->actingAs($this->user(User::ROLE_MODERATOR));
        $category = Category::query()->create(['project_id' => Project::DEFAULT_ID, 'name' => 'Import category']);
        foreach (['txt', 'csv'] as $extension) {
            $email = 'import-'.$extension.'@example.test';
            $contents = $extension === 'txt' ? $email."\n" : "email,name\n".$email.",Imported name\n";
            $request = Request::create('/import', 'POST', [
                'categoryId' => [$category->id], 'project_ids' => [],
            ], [], ['import' => UploadedFile::fake()->createWithContent('subscribers.'.$extension, $contents)]);

            $service = app(SubscriberService::class);
            $this->assertSame(1, $extension === 'txt' ? $service->importFromText($request) : $service->importFromExcel($request));
            $subscriber = Subscribers::query()->where('email', $email)->sole();
            $this->assertSame([Project::DEFAULT_ID], $subscriber->projects()->pluck('projects.id')->all());
            $this->assertSame([$category->id], $subscriber->subscriptions()->pluck('category_id')->all());
        }
    }

    public function test_default_assignment_does_not_allow_categories_from_a_private_project(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $project = Project::query()->create(['name' => 'Private project', 'owner_id' => $admin->id, 'status' => 1]);
        $category = Category::query()->create(['project_id' => $project->id, 'name' => 'Private category']);
        $this->actingAs($this->user(User::ROLE_MODERATOR))->post(route('admin.subscribers.store'), [
            'email' => 'forbidden@example.test', 'categoryId' => [$category->id],
        ])->assertRedirect()->assertSessionHasErrors('categoryId.0');
        $this->assertDatabaseMissing('subscribers', ['email' => 'forbidden@example.test']);
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => $role, 'login' => $role.'-'.bin2hex(random_bytes(4)),
            'role' => $role, 'password' => 'test-password',
        ]);
    }
}
