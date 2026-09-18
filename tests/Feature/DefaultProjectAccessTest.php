<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DefaultProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Project $default;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->user(User::ROLE_ADMIN);
        $this->default = Project::defaultProject();
    }

    public function test_default_project_is_virtual_active_and_has_no_owner(): void
    {
        $private = Project::query()->create(['name' => 'Private', 'owner_id' => $this->admin->id, 'status' => 1]);

        $this->assertSame(0, $this->default->id);
        $this->assertTrue($this->default->status);
        $this->assertNull($this->default->owner_id);
        $this->assertNull($this->default->owner);
        $this->assertTrue(Project::query()->whereKey(Project::DEFAULT_ID)->doesntExist());
        $this->assertGreaterThan(0, $private->id);
        $this->assertDatabaseCount('projects', 1);
    }

    public function test_all_roles_see_default_without_membership_while_private_projects_remain_scoped(): void
    {
        $private = Project::query()->create(['name' => 'Private', 'owner_id' => $this->admin->id, 'status' => 1]);
        foreach ([$this->admin, $this->user(User::ROLE_PROJECT_ADMIN), $this->user(User::ROLE_MODERATOR)] as $user) {
            $this->assertTrue(ProjectAccess::can(0, 'view', $user));
            $this->assertSame($user->isAdmin(), ProjectAccess::can($private, 'view', $user));
        }
        $this->assertSame(0, $this->default->members()->count());
        $this->assertFalse(ProjectAccess::can(0));
    }

    public function test_default_cannot_be_edited_disabled_or_deleted_by_any_role(): void
    {
        foreach ([$this->admin, $this->user(User::ROLE_PROJECT_ADMIN), $this->user(User::ROLE_MODERATOR)] as $user) {
            $this->actingAs($user)->get(route('admin.projects.edit', 0))->assertForbidden();
            $this->delete(route('admin.projects.destroy', 0))->assertForbidden();
            foreach ([0, 1] as $status) {
                $this->put(route('admin.projects.update'), ['id' => 0, 'name' => 'Changed', 'status' => $status])
                    ->assertForbidden();
            }
        }
        $this->assertTrue(Project::defaultProject()->status);
        $this->assertDatabaseCount('projects', 0);

        $rows = $this->actingAs($this->admin)->getJson(route('admin.datatable.projects'))->assertOk()->json('data');
        $this->assertSame(0, $rows[0]['id']);
        $this->assertSame('', $rows[0]['actions']);
    }

    public function test_default_project_name_is_translated_for_every_interface_language(): void
    {
        $names = [
            'ar' => 'المشروع الافتراضي',
            'de' => 'Standardprojekt',
            'en' => 'Default project',
            'es' => 'Proyecto predeterminado',
            'fr' => 'Projet par défaut',
            'hi' => 'डिफ़ॉल्ट प्रोजेक्ट',
            'pt' => 'Projeto padrão',
            'ru' => 'Основной проект',
            'zh-cn' => '默认项目',
        ];

        foreach ($names as $locale => $name) {
            app()->setLocale($locale);
            $this->assertSame($name, Project::defaultProject()->name);
            $this->assertSame($name, ProjectAccess::projects('view', $this->admin)->findOrFail(0)->name);
        }

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_virtual_project_cannot_be_saved_through_the_model(): void
    {
        foreach ([Project::defaultProject(), Project::query()->includingDefault()->findOrFail(0)] as $project) {
            try {
                $project->fill(['name' => 'Changed', 'status' => false])->save();
                $this->fail('The virtual default project must never be saved.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('default project is virtual', $exception->getMessage());
            }
        }

        $this->assertDatabaseCount('projects', 0);
        $this->assertTrue(Project::defaultProject()->status);
    }

    public function test_hydrated_virtual_project_cannot_be_deleted_through_the_model(): void
    {
        try {
            Project::query()->includingDefault()->findOrFail(0)->delete();
            $this->fail('The virtual default project must never be deleted.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('default project is virtual', $exception->getMessage());
        }

        $this->assertDatabaseCount('projects', 0);
        $this->assertTrue(Project::query()->includingDefault()->whereKey(0)->exists());
    }

    public function test_project_manager_can_use_default_but_cannot_change_its_shared_settings(): void
    {
        $manager = $this->user(User::ROLE_PROJECT_ADMIN);
        $this->assertTrue(ProjectAccess::can(0, 'manage', $manager));
        $this->actingAs($manager)->get(route('admin.projects.edit', 0))->assertForbidden();
        $this->put(route('admin.projects.update'), ['id' => 0, 'name' => 'Changed', 'status' => 1])->assertForbidden();
        $rows = $this->getJson(route('admin.datatable.projects'))->assertOk()->json('data');
        $this->assertSame(0, $rows[0]['id']);
        $this->assertSame('', $rows[0]['actions']);
    }

    public function test_default_access_preserves_moderator_route_permissions(): void
    {
        $this->actingAs($this->user(User::ROLE_MODERATOR));
        $this->get(route('admin.subscribers.create'))->assertOk();
        $this->get(route('admin.templates.create'))->assertForbidden();
        $this->get(route('admin.schedule.create'))->assertForbidden();
        $this->post(route('admin.log.clear'))->assertForbidden();
        $this->post(route('admin.redirect.clear'))->assertForbidden();
    }

    private function user(string $role, ?string $login = null): User
    {
        return User::query()->create(['name' => $role, 'login' => $login ?? $role, 'role' => $role, 'password' => 'password']);
    }
}
