<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\ReadySent;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use App\Models\Templates;
use App\Models\User;
use App\Repositories\SubscriberRepository;
use App\Services\SubscriberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SubscriberProjectMembershipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $moderator;
    private Project $own;
    private Project $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Administrator', 'login' => 'membership-admin',
            'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $this->moderator = User::query()->create([
            'name' => 'Moderator', 'login' => 'membership-moderator',
            'role' => User::ROLE_MODERATOR, 'password' => 'password',
        ]);
        $this->own = Project::query()->create([
            'name' => 'Accessible project', 'owner_id' => $this->admin->id, 'status' => 1,
        ]);
        $this->other = Project::query()->create([
            'name' => 'Private project name', 'owner_id' => $this->admin->id, 'status' => 1,
        ]);
        $this->own->members()->attach($this->moderator, ['role' => User::ROLE_MODERATOR]);
        $this->actingAs($this->admin);
    }

    public function test_an_administrator_can_create_subscribers_without_projects_and_with_two_projects(): void
    {
        $this->post(route('admin.subscribers.store'), [
            'email' => 'orphan@example.test', 'project_ids' => [],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $orphan = Subscribers::query()->where('email', 'orphan@example.test')->sole();
        $this->assertSame(0, $orphan->projects()->count());

        $ownCategory = $this->category($this->own, 'Own category');
        $otherCategory = $this->category($this->other, 'Other category');
        $this->post(route('admin.subscribers.store'), [
            'email' => 'shared@example.test',
            'project_ids' => [$this->own->id, $this->other->id],
            'categoryId' => [$ownCategory->id, $otherCategory->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $shared = Subscribers::query()->where('email', 'shared@example.test')->sole();
        $this->assertEqualsCanonicalizing([$this->own->id, $this->other->id], $shared->projects()->pluck('projects.id')->all());
        $this->assertEqualsCanonicalizing([$ownCategory->id, $otherCategory->id], $shared->subscriptions()->pluck('category_id')->all());

        $this->put(route('admin.subscribers.update'), [
            'id' => $shared->id, 'email' => $shared->email, 'project_ids' => [],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertModelExists($shared);
        $this->assertSame(0, $shared->projects()->count());
        $this->assertSame(0, $shared->subscriptions()->count());
    }

    public function test_create_and_edit_normalize_email_and_enforce_one_global_identity(): void
    {
        $this->post(route('admin.subscribers.store'), [
            'email' => '  SHARED@EXAMPLE.TEST  ', 'project_ids' => [$this->own->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $shared = Subscribers::query()->sole();
        $this->assertSame('shared@example.test', $shared->email);

        $this->post(route('admin.subscribers.store'), [
            'email' => ' Shared@example.test ', 'project_ids' => [$this->other->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertDatabaseCount('subscribers', 1);
        $this->assertEqualsCanonicalizing([$this->own->id, $this->other->id], $shared->projects()->pluck('projects.id')->all());

        $this->put(route('admin.subscribers.update'), [
            'id' => $shared->id, 'email' => '  RENAMED@EXAMPLE.TEST  ',
            'project_ids' => [$this->own->id, $this->other->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('renamed@example.test', $shared->fresh()->email);

        $other = $this->subscriber('other@example.test', [$this->other->id]);
        $this->put(route('admin.subscribers.update'), [
            'id' => $other->id, 'email' => '  RENAMED@EXAMPLE.TEST  ', 'project_ids' => [$this->other->id],
        ])->assertRedirect()->assertSessionHasErrors('email');
        $this->assertSame('other@example.test', $other->fresh()->email);
        $this->assertDatabaseCount('subscribers', 2);
        $this->assertSame(1, Subscribers::query()->where('email', 'renamed@example.test')->count());
    }

    public function test_scoped_lists_and_edit_forms_hide_foreign_projects_categories_and_orphans(): void
    {
        $shared = $this->subscriber('shared@example.test', [$this->own->id, $this->other->id]);
        $foreign = $this->subscriber('foreign@example.test', [$this->other->id]);
        $orphan = $this->subscriber('orphan@example.test');
        $ownCategory = $this->category($this->own, 'Visible category');
        $otherCategory = $this->category($this->other, 'Private category name');
        $this->subscribe($shared, $ownCategory, $otherCategory);
        $this->actingAs($this->moderator);

        $rows = $this->getJson(route('admin.datatable.subscribers', ['draw' => 1, 'start' => 0, 'length' => 20]))
            ->assertOk()->assertDontSee($this->other->name)->assertDontSee($otherCategory->name)->json('data');
        $this->assertEquals([$shared->id], array_column($rows, 'id'));
        $this->get(route('admin.subscribers.edit', $shared->id))->assertOk()
            ->assertSee($this->own->name)->assertSee($ownCategory->name)
            ->assertDontSee($this->other->name)->assertDontSee($otherCategory->name);
        $this->get(route('admin.subscribers.edit', $foreign->id))->assertNotFound();
        $this->get(route('admin.subscribers.edit', $orphan->id))->assertNotFound();
        $this->delete(route('admin.subscribers.destroy', $orphan->id))->assertNotFound();

        $this->actingAs($this->admin)->get(route('admin.subscribers.edit', $orphan->id))->assertOk();
    }

    public function test_crafted_datatable_relation_search_cannot_probe_hidden_project_or_category_memberships(): void
    {
        $shared = $this->subscriber('shared@example.test', [$this->own->id, $this->other->id]);
        $ownOnly = $this->subscriber('own-only@example.test', [$this->own->id]);
        $hiddenCategory = $this->category($this->other, 'Hidden category needle');
        $this->subscribe($shared, $hiddenCategory);
        $this->actingAs($this->moderator);

        foreach (['projects.name' => $this->other->name, 'subscriptions.category.name' => $hiddenCategory->name] as $column => $hiddenName) {
            foreach ([$hiddenName, 'Nonexistent secret membership'] as $needle) {
                foreach (['column', 'global'] as $searchMode) {
                    $response = $this->getJson(route('admin.datatable.subscribers', [
                        'draw' => 1, 'start' => 0, 'length' => 20,
                        'columns' => [[
                            'data' => 'name', 'name' => $column, 'searchable' => 'true', 'orderable' => 'true',
                            'search' => ['value' => $searchMode === 'column' ? $needle : '', 'regex' => 'false'],
                        ]],
                        'search' => ['value' => $searchMode === 'global' ? $needle : '', 'regex' => 'false'],
                    ]))->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 2)
                        ->assertDontSee($this->other->name)->assertDontSee($hiddenCategory->name);
                    $this->assertEqualsCanonicalizing([$shared->id, $ownOnly->id], array_column($response->json('data'), 'id'));
                }
            }
        }
    }

    public function test_editing_accessible_memberships_and_categories_preserves_foreign_links(): void
    {
        $shared = $this->subscriber('shared@example.test', [$this->own->id, $this->other->id]);
        $oldCategory = $this->category($this->own, 'Old own category');
        $newCategory = $this->category($this->own, 'New own category');
        $foreignCategory = $this->category($this->other, 'Foreign category');
        $this->subscribe($shared, $oldCategory, $foreignCategory);

        $this->actingAs($this->moderator)->put(route('admin.subscribers.update'), [
            'id' => $shared->id, 'email' => $shared->email, 'name' => $shared->name,
            'project_ids' => [$this->own->id], 'categoryId' => [$newCategory->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertEqualsCanonicalizing([$this->own->id, $this->other->id], $shared->projects()->pluck('projects.id')->all());
        $this->assertEqualsCanonicalizing([$newCategory->id, $foreignCategory->id], $shared->subscriptions()->pluck('category_id')->all());
    }

    public function test_non_admin_deletion_only_detaches_accessible_projects_and_admin_deletion_removes_the_identity(): void
    {
        $shared = $this->subscriber('shared@example.test', [$this->own->id, $this->other->id]);
        $ownOnly = $this->subscriber('own-only@example.test', [$this->own->id]);
        $ownCategory = $this->category($this->own, 'Own category');
        $otherCategory = $this->category($this->other, 'Other category');
        $this->subscribe($shared, $ownCategory, $otherCategory);
        $this->actingAs($this->moderator);

        $this->delete(route('admin.subscribers.destroy', $shared->id))->assertSuccessful();
        $this->assertModelExists($shared);
        $this->assertSame([$this->other->id], $shared->projects()->pluck('projects.id')->all());
        $this->assertSame([$otherCategory->id], $shared->subscriptions()->pluck('category_id')->all());
        $this->delete(route('admin.subscribers.destroy', $ownOnly->id))->assertSuccessful();
        $this->assertModelExists($ownOnly);
        $this->assertSame(0, $ownOnly->projects()->count());
        $this->get(route('admin.subscribers.edit', $ownOnly->id))->assertNotFound();

        $this->actingAs($this->admin)->delete(route('admin.subscribers.destroy', $shared->id))->assertSuccessful();
        $this->assertModelMissing($shared);
        $this->assertDatabaseMissing('project_subscriber', ['subscriber_id' => $shared->id]);
        $this->assertDatabaseMissing('subscriptions', ['subscriber_id' => $shared->id]);
        $this->assertModelExists($ownOnly);
    }

    public function test_status_confirmation_and_unsubscribe_are_global_for_shared_subscribers(): void
    {
        $shared = $this->subscriber('shared@example.test', [$this->own->id, $this->other->id]);
        $ownCategory = $this->category($this->own, 'Own category');
        $otherCategory = $this->category($this->other, 'Other category');
        $this->subscribe($shared, $ownCategory, $otherCategory);
        $repository = app(SubscriberRepository::class);

        $this->actingAs($this->moderator)->post(route('admin.subscribers.status'), [
            'action' => 0, 'activate' => [$shared->id],
        ])->assertRedirect()->assertSessionMissing('error');
        $this->assertSame(0, (int) $shared->fresh()->active);
        foreach ([[$this->own, $ownCategory], [$this->other, $otherCategory]] as [$project, $category]) {
            $this->assertSame(0, $repository->countSubscriptions([$category->id], null, null, $project->id));
        }

        auth()->logout();
        $this->get(route('frontend.subscribe', ['subscriber' => $shared->id, 'token' => $shared->token]))->assertOk();
        foreach ([[$this->own, $ownCategory], [$this->other, $otherCategory]] as [$project, $category]) {
            $this->assertSame(1, $repository->countSubscriptions([$category->id], null, null, $project->id));
        }
        $this->get(route('frontend.unsubscribe', ['subscriber' => $shared->id, 'token' => $shared->token]))->assertOk();
        $this->assertSame(0, (int) $shared->fresh()->active);
        $this->assertSame(2, $shared->projects()->count());
        $this->assertSame(2, $shared->subscriptions()->count());
        $this->assertSame($shared->token, $shared->fresh()->token);
    }

    public function test_import_reuses_the_identity_and_preserves_global_state_and_other_project_categories(): void
    {
        $shared = $this->subscriber('shared@example.test', [$this->other->id]);
        $shared->update(['active' => 0, 'timeSent' => '2026-01-02 03:04:05']);
        $ownCategory = $this->category($this->own, 'Own category');
        $otherCategory = $this->category($this->other, 'Other category');
        $this->subscribe($shared, $otherCategory);
        $this->actingAs($this->moderator);
        $request = Request::create('/import', 'POST', [
            'project_ids' => [$this->own->id], 'categoryId' => [$ownCategory->id],
        ], [], ['import' => UploadedFile::fake()->createWithContent('subscribers.txt', "Imported name SHARED@example.test\n")]);

        $this->assertSame(1, app(SubscriberService::class)->importFromText($request));
        $this->assertDatabaseCount('subscribers', 1);
        $this->assertEqualsCanonicalizing([$this->own->id, $this->other->id], $shared->projects()->pluck('projects.id')->all());
        $this->assertEqualsCanonicalizing([$ownCategory->id, $otherCategory->id], $shared->subscriptions()->pluck('category_id')->all());
        $this->assertSame(0, (int) $shared->fresh()->active);
        $this->assertSame($shared->token, $shared->fresh()->token);
        $this->assertSame('2026-01-02 03:04:05', (string) $shared->fresh()->timeSent);
        $this->assertSame($shared->name, $shared->fresh()->name);
    }

    public function test_exporting_two_projects_lists_a_shared_identity_only_once(): void
    {
        $shared = $this->subscriber('shared@example.test', [$this->own->id, $this->other->id]);
        $this->subscriber('own-only@example.test', [$this->own->id]);
        $this->subscriber('other-only@example.test', [$this->other->id]);
        $this->subscriber('orphan@example.test');

        $contents = $this->post(route('admin.subscribers.export_subscribers'), [
            'project_ids' => [$this->own->id, $this->other->id],
            'export_type' => 'text', 'compress' => 'none',
        ])->assertOk()->streamedContent();

        $this->assertSame(1, substr_count($contents, $shared->email));
        $this->assertStringContainsString('own-only@example.test', $contents);
        $this->assertStringContainsString('other-only@example.test', $contents);
        $this->assertStringNotContainsString('orphan@example.test', $contents);
    }

    public function test_deleting_a_project_retains_shared_subscribers_orphans_and_other_project_delivery_history(): void
    {
        $shared = $this->subscriber('shared@example.test', [$this->own->id, $this->other->id]);
        $ownOnly = $this->subscriber('own-only@example.test', [$this->own->id]);
        $ownCategory = $this->category($this->own, 'Own category');
        $otherCategory = $this->category($this->other, 'Other category');
        $this->subscribe($shared, $ownCategory, $otherCategory);
        $this->subscribe($ownOnly, $ownCategory);
        $deliveries = [];
        foreach ([$this->own, $this->other] as $project) {
            $template = Templates::query()->create([
                'project_id' => $project->id, 'name' => 'Template '.$project->id, 'body' => 'Hello', 'prior' => 0,
            ]);
            $deliveries[] = ReadySent::query()->create([
                'project_id' => $project->id, 'subscriber_id' => $shared->id, 'email' => $shared->email,
                'template_id' => $template->id, 'template' => $template->name, 'success' => 1,
            ]);
        }

        $this->delete(route('admin.projects.destroy', $this->own->id))->assertNoContent();

        $this->assertModelExists($shared);
        $this->assertModelExists($ownOnly);
        $this->assertSame([$this->other->id], $shared->projects()->pluck('projects.id')->all());
        $this->assertSame(0, $ownOnly->projects()->count());
        $this->assertEqualsCanonicalizing([$ownCategory->id, $otherCategory->id], $shared->subscriptions()->pluck('category_id')->all());
        $this->assertSame([$ownCategory->id], $ownOnly->subscriptions()->pluck('category_id')->all());
        $this->assertNull($ownCategory->fresh()->project_id);
        $this->assertSame($this->other->id, $otherCategory->fresh()->project_id);
        $this->assertModelMissing($deliveries[0]);
        $this->assertModelExists($deliveries[1]);
        $this->get(route('admin.subscribers.edit', $ownOnly->id))->assertOk();
        $this->actingAs($this->moderator)->get(route('admin.subscribers.edit', $ownOnly->id))->assertNotFound();
    }

    private function subscriber(string $email, array $projectIds = []): Subscribers
    {
        return $this->subscriberFixture([
            'email' => $email, 'name' => 'Saved name', 'active' => 1,
            'token' => bin2hex(random_bytes(16)),
        ], $projectIds);
    }

    private function category(Project $project, string $name): Category
    {
        return Category::query()->create(['project_id' => $project->id, 'name' => $name]);
    }

    private function subscribe(Subscribers $subscriber, Category ...$categories): void
    {
        foreach ($categories as $category) {
            Subscriptions::query()->create(['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);
        }
    }
}
