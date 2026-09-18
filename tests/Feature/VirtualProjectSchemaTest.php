<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VirtualProjectSchemaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Subscribers $subscriber;
    private array $templates;

    protected function setUp(): void
    {
        parent::setUp();
        $owner = User::query()->create([
            'name' => 'Schema administrator', 'login' => 'schema-admin',
            'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $this->project = Project::query()->create(['name' => 'Stored project', 'owner_id' => $owner->id, 'status' => 1]);
        $this->subscriber = $this->subscriberFixture(['email' => 'schema@example.test', 'token' => 'schema-token']);

        foreach ([Project::DEFAULT_ID, $this->project->id] as $projectId) {
            $this->templates[$projectId] = Templates::query()->create([
                'project_id' => $projectId, 'name' => 'Fixture template '.$projectId, 'body' => 'Hello', 'prior' => 0,
            ]);
        }
    }

    #[DataProvider('projectTables')]
    public function test_project_references_allow_the_virtual_default_and_existing_projects(string $table): void
    {
        foreach ([Project::DEFAULT_ID, $this->project->id] as $projectId) {
            $attributes = $this->attributes($table, $projectId);
            DB::table($table)->insert($attributes);

            $this->assertDatabaseHas($table, [
                ...$attributes,
                'project_reference_id' => $projectId === Project::DEFAULT_ID ? null : $projectId,
            ]);
        }

        $this->assertDatabaseMissing('projects', ['id' => Project::DEFAULT_ID]);
    }

    #[DataProvider('projectTables')]
    public function test_project_references_reject_nonexistent_projects_on_insert_and_update(string $table): void
    {
        $missingId = $this->project->id + 1000;
        $before = DB::table($table)->count();
        $this->assertForeignKeyRejects(fn () => DB::table($table)->insert($this->attributes($table, $missingId)));
        $this->assertSame($before, DB::table($table)->count());

        $attributes = $this->attributes($table, Project::DEFAULT_ID);
        DB::table($table)->insert($attributes);
        $this->assertForeignKeyRejects(fn () => DB::table($table)->where($attributes)->update(['project_id' => $missingId]));
        $this->assertDatabaseHas($table, [...$attributes, 'project_reference_id' => null]);
        $this->assertDatabaseMissing($table, ['project_id' => $missingId]);
    }

    public static function projectTables(): array
    {
        return array_combine(
            $tables = ['templates', 'categories', 'schedule', 'ready_sent', 'redirect', 'project_subscriber'],
            array_map(static fn (string $table) => [$table], $tables),
        );
    }

    private function attributes(string $table, int $projectId): array
    {
        $template = $this->templates[$projectId] ?? $this->templates[Project::DEFAULT_ID];

        return ['project_id' => $projectId, ...match ($table) {
            'templates' => ['name' => 'Schema template '.$projectId, 'body' => 'Hello', 'prior' => 0],
            'categories' => ['name' => 'Schema category '.$projectId],
            'schedule' => [
                'event_name' => 'Schema mailing '.$projectId, 'template_id' => $template->id,
                'event_start' => '2026-01-01 12:00:00', 'event_end' => '2026-01-01 13:00:00',
            ],
            'ready_sent' => [
                'subscriber_id' => $this->subscriber->id, 'email' => $this->subscriber->email,
                'template_id' => $template->id, 'template' => $template->name, 'success' => 1,
            ],
            'redirect' => ['url' => 'https://example.test/schema', 'email' => $this->subscriber->email],
            'project_subscriber' => ['subscriber_id' => $this->subscriber->id],
        }];
    }

    private function assertForeignKeyRejects(callable $operation): void
    {
        try {
            $operation();
            $this->fail('A nonexistent positive project ID must violate the foreign key.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
            $this->assertSame(1452, $exception->errorInfo[1]);
        }
    }
}
