<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Logs;
use App\Models\Project;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Schedule;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use App\Models\Templates;
use App\Models\User;
use App\Services\ProjectAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InactiveProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $moderator;
    private Project $activeProject;
    private Project $inactiveProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->user(User::ROLE_ADMIN);
        $this->manager = $this->user(User::ROLE_PROJECT_ADMIN);
        $this->moderator = $this->user(User::ROLE_MODERATOR);
        $this->activeProject = Project::query()->create([
            'name' => 'Visible active project', 'status' => 1, 'owner_id' => $this->manager->id,
        ]);
        $this->inactiveProject = Project::query()->create([
            'name' => 'Hidden inactive project', 'status' => 0, 'owner_id' => $this->manager->id,
        ]);
        foreach ([$this->activeProject, $this->inactiveProject] as $project) {
            $project->members()->attach($this->moderator->id, ['role' => User::ROLE_MODERATOR]);
        }
    }

    public function test_only_administrators_can_manage_inactive_projects_and_reactivate_them(): void
    {
        $template = $this->template($this->inactiveProject->id);

        foreach ([$this->admin, $this->manager, $this->moderator] as $user) {
            $this->assertEqualsCanonicalizing(
                [Project::DEFAULT_ID, $this->activeProject->id],
                ProjectAccess::projects('view', $user)->pluck('id')->all()
            );
            $this->assertFalse(ProjectAccess::can($this->inactiveProject, 'view', $user));
            $this->assertTrue(ProjectAccess::can(Project::DEFAULT_ID, 'view', $user));
        }

        $this->actingAs($this->manager);
        $rows = $this->getJson(route('admin.datatable.projects'))->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([Project::DEFAULT_ID, $this->activeProject->id], array_column($rows, 'id'));
        $this->get(route('admin.projects.edit', $this->inactiveProject->id))->assertNotFound();
        $this->put(route('admin.projects.update'), [
            'id' => $this->inactiveProject->id, 'name' => $this->inactiveProject->name, 'status' => 1,
        ])->assertNotFound();
        $this->delete(route('admin.projects.destroy', $this->inactiveProject->id))->assertNotFound();
        $this->assertDatabaseHas('projects', ['id' => $this->inactiveProject->id, 'status' => 0]);

        $this->actingAs($this->admin);
        $rows = $this->getJson(route('admin.datatable.projects'))->assertOk()->json('data');
        $this->assertContains($this->inactiveProject->id, array_column($rows, 'id'));
        $this->get(route('admin.projects.edit', $this->inactiveProject->id))->assertOk();
        $this->get(route('admin.templates.edit', $template->id))->assertNotFound();
        $this->put(route('admin.projects.update'), [
            'id' => $this->inactiveProject->id, 'name' => $this->inactiveProject->name, 'status' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.projects.index'));
        $this->assertDatabaseHas('projects', ['id' => $this->inactiveProject->id, 'status' => 1]);
        $this->assertDatabaseHas('project_user', ['project_id' => $this->inactiveProject->id, 'user_id' => $this->moderator->id]);
        $this->actingAs($this->manager)->get(route('admin.templates.edit', $template->id))->assertOk();
        $this->assertTrue(ProjectAccess::can($this->inactiveProject, 'view', $this->moderator));
    }

    public function test_owning_only_an_inactive_project_does_not_grant_a_moderator_management_access(): void
    {
        $this->inactiveProject->update(['owner_id' => $this->moderator->id]);
        $this->assertFalse($this->moderator->canManageProjects());
        $this->actingAs($this->moderator);

        foreach (['admin.projects.index', 'admin.templates.index', 'admin.schedule.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->get(route('admin.projects.edit', $this->inactiveProject->id))->assertForbidden();
        $this->get(route('admin.subscribers.index'))->assertOk();
        $this->assertTrue(ProjectAccess::can(Project::DEFAULT_ID));
    }

    #[DataProvider('managingRoles')]
    public function test_inactive_templates_and_schedules_cannot_be_listed_selected_or_mutated(string $role): void
    {
        $activeTemplate = $this->template($this->activeProject->id);
        $inactiveTemplate = $this->template($this->inactiveProject->id);
        $defaultTemplate = $this->template(Project::DEFAULT_ID);
        $category = Category::query()->create(['project_id' => $this->inactiveProject->id, 'name' => 'Inactive category']);
        $activeSchedule = $this->schedule($activeTemplate);
        $inactiveSchedule = $this->schedule($inactiveTemplate);
        $this->actingAs($this->forRole($role));

        $rows = $this->getJson(route('admin.datatable.templates'))->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([$activeTemplate->id, $defaultTemplate->id], array_column($rows, 'id'));
        $this->get(route('admin.templates.create'))->assertOk()
            ->assertSee($this->activeProject->name)->assertDontSee($this->inactiveProject->name);
        foreach (['admin.templates.show', 'admin.templates.edit'] as $route) {
            $this->get(route($route, $inactiveTemplate->id))->assertNotFound();
        }
        $payload = ['project_id' => $this->inactiveProject->id, 'name' => 'Rejected template', 'body' => 'Body', 'prior' => 0];
        $this->post(route('admin.templates.store'), $payload)->assertSessionHasErrors('project_id');
        $this->put(route('admin.templates.update'), [...$payload, 'id' => $inactiveTemplate->id])->assertForbidden();
        $this->delete(route('admin.templates.destroy', $inactiveTemplate->id))->assertNotFound();

        $this->get(route('admin.schedule.create'))->assertOk()
            ->assertSee($activeTemplate->name)->assertDontSee($inactiveTemplate->name);
        $events = $this->getJson(route('admin.schedule.list', [
            'start' => now()->toDateString(), 'end' => now()->addWeek()->toDateString(),
        ]))->assertOk()->json();
        $this->assertSame([$activeSchedule->id], array_column($events, 'id'));
        $this->get(route('admin.schedule.edit', $inactiveSchedule->id))->assertNotFound();
        $this->postJson(route('admin.schedule.calendarEvents'), ['id' => $inactiveSchedule->id, 'type' => 'delete'])->assertNotFound();
        $this->deleteJson(route('admin.schedule.destroy', $inactiveSchedule->id))->assertNotFound();
        $this->post(route('admin.schedule.store'), [
            'event_name' => 'Rejected schedule', 'template_id' => $inactiveTemplate->id,
            'categoryId' => [$category->id],
            'date_interval' => now()->addDays(3)->format('d.m.Y H:i').' - '.now()->addDays(3)->addHour()->format('d.m.Y H:i'),
        ])->assertSessionHasErrors('template_id');
        $this->assertModelExists($inactiveTemplate);
        $this->assertModelExists($inactiveSchedule);
    }

    #[DataProvider('allRoles')]
    public function test_subscribers_need_an_active_membership_and_only_admins_see_true_orphans(string $role): void
    {
        $active = $this->subscriber('active', [$this->activeProject->id]);
        $inactive = $this->subscriber('inactive', [$this->inactiveProject->id]);
        $shared = $this->subscriber('shared', [$this->activeProject->id, $this->inactiveProject->id]);
        $default = $this->subscriber('default', [Project::DEFAULT_ID]);
        $orphan = $this->subscriber('orphan', []);
        $this->actingAs($this->forRole($role));

        $rows = $this->getJson(route('admin.datatable.subscribers'))->assertOk()->json('data');
        $expected = [$active->id, $shared->id, $default->id];
        if ($role === User::ROLE_ADMIN) {
            $expected[] = $orphan->id;
        }
        $this->assertEqualsCanonicalizing($expected, array_column($rows, 'id'));
        $this->assertStringNotContainsString($this->inactiveProject->name, json_encode($rows));
        $this->get(route('admin.subscribers.edit', $inactive->id))->assertNotFound();
        $this->delete(route('admin.subscribers.destroy', $inactive->id))->assertNotFound();
        $this->put(route('admin.subscribers.update'), [
            'id' => $inactive->id, 'email' => $inactive->email, 'name' => 'Forbidden',
            'project_ids' => [$this->activeProject->id],
        ])->assertForbidden();
        $this->post(route('admin.subscribers.status'), ['action' => 2, 'activate' => [$active->id, $inactive->id]])->assertForbidden();
        $this->assertModelExists($active);
        $this->assertModelExists($inactive);

        foreach (['admin.subscribers.create', 'admin.subscribers.import', 'admin.subscribers.export'] as $route) {
            $this->get(route($route))->assertOk()->assertSee($this->activeProject->name)->assertDontSee($this->inactiveProject->name);
        }
        $this->post(route('admin.subscribers.store'), [
            'email' => 'rejected@example.test', 'project_ids' => [$this->inactiveProject->id],
        ])->assertSessionHasErrors('project_ids.0');
        $this->post(route('admin.subscribers.export_subscribers'), [
            'project_ids' => [$this->inactiveProject->id], 'export_type' => 'text', 'compress' => 'none',
        ])->assertSessionHasErrors('project_ids.0');
        $export = $this->post(route('admin.subscribers.export_subscribers'), [
            'project_ids' => [$this->activeProject->id], 'export_type' => 'text', 'compress' => 'none',
        ])->assertOk()->streamedContent();
        $this->assertStringContainsString($active->email, $export);
        $this->assertStringNotContainsString($inactive->email, $export);
    }

    #[DataProvider('allRoles')]
    public function test_editing_a_shared_subscriber_preserves_hidden_memberships_and_categories(string $role): void
    {
        $subscriber = $this->subscriber('shared', [$this->activeProject->id, $this->inactiveProject->id]);
        $category = Category::query()->create(['project_id' => $this->inactiveProject->id, 'name' => 'Hidden category']);
        Subscriptions::query()->create(['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);
        $this->actingAs($this->forRole($role));
        $this->get(route('admin.subscribers.edit', $subscriber->id))->assertOk()
            ->assertDontSee($this->inactiveProject->name)->assertDontSee($category->name);
        $this->put(route('admin.subscribers.update'), [
            'id' => $subscriber->id, 'email' => $subscriber->email, 'name' => 'Updated shared name',
            'project_ids' => [$this->activeProject->id],
        ])->assertSessionHasNoErrors()->assertSessionMissing('error')->assertRedirect(route('admin.subscribers.index'));
        $this->assertEqualsCanonicalizing(
            [$this->activeProject->id, $this->inactiveProject->id],
            $subscriber->projects()->pluck('projects.id')->all()
        );
        $this->assertDatabaseHas('subscriptions', ['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);

        if ($role !== User::ROLE_ADMIN) {
            $this->delete(route('admin.subscribers.destroy', $subscriber->id))->assertSuccessful();
            $this->assertModelExists($subscriber);
            $this->assertSame([$this->inactiveProject->id], $subscriber->projects()->pluck('projects.id')->all());
            $this->assertDatabaseHas('subscriptions', ['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);
            $this->get(route('admin.subscribers.edit', $subscriber->id))->assertNotFound();
        }
    }

    #[DataProvider('allRoles')]
    public function test_mailing_reports_redirects_and_dashboard_exclude_inactive_project_data(string $role): void
    {
        $activeTemplate = $this->template($this->activeProject->id);
        $inactiveTemplate = $this->template($this->inactiveProject->id);
        $active = $this->subscriber('active', [$this->activeProject->id]);
        $inactive = $this->subscriber('inactive', [$this->inactiveProject->id]);
        $mixedLog = Logs::query()->create(['time' => now()]);
        $inactiveLog = Logs::query()->create(['time' => now()]);
        $this->delivery($activeTemplate, $active, $mixedLog);
        $this->delivery($inactiveTemplate, $inactive, $mixedLog);
        $this->delivery($inactiveTemplate, $inactive, $inactiveLog);
        Redirect::query()->create(['project_id' => $this->activeProject->id, 'email' => $active->email, 'url' => 'https://example.test/visible']);
        Redirect::query()->create(['project_id' => $this->inactiveProject->id, 'email' => $inactive->email, 'url' => 'https://example.test/hidden']);
        $this->actingAs($this->forRole($role));

        $summary = $this->getJson(route('admin.datatable.logs'))->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($mixedLog->id, $summary->json('data.0.id'));
        $this->assertSame('1', strip_tags($summary->json('data.0.count')));
        $this->getJson(route('admin.datatable.info_log', $mixedLog->id))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', $active->email);
        foreach (['admin.log.info', 'admin.log.report', 'admin.datatable.info_log'] as $route) {
            $this->get(route($route, $inactiveLog->id))->assertNotFound();
        }
        $this->getJson(route('admin.datatable.redirect'))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.url', 'https://example.test/visible');
        $hiddenUrl = rtrim(strtr(base64_encode('https://example.test/hidden'), '+/', '-_'), '=');
        $this->get(route('admin.redirect.report', $hiddenUrl))->assertNotFound();
        $stats = $this->get(route('admin.dashboard.index'))->assertOk()->viewData('stats');
        $this->assertSame(1, $stats['subscribers']);
        $this->assertSame(1, $stats['sentTotal']);
        $this->assertSame(1, $stats['readTotal']);
        $this->assertSame(1, $stats['clicks']);
    }

    public static function allRoles(): array
    {
        return [[User::ROLE_ADMIN], [User::ROLE_PROJECT_ADMIN], [User::ROLE_MODERATOR]];
    }

    public static function managingRoles(): array
    {
        return [[User::ROLE_ADMIN], [User::ROLE_PROJECT_ADMIN]];
    }

    private function user(string $role): User
    {
        return User::query()->create(['name' => $role, 'login' => $role, 'role' => $role, 'password' => 'password']);
    }

    private function forRole(string $role): User
    {
        return match ($role) {
            User::ROLE_ADMIN => $this->admin,
            User::ROLE_PROJECT_ADMIN => $this->manager,
            User::ROLE_MODERATOR => $this->moderator,
        };
    }

    private function template(int $projectId): Templates
    {
        return Templates::query()->create(['project_id' => $projectId, 'name' => 'Template project '.$projectId, 'body' => 'Body', 'prior' => 0]);
    }

    private function schedule(Templates $template): Schedule
    {
        return Schedule::query()->create([
            'project_id' => $template->project_id, 'template_id' => $template->id,
            'event_name' => 'Schedule '.$template->id, 'event_start' => now()->addDays(3), 'event_end' => now()->addDays(3)->addHour(),
        ]);
    }

    private function subscriber(string $name, array $projectIds): Subscribers
    {
        return $this->subscriberFixture([
            'name' => $name, 'email' => $name.'@example.test', 'token' => md5($name), 'active' => 1,
        ], $projectIds);
    }

    private function delivery(Templates $template, Subscribers $subscriber, Logs $log): ReadySent
    {
        return ReadySent::query()->create([
            'project_id' => $template->project_id, 'template_id' => $template->id, 'template' => $template->name,
            'subscriber_id' => $subscriber->id, 'email' => $subscriber->email,
            'success' => 1, 'readMail' => 1, 'log_id' => $log->id,
        ]);
    }
}
