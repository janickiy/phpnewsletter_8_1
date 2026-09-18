<?php

namespace Tests\Feature;

use App\Models\Attach;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Repositories\SubscriberRepository;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProjectDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_deleting_a_project_removes_its_data_and_files_and_preserves_other_projects_and_global_data(): void
    {
        $owner = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project = $this->project($owner);
        $otherProject = $this->project($owner);
        $exclusiveLog = $this->insert('logs', ['time' => '2026-01-01 12:00:00', 'user_id' => $owner->id]);
        $sharedLog = $this->insert('logs', ['time' => '2026-01-02 12:00:00', 'user_id' => $owner->id]);
        $unrelatedEmptyLog = $this->insert('logs', ['time' => '2026-01-03 12:00:00', 'user_id' => $owner->id]);
        $deleted = $this->projectData($project, $moderator, $exclusiveLog['id']);
        $retained = $this->projectData($otherProject, $moderator, $sharedLog['id']);
        $sharedMail = $deleted['ready_sent'];
        unset($sharedMail['id']);
        $sharedMail['log_id'] = $sharedLog['id'];
        $sharedMail = $this->insert('ready_sent', $sharedMail);
        $globalData = $this->globalData($owner);
        $usersBefore = DB::table('users')->orderBy('id')->get()->toJson();

        $this->actingAs($owner)->delete(route('admin.projects.destroy', $project->id))->assertNoContent();

        $this->assertModelMissing($project);
        foreach ($deleted as $table => $row) {
            if (in_array($table, ['subscribers', 'subscriptions'], true)) {
                $this->assertDatabaseHas($table, $row);
                continue;
            }
            if ($table === 'categories') {
                $this->assertDatabaseHas($table, [...$row, 'project_id' => null]);
                continue;
            }
            $this->assertDatabaseMissing($table, isset($row['id']) ? ['id' => $row['id']] : $row);
        }
        $this->assertDatabaseMissing('ready_sent', ['id' => $sharedMail['id']]);
        $this->assertDatabaseMissing('logs', ['id' => $exclusiveLog['id']]);
        $this->assertDatabaseHas('logs', $sharedLog);
        $this->assertDatabaseHas('logs', $unrelatedEmptyLog);
        $this->assertModelExists($otherProject);
        foreach ([...$retained, ...$globalData] as $table => $row) {
            $this->assertDatabaseHas($table, $row);
        }
        $this->assertSame($usersBefore, DB::table('users')->orderBy('id')->get()->toJson());
        // RefreshDatabase runs afterCommit callbacks when the repository transaction commits.
        Storage::disk('local')->assertMissing($this->attachmentPath($deleted));
        Storage::disk('local')->assertExists($this->attachmentPath($retained));
    }

    public function test_an_administrator_can_list_and_rename_a_preserved_category_without_losing_subscriptions(): void
    {
        $admin = $this->user('admin');
        $project = $this->project($admin);
        $data = $this->projectData($project, $this->user('moderator'));
        $category = $data['categories'];

        $this->actingAs($admin)->delete(route('admin.projects.destroy', $project->id))->assertNoContent();

        $this->get(route('admin.category.index'))->assertOk();
        $rows = $this->getJson(route('admin.datatable.category', ['draw' => 1, 'start' => 0, 'length' => 20]))
            ->assertOk()->assertJsonPath('recordsTotal', 1)->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame($category['id'], (int) $rows[0]['id']);
        $this->assertSame($category['name'], $rows[0]['name']);
        $this->assertSame(1, (int) $rows[0]['subcount']);
        $this->get(route('admin.category.edit', $category['id']))->assertOk()->assertSee($category['name']);

        $this->put(route('admin.category.update'), [
            'id' => $category['id'], 'project_id' => '', 'name' => 'Preserved category renamed',
        ])->assertRedirect(route('admin.category.index'))->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertDatabaseHas('categories', [
            'id' => $category['id'], 'project_id' => null, 'name' => 'Preserved category renamed',
        ]);
        $this->assertDatabaseHas('subscriptions', $data['subscriptions']);
        $this->assertDatabaseHas('subscribers', $data['subscribers']);
    }

    public function test_project_roles_cannot_access_or_edit_categories_after_their_project_is_deleted(): void
    {
        $owner = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project = $this->project($owner);
        $otherProject = $this->project($owner);
        $otherProject->members()->attach($moderator, ['role' => 'moderator']);
        $data = $this->projectData($project, $moderator);
        DB::table('project_subscriber')->insert([
            'project_id' => $otherProject->id, 'subscriber_id' => $data['subscribers']['id'],
        ]);
        $this->actingAs($owner)->delete(route('admin.projects.destroy', $project->id))->assertNoContent();

        foreach ([$owner, $moderator] as $user) {
            $this->actingAs($user);
            $this->get(route('admin.category.index'))->assertForbidden();
            $this->getJson(route('admin.datatable.category'))->assertForbidden();
            $this->get(route('admin.category.edit', $data['categories']['id']))->assertForbidden();
            $this->put(route('admin.category.update'), [
                'id' => $data['categories']['id'], 'project_id' => '', 'name' => 'Forbidden change',
            ])->assertForbidden();
            $this->get(route('admin.subscribers.edit', $data['subscribers']['id']))
                ->assertOk()->assertDontSee($data['categories']['name']);
            $this->getJson(route('admin.datatable.subscribers', ['draw' => 1, 'start' => 0, 'length' => 20]))
                ->assertOk()->assertDontSee($data['categories']['name']);
        }

        $this->assertDatabaseHas('categories', [...$data['categories'], 'project_id' => null]);
        $this->assertDatabaseHas('subscriptions', $data['subscriptions']);
    }

    public function test_preserved_category_memberships_are_not_used_for_another_projects_mailing(): void
    {
        $owner = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project = $this->project($owner);
        $otherProject = $this->project($owner);
        $data = $this->projectData($project, $moderator);
        $otherData = $this->projectData($otherProject, $moderator);
        DB::table('subscribers')->where('id', $data['subscribers']['id'])->update(['active' => 1]);
        DB::table('project_subscriber')->insert([
            'project_id' => $otherProject->id, 'subscriber_id' => $data['subscribers']['id'],
        ]);

        $this->actingAs($owner)->delete(route('admin.projects.destroy', $project->id))->assertNoContent();

        $repository = app(SubscriberRepository::class);
        $this->assertSame(0, $repository->countSubscriptions([$data['categories']['id']], null, null, $otherProject->id));
        $this->assertCount(0, $repository->getSubscribers(
            1, $otherData['templates']['id'], [$data['categories']['id']], 'subscribers.id', 100
        ));
        $this->get(route('frontend.form', ['project_id' => $otherProject->id]))
            ->assertOk()->assertDontSee($data['categories']['name'])->assertSee($otherData['categories']['name']);
        $this->assertDatabaseHas('subscriptions', $data['subscriptions']);
    }

    public function test_the_project_foreign_key_rejects_raw_deletion_before_categories_are_detached(): void
    {
        $project = $this->project($this->user('admin'));
        $category = $this->insert('categories', ['project_id' => $project->id, 'name' => 'Protected by the foreign key']);
        $subscriber = $this->subscriberFixture(['email' => 'retained@example.test', 'token' => str_repeat('a', 32)]);
        $subscription = ['subscriber_id' => $subscriber->id, 'category_id' => $category['id']];
        DB::table('subscriptions')->insert($subscription);

        try {
            DB::table('projects')->where('id', $project->id)->delete();
            $this->fail('Project deletion must use the repository to preserve associated categories.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->getCode());
        }

        $this->assertModelExists($project);
        $this->assertDatabaseHas('categories', $category);
        $this->assertDatabaseHas('subscriptions', $subscription);
        $this->assertModelExists($subscriber);
    }

    public function test_an_unassigned_manager_and_assigned_non_owner_moderator_cannot_delete_project_data(): void
    {
        $owner = $this->user('project_admin');
        $outsider = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project = $this->project($owner);
        $data = $this->projectData($project, $moderator);
        $before = $this->databaseSnapshot();

        foreach ([[$outsider, 404], [$moderator, 403]] as [$user, $status]) {
            $this->actingAs($user)->delete(route('admin.projects.destroy', $project->id))->assertStatus($status);

            $this->assertFalse($this->app->make(ProjectRepository::class)->delete($project->id));
            $this->assertSame($before, $this->databaseSnapshot());
            Storage::disk('local')->assertExists($this->attachmentPath($data));
        }
    }

    public function test_a_failure_deleting_the_project_rolls_back_all_child_deletions_and_keeps_attachment_files(): void
    {
        $owner = $this->user('project_admin');
        $project = $this->project($owner);
        $log = $this->insert('logs', ['time' => '2026-01-01 12:00:00', 'user_id' => $owner->id]);
        $data = $this->projectData($project, $this->user('moderator'), $log['id']);
        $before = $this->databaseSnapshot();
        $this->actingAs($owner);
        $event = 'eloquent.deleting: '.Project::class;
        Event::listen($event, function (Project $deleting) use ($project): void {
            if ($deleting->id === $project->id) {
                throw new RuntimeException('Forced project deletion failure.');
            }
        });

        try {
            $this->app->make(ProjectRepository::class)->delete($project->id);
            $this->fail('The forced project deletion failure must escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced project deletion failure.', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        // A subsequent commit must not execute a callback left over from the failed deletion.
        DB::transaction(static fn () => null);
        $this->assertSame($before, $this->databaseSnapshot());
        Storage::disk('local')->assertExists($this->attachmentPath($data));
    }

    public function test_rolling_back_an_outer_transaction_restores_project_data_and_does_not_delete_files(): void
    {
        $owner = $this->user('project_admin');
        $project = $this->project($owner);
        $data = $this->projectData($project, $this->user('moderator'));
        $before = $this->databaseSnapshot();
        $this->actingAs($owner);

        try {
            DB::transaction(function () use ($project, $data): void {
                $this->assertTrue($this->app->make(ProjectRepository::class)->delete($project->id));
                $this->assertModelMissing($project);
                Storage::disk('local')->assertExists($this->attachmentPath($data));

                throw new RuntimeException('Forced outer transaction rollback.');
            });
            $this->fail('The outer transaction must roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced outer transaction rollback.', $exception->getMessage());
        }

        DB::transaction(static fn () => null);
        $this->assertSame($before, $this->databaseSnapshot());
        Storage::disk('local')->assertExists($this->attachmentPath($data));
    }

    public function test_an_attachment_file_shared_by_two_projects_is_kept_until_the_last_project_is_deleted(): void
    {
        $owner = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project = $this->project($owner);
        $otherProject = $this->project($owner);
        $data = $this->projectData($project, $moderator);
        $otherData = $this->projectData($otherProject, $moderator);
        DB::table('attach')->where('id', $otherData['attach']['id'])->update(['file_name' => $data['attach']['file_name']]);

        $this->actingAs($owner)->delete(route('admin.projects.destroy', $project->id))->assertNoContent();

        $this->assertDatabaseHas('attach', [
            'id' => $otherData['attach']['id'], 'file_name' => $data['attach']['file_name'],
        ]);
        Storage::disk('local')->assertExists($this->attachmentPath($data));

        $this->delete(route('admin.projects.destroy', $otherProject->id))->assertNoContent();

        Storage::disk('local')->assertMissing($this->attachmentPath($data));
    }

    public function test_a_foreign_schedule_referencing_the_project_template_prevents_all_deletions(): void
    {
        $owner = $this->user('project_admin');
        $moderator = $this->user('moderator');
        $project = $this->project($owner);
        $otherProject = $this->project($owner);
        $data = $this->projectData($project, $moderator);
        $otherData = $this->projectData($otherProject, $moderator);
        DB::table('schedule')->where('id', $otherData['schedule']['id'])->update(['template_id' => $data['templates']['id']]);
        $before = $this->databaseSnapshot();
        $this->actingAs($owner);

        try {
            $this->app->make(ProjectRepository::class)->delete($project->id);
            $this->fail('Deleting a template referenced by another project must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('another project', $exception->getMessage());
        }

        $this->assertSame($before, $this->databaseSnapshot());
        Storage::disk('local')->assertExists($this->attachmentPath($data));
        Storage::disk('local')->assertExists($this->attachmentPath($otherData));
    }

    public function test_a_malformed_attachment_filename_cannot_delete_a_file_outside_the_attachment_directory(): void
    {
        $owner = $this->user('project_admin');
        $project = $this->project($owner);
        $data = $this->projectData($project, $this->user('moderator'));
        DB::table('attach')->where('id', $data['attach']['id'])->update(['file_name' => '..\\..\\outside.txt']);
        Storage::disk('local')->put('outside.txt', 'Keep this file');

        $this->actingAs($owner)->delete(route('admin.projects.destroy', $project->id))->assertNoContent();

        $this->assertModelMissing($project);
        $this->assertDatabaseMissing('attach', ['id' => $data['attach']['id']]);
        $this->assertSame('Keep this file', Storage::disk('local')->get('outside.txt'));
    }

    private function projectData(Project $project, User $member, ?int $logId = null): array
    {
        $rows['project_user'] = $this->insert('project_user', [
            'project_id' => $project->id, 'user_id' => $member->id, 'role' => $member->role,
        ]);
        $rows['templates'] = $this->insert('templates', [
            'project_id' => $project->id, 'name' => 'Template '.$project->id, 'body' => 'Hello', 'prior' => 0,
        ]);
        $rows['attach'] = $this->insert('attach', [
            'template_id' => $rows['templates']['id'], 'name' => 'Document', 'file_name' => 'project-'.$project->id.'.txt',
        ]);
        Storage::disk('local')->put($this->attachmentPath($rows), 'Attachment for project '.$project->id);
        $rows['subscribers'] = $this->insert('subscribers', [
            'name' => 'Subscriber', 'email' => 'subscriber-'.$project->id.'@example.test',
            'token' => str_pad((string) $project->id, 32, '0', STR_PAD_LEFT),
        ]);
        $rows['project_subscriber'] = [
            'project_id' => $project->id, 'subscriber_id' => $rows['subscribers']['id'],
        ];
        DB::table('project_subscriber')->insert($rows['project_subscriber']);
        $rows['categories'] = $this->insert('categories', [
            'project_id' => $project->id, 'name' => 'Category '.$project->id,
        ]);
        $rows['subscriptions'] = [
            'subscriber_id' => $rows['subscribers']['id'], 'category_id' => $rows['categories']['id'],
        ];
        DB::table('subscriptions')->insert($rows['subscriptions']);
        $rows['schedule'] = $this->insert('schedule', [
            'project_id' => $project->id, 'template_id' => $rows['templates']['id'], 'event_name' => 'Scheduled mailing',
            'event_start' => '2026-01-01 12:00:00', 'event_end' => '2026-01-01 13:00:00',
        ]);
        $rows['schedule_category'] = [
            'schedule_id' => $rows['schedule']['id'], 'category_id' => $rows['categories']['id'],
        ];
        DB::table('schedule_category')->insert($rows['schedule_category']);
        $rows['ready_sent'] = $this->insert('ready_sent', [
            'project_id' => $project->id, 'subscriber_id' => $rows['subscribers']['id'],
            'template_id' => $rows['templates']['id'], 'schedule_id' => $rows['schedule']['id'],
            'email' => $rows['subscribers']['email'], 'template' => $rows['templates']['name'], 'success' => 1, 'log_id' => $logId,
        ]);
        $rows['redirect'] = $this->insert('redirect', [
            'project_id' => $project->id, 'url' => 'https://example.test/project/'.$project->id, 'email' => $rows['subscribers']['email'],
        ]);

        return $rows;
    }

    private function globalData(User $owner): array
    {
        return [
            'smtp' => $this->insert('smtp', [
                'host' => 'smtp.example.test', 'username' => 'mailer', 'email' => 'mailer@example.test',
                'password' => 'smtp-secret', 'port' => 587, 'authentication' => 'LOGIN', 'secure' => 'tls', 'timeout' => 30,
            ]),
            'settings' => $this->insert('settings', ['name' => 'project-deletion-fixture', 'value' => 'Keep this setting']),
            'customheaders' => $this->insert('customheaders', ['name' => 'X-Fixture', 'value' => 'Keep this header']),
            'macros' => $this->insert('macros', ['name' => 'deletion_fixture', 'value' => 'Keep this macro', 'type' => 1]),
            'process' => $this->insert('process', ['user_id' => $owner->id, 'command' => 'start']),
        ];
    }

    private function databaseSnapshot(): array
    {
        $snapshot = [];
        foreach (['projects', 'project_user', 'project_subscriber', 'templates', 'attach', 'subscribers', 'categories', 'subscriptions',
            'schedule', 'schedule_category', 'ready_sent', 'redirect', 'logs', 'users'] as $table) {
            $query = DB::table($table);
            foreach (match ($table) {
                'subscriptions' => ['category_id', 'subscriber_id'],
                'project_subscriber' => ['project_id', 'subscriber_id'],
                'schedule_category' => ['schedule_id', 'category_id'],
                default => ['id'],
            } as $column) {
                $query->orderBy($column);
            }
            $snapshot[$table] = $query->get()->toJson();
        }

        return $snapshot;
    }

    private function insert(string $table, array $attributes): array
    {
        return ['id' => DB::table($table)->insertGetId($attributes), ...$attributes];
    }

    private function attachmentPath(array $rows): string
    {
        return Attach::DIRECTORY.'/'.$rows['attach']['file_name'];
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role), 'login' => $role.'-'.User::query()->count(), 'role' => $role, 'password' => 'secret123',
        ]);
    }

    private function project(User $owner): Project
    {
        return Project::query()->create(['name' => 'Project '.Project::query()->count(), 'owner_id' => $owner->id, 'status' => true]);
    }
}
