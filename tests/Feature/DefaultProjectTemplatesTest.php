<?php

namespace Tests\Feature;

use App\DTO\Create\TemplatesCreateData;
use App\Models\Project;
use App\Models\Templates;
use App\Models\User;
use App\Repositories\TemplateRepository;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultProjectTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->user(User::ROLE_ADMIN);
        $this->manager = $this->user(User::ROLE_PROJECT_ADMIN);
    }

    public function test_omitted_empty_and_zero_project_select_the_default_when_creating_templates(): void
    {
        foreach ([$this->admin, $this->manager] as $user) {
            foreach ([[], ['project_id' => null], ['project_id' => ''], ['project_id' => 0], ['project_id' => '0']] as $index => $projectInput) {
                $name = "Default template {$user->id}-{$index}";
                $this->actingAs($user)->post(route('admin.templates.store'), $projectInput + [
                    'name' => $name,
                    'body' => '<p>Default project content</p>',
                    'prior' => 0,
                ])->assertSessionHasNoErrors()->assertRedirect(route('admin.templates.index'));

                $this->assertDatabaseHas('templates', ['name' => $name, 'project_id' => Project::DEFAULT_ID]);
            }
        }
    }

    public function test_template_dto_uses_the_default_project_when_no_project_is_supplied(): void
    {
        $this->actingAs($this->manager);
        $template = app(TemplateRepository::class)->add(new TemplatesCreateData(
            name: 'Repository default template',
            body: '<p>Default project content</p>',
            prior: 0,
        ));

        $this->assertSame(Project::DEFAULT_ID, (int) $template->project_id);
    }

    public function test_default_project_is_the_first_selected_option_without_a_placeholder(): void
    {
        $project = Project::query()->create(['name' => 'A private project', 'owner_id' => $this->manager->id, 'status' => 1]);
        $foreign = Project::query()->create(['name' => 'Another private project', 'owner_id' => $this->admin->id, 'status' => 1]);
        $response = $this->actingAs($this->manager)->get(route('admin.templates.create'))->assertOk();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $options = $xpath->query('//select[@id="project_id"]/option');

        $this->assertSame(2, $options->length);
        $this->assertSame('0', $options->item(0)->getAttribute('value'));
        $this->assertTrue($options->item(0)->hasAttribute('selected'));
        $this->assertSame((string) $project->id, $options->item(1)->getAttribute('value'));
        $this->assertSame(0, $xpath->query('//select[@id="project_id"]/option[@value=""]')->length);
        $this->assertSame(0, $xpath->query('//select[@id="project_id"]/option[@value="'.$foreign->id.'"]')->length);
    }

    public function test_manager_can_edit_a_default_template_but_cannot_reassign_its_project(): void
    {
        $template = Templates::query()->create([
            'project_id' => Project::DEFAULT_ID, 'name' => 'Shared template', 'body' => '<p>Hello</p>', 'prior' => 0,
        ]);
        $project = Project::query()->create(['name' => 'Managed project', 'owner_id' => $this->manager->id, 'status' => 1]);
        $payload = ['id' => $template->id, 'name' => 'Updated default template', 'body' => '<p>Updated</p>', 'prior' => 1];

        $this->actingAs($this->manager)->get(route('admin.templates.edit', $template->id))->assertOk();
        $this->put(route('admin.templates.update'), $payload + ['project_id' => Project::DEFAULT_ID])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseHas('templates', ['id' => $template->id, 'project_id' => Project::DEFAULT_ID, 'name' => $payload['name']]);

        $this->put(route('admin.templates.update'), $payload + ['project_id' => $project->id])
            ->assertSessionHasErrors('project_id');
        $this->assertSame(Project::DEFAULT_ID, (int) $template->fresh()->project_id);
    }

    public function test_default_access_does_not_allow_a_manager_to_create_templates_in_other_projects(): void
    {
        $foreign = Project::query()->create(['name' => 'Private project', 'owner_id' => $this->admin->id, 'status' => 1]);

        foreach ([$foreign->id, -1, [], 'invalid'] as $projectId) {
            $this->actingAs($this->manager)->post(route('admin.templates.store'), [
                'project_id' => $projectId, 'name' => 'Forbidden template', 'body' => '<p>Hello</p>', 'prior' => 0,
            ])->assertSessionHasErrors('project_id');
        }

        $this->assertDatabaseMissing('templates', ['name' => 'Forbidden template']);
    }

    private function user(string $role): User
    {
        return User::query()->create(['name' => $role, 'login' => $role, 'role' => $role, 'password' => 'password']);
    }
}
