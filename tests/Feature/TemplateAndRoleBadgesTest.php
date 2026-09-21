<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attach;
use App\Models\Project;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateAndRoleBadgesTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_table_has_localized_badges_for_every_priority_and_attachment_state(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN));
        $expected = [];

        foreach ([0 => 'primary', 1 => 'danger', 2 => 'secondary', 99 => 'primary'] as $priority => $color) {
            $template = $this->template($priority);
            $expected[$template->id] = [$priority, $color];

            if ($priority === 1) {
                Attach::query()->create([
                    'template_id' => $template->id,
                    'name' => 'example.txt',
                    'file_name' => 'example.txt',
                ]);
            }
        }

        foreach (['en', 'ru'] as $locale) {
            $response = $this->getJson(route('admin.datatable.templates'), ['Accept-Language' => $locale])->assertOk();
            $this->assertSame(4, $response->json('recordsTotal'));

            foreach ($response->json('data') as $row) {
                [$priority, $color] = $expected[$row['id']];
                $key = match ($priority) {
                    1 => 'high',
                    2 => 'low',
                    default => 'normal',
                };

                $this->assertSame(
                    '<span class="badge text-bg-'.$color.'">'.e(__('frontend.str.'.$key)).'</span>',
                    $row['prior']
                );
                $this->assertSame(
                    '<span class="badge text-bg-'.($priority === 1 ? 'success' : 'secondary').'">'
                        .e(__('frontend.str.'.($priority === 1 ? 'yes' : 'no'))).'</span>',
                    $row['attach']
                );
            }
        }
    }

    public function test_raw_badge_columns_do_not_disable_escaping_of_template_names_or_project_names(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $projectName = '<img src=x onerror="alert(1)">';
        $templateName = '<script>alert("template")</script>';
        $project = Project::query()->create(['name' => $projectName, 'status' => 1, 'owner_id' => $admin->id]);
        $this->template(0, ['project_id' => $project->id, 'name' => $templateName]);

        $row = $this->actingAs($admin)->getJson(route('admin.datatable.templates'))->assertOk()->json('data.0');

        $this->assertSame(e($projectName), $row['project']);
        $this->assertStringContainsString(e($templateName), $row['name']);
        $this->assertStringNotContainsString('<script>', $row['name']);
        $this->assertStringNotContainsString('<img', $row['project']);
    }

    public function test_user_role_badges_are_localized_and_unknown_legacy_roles_remain_escaped(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $manager = $this->user(User::ROLE_PROJECT_ADMIN);
        $moderator = $this->user(User::ROLE_MODERATOR);
        $unknownRole = '<img src=x onerror="alert(1)">';
        $unknown = $this->user($unknownRole);
        $expected = [
            $admin->id => ['admin', 'danger'],
            $manager->id => ['project_admin', 'primary'],
            $moderator->id => ['moderator', 'success'],
        ];
        $this->actingAs($admin);

        foreach (['en', 'ru'] as $locale) {
            $response = $this->getJson(route('admin.datatable.users'), ['Accept-Language' => $locale])->assertOk();
            $rows = collect($response->json('data'))->keyBy('id');

            foreach ($expected as $id => [$role, $color]) {
                $this->assertSame(
                    '<span class="badge text-bg-'.$color.'">'.e(__('frontend.str.projects.roles.'.$role)).'</span>',
                    $rows[$id]['role']
                );
            }

            $this->assertStringContainsString(e($unknownRole), $rows[$unknown->id]['role']);
            $this->assertStringNotContainsString('<img', $rows[$unknown->id]['role']);
            $this->assertSame(UserRole::options(), User::getOptions());
        }

        $this->assertSame(UserRole::Admin->value, User::ROLE_ADMIN);
        $this->assertSame(UserRole::ProjectAdmin->value, User::ROLE_PROJECT_ADMIN);
        $this->assertSame(UserRole::Moderator->value, User::ROLE_MODERATOR);
        $this->assertEqualsCanonicalizing(['admin', 'project_admin', 'moderator'], UserRole::values());
        $this->assertIsString($admin->fresh()->role);
        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertTrue($manager->fresh()->isProjectAdmin());
        $this->assertTrue($moderator->fresh()->isModerator());
        $this->assertFalse($unknown->fresh()->isAdmin());
    }

    public function test_template_create_and_update_accept_only_supported_priority_values(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN));

        foreach ([0, 1, 2] as $priority) {
            $payload = [
                'project_id' => Project::DEFAULT_ID,
                'name' => 'Template priority '.$priority,
                'body' => '<p>Priority validation</p>',
                'prior' => (string) $priority,
            ];
            $this->post(route('admin.templates.store'), $payload)
                ->assertSessionHasNoErrors()->assertRedirect(route('admin.templates.index'));
            $template = Templates::query()->where('name', $payload['name'])->firstOrFail();
            $this->assertSame($priority, (int) $template->prior);

            $updatedPriority = ($priority + 1) % 3;
            $this->put(route('admin.templates.update'), [
                ...$payload, 'id' => $template->id, 'prior' => (string) $updatedPriority,
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.templates.index'));
            $this->assertSame($updatedPriority, (int) $template->fresh()->prior);
        }

        foreach ([-1, 3, 99, 'high'] as $invalidPriority) {
            $payload = [
                'project_id' => Project::DEFAULT_ID,
                'name' => 'Rejected priority',
                'body' => '<p>Invalid priority</p>',
                'prior' => $invalidPriority,
            ];
            $this->post(route('admin.templates.store'), $payload)->assertSessionHasErrors('prior');
            $this->put(route('admin.templates.update'), [...$payload, 'id' => $template->id])
                ->assertSessionHasErrors('prior');
            $this->assertSame($updatedPriority, (int) $template->fresh()->prior);
        }

        $this->assertDatabaseCount('templates', 3);
        $this->assertDatabaseMissing('templates', ['name' => 'Rejected priority']);
    }

    private function template(int $priority, array $attributes = []): Templates
    {
        return Templates::query()->create([
            'project_id' => Project::DEFAULT_ID,
            'name' => 'Template '.$priority,
            'body' => '<p>Message body</p>',
            'prior' => $priority,
            ...$attributes,
        ]);
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => 'User '.User::query()->count(),
            'login' => 'badge-user-'.User::query()->count(),
            'role' => $role,
            'password' => 'test-password',
        ]);
    }
}
