<?php

namespace Tests\Feature;

use App\DTO\Update\ProjectUpdateData;
use App\Models\Project;
use App\Models\Templates;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Services\ProjectAccess;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_administrator_creates_project_as_owner_and_cannot_forge_owner(): void
    {
        $creator = $this->user('project_admin');
        $other = $this->user('admin');
        $this->actingAs($creator);

        $this->post(route('admin.projects.store'), $this->payload())
            ->assertRedirect(route('admin.projects.index'));
        $this->assertDatabaseHas('projects', ['name' => 'Project', 'owner_id' => $creator->id]);

        $this->post(route('admin.projects.store'), [...$this->payload(), 'name' => 'Forged owner', 'owner_id' => $other->id])
            ->assertForbidden();
        $this->assertDatabaseMissing('projects', ['name' => 'Forged owner']);
    }

    public function test_moderator_cannot_create_a_project_or_enter_projects_without_ownership(): void
    {
        $this->actingAs($this->user('moderator'));
        $this->get(route('admin.projects.index'))->assertForbidden();
        $this->get(route('admin.projects.create'))->assertForbidden();
        $this->post(route('admin.projects.store'), $this->payload())->assertForbidden();
    }

    public function test_project_lists_are_limited_to_owned_or_assigned_projects(): void
    {
        $admin = $this->user('admin');
        $owner = $this->user('project_admin');
        $manager = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $owned = $this->project($owner);
        $assigned = $this->project($admin);
        $other = $this->project($admin);
        $assigned->members()->attach($owner, ['role' => 'project_admin']);
        $assigned->members()->attach($manager, ['role' => 'project_admin']);
        $assigned->members()->attach($moderator, ['role' => 'moderator']);

        $this->assertEqualsCanonicalizing([$owned->id, $assigned->id], ProjectAccess::projects('manage', $owner)->pluck('id')->all());
        $this->assertEquals([$assigned->id], ProjectAccess::projects('manage', $manager)->pluck('id')->all());
        $this->assertEquals([$assigned->id], ProjectAccess::projects('view', $moderator)->pluck('id')->all());
        $this->assertCount(0, ProjectAccess::projects('manage', $moderator)->get());
        $this->assertEqualsCanonicalizing([$owned->id, $assigned->id, $other->id], ProjectAccess::projects('manage', $admin)->pluck('id')->all());

        $this->actingAs($manager);
        $this->getJson(route('admin.datatable.projects'))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $assigned->id);
    }

    public function test_an_unassigned_manager_cannot_view_update_or_delete_another_project(): void
    {
        $owner = $this->user('project_admin');
        $project = $this->project($owner);
        $this->actingAs($this->user('project_admin'));

        $this->get(route('admin.projects.edit', $project->id))->assertNotFound();
        $this->put(route('admin.projects.update'), [...$this->payload(), 'id' => $project->id])->assertNotFound();
        $this->delete(route('admin.projects.destroy', $project->id))->assertNotFound();
        $this->assertModelExists($project);
    }

    public function test_owner_can_assign_administrators_and_moderators_without_changing_global_roles(): void
    {
        $owner = $this->user('project_admin');
        $project = $this->project($owner);
        $manager = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $this->actingAs($owner);

        $this->put(route('admin.projects.update'), [
            ...$this->payload(), 'id' => $project->id,
            'project_admin_ids' => [$manager->id], 'moderator_ids' => [$moderator->id],
        ])->assertRedirect(route('admin.projects.index'));
        $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $manager->id, 'role' => 'project_admin']);
        $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $moderator->id, 'role' => 'moderator']);
        $this->assertSame('project_admin', $manager->fresh()->role);
        $this->assertSame('moderator', $moderator->fresh()->role);

        $this->put(route('admin.projects.update'), [
            ...$this->payload(), 'id' => $project->id,
            'project_admin_ids_present' => 1, 'moderator_ids_present' => 1,
        ])->assertRedirect();
        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id]);
    }

    public function test_assigned_manager_can_manage_moderators_but_cannot_change_owner_or_administrators(): void
    {
        $owner = $this->user('admin');
        $project = $this->project($owner);
        $manager = $this->user('project_admin');
        $peer = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project->members()->attach($manager, ['role' => 'project_admin']);
        $this->actingAs($manager);

        $this->put(route('admin.projects.update'), [
            ...$this->payload(), 'id' => $project->id, 'moderator_ids' => [$moderator->id],
        ])->assertRedirect();
        $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $moderator->id]);
        $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $manager->id]);

        foreach ([['owner_id' => $manager->id], ['project_admin_ids' => [$peer->id]], ['project_admin_ids_present' => 1], ['project_admin_ids' => []]] as $forged) {
            $this->put(route('admin.projects.update'), [...$this->payload(), 'id' => $project->id, ...$forged])->assertForbidden();
        }
        $this->assertSame($owner->id, $project->fresh()->owner_id);
        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id, 'user_id' => $peer->id]);
    }

    public function test_editing_project_details_without_assignment_fields_preserves_owner_and_both_member_groups(): void
    {
        $owner = $this->user('project_admin');
        $manager = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project = $this->project($owner);
        $project->members()->attach($manager, ['role' => 'project_admin']);
        $project->members()->attach($moderator, ['role' => 'moderator']);

        $this->actingAs($owner)->put(route('admin.projects.update'), [
            'id' => $project->id,
            'name' => 'Updated project',
            'description' => 'Updated description',
            'status' => 0,
        ])->assertRedirect(route('admin.projects.index'));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated project',
            'description' => 'Updated description',
            'status' => 0,
            'owner_id' => $owner->id,
        ]);
        $this->assertSame([
            $manager->id => 'project_admin',
            $moderator->id => 'moderator',
        ], $project->members()->orderBy('users.id')->get()->mapWithKeys(
            fn (User $member) => [$member->id => $member->pivot->role],
        )->all());
    }

    public function test_failed_member_assignment_rolls_back_project_details_ownership_and_existing_members(): void
    {
        $admin = $this->user('admin');
        $owner = $this->user('project_admin');
        $manager = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project = $this->project($owner);
        $project->members()->attach($manager, ['role' => 'project_admin']);
        $project->members()->attach($moderator, ['role' => 'moderator']);
        $this->actingAs($admin);

        try {
            $this->app->make(ProjectRepository::class)->update($project->id, new ProjectUpdateData(
                name: 'Must not be saved',
                status: false,
                description: 'Must not be saved',
                ownerId: $admin->id,
                projectAdminIds: [],
                moderatorIds: [User::query()->max('id') + 1],
            ));
            $this->fail('Assigning a nonexistent user must fail.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'status' => 1,
            'owner_id' => $owner->id,
        ]);
        $this->assertSame([
            $manager->id => 'project_admin',
            $moderator->id => 'moderator',
        ], $project->members()->orderBy('users.id')->get()->mapWithKeys(
            fn (User $member) => [$member->id => $member->pivot->role],
        )->all());
    }

    public function test_assignment_requires_existing_users_with_matching_global_roles(): void
    {
        $admin = $this->user('admin');
        $manager = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $this->actingAs($admin);

        foreach ([
            ['project_admin_ids' => [$moderator->id]],
            ['moderator_ids' => [$manager->id]],
            ['project_admin_ids' => [$admin->id]],
            ['moderator_ids' => [999999]],
        ] as $invalid) {
            $response = $this->post(route('admin.projects.store'), [...$this->payload(), ...$invalid]);
            $response->assertSessionHasErrors(array_key_first($invalid).'.0');
        }
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_only_administrator_can_transfer_ownership(): void
    {
        $admin = $this->user('admin');
        $owner = $this->user('project_admin');
        $replacement = $this->user('project_admin');
        $project = $this->project($owner);
        $payload = [...$this->payload(), 'id' => $project->id, 'owner_id' => $replacement->id];

        $this->actingAs($owner)->put(route('admin.projects.update'), $payload)->assertForbidden();
        $this->actingAs($admin)->put(route('admin.projects.update'), $payload)->assertRedirect();
        $this->assertSame($replacement->id, $project->fresh()->owner_id);
        $this->assertFalse(ProjectAccess::can($project, 'manage', $owner));
        $this->assertTrue(ProjectAccess::can($project, 'manage', $replacement));
    }

    public function test_moderator_who_owns_project_can_manage_it_but_cannot_create_more(): void
    {
        $owner = $this->user('moderator');
        $project = $this->project($owner);
        $manager = $this->user('project_admin');
        $this->actingAs($owner);

        $this->get(route('admin.projects.index'))->assertOk();
        $this->get(route('admin.projects.edit', $project->id))->assertOk();
        $this->put(route('admin.projects.update'), [
            ...$this->payload(), 'id' => $project->id, 'project_admin_ids' => [$manager->id],
        ])->assertRedirect();
        $this->get(route('admin.projects.create'))->assertForbidden();
    }

    public function test_role_changes_remove_memberships_and_project_owners_cannot_be_deleted(): void
    {
        $admin = $this->user('admin');
        $manager = $this->user('project_admin');
        $project = $this->project($manager);
        $project->members()->attach($manager, ['role' => 'project_admin']);
        $this->actingAs($admin);

        $this->put(route('admin.users.update'), [
            'id' => $manager->id, 'name' => $manager->name, 'login' => $manager->login, 'role' => 'moderator',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('project_user', ['user_id' => $manager->id]);
        $this->assertTrue(ProjectAccess::can($project, 'manage', $manager->fresh()));

        $this->delete(route('admin.users.destroy', $manager->id))->assertUnprocessable();
        $this->assertModelExists($manager);
    }

    public function test_empty_projects_and_projects_with_mailing_data_can_be_deleted(): void
    {
        $owner = $this->user('project_admin');
        $project = $this->project($owner);
        $emptyProject = $this->project($owner);
        Templates::query()->create(['name' => 'Saved template', 'body' => 'Hello', 'prior' => 0, 'project_id' => $project->id]);
        $this->actingAs($owner);

        $this->delete(route('admin.projects.destroy', $project->id))->assertNoContent();
        $this->assertModelMissing($project);
        $this->assertDatabaseMissing('templates', ['project_id' => $project->id]);
        $this->delete(route('admin.projects.destroy', $emptyProject->id))->assertNoContent();
        $this->assertModelMissing($emptyProject);
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role), 'login' => $role.'-'.User::query()->count(),
            'role' => $role, 'password' => 'secret123',
        ]);
    }

    private function project(User $owner): Project
    {
        return Project::query()->create([...$this->payload(), 'name' => 'Project '.Project::query()->count(), 'owner_id' => $owner->id]);
    }

    private function payload(): array
    {
        return ['name' => 'Project', 'description' => 'Project description', 'status' => 1];
    }
}
