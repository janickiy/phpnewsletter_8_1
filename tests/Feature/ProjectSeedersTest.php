<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FakeSubscribersSeeder;
use Database\Seeders\LocalDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_seeds_create_global_categories_without_a_project_record_or_owner(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('categories', 3);

        $this->admin();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('categories', 3);
        $project = Project::defaultProject();
        $this->assertSame(Project::DEFAULT_ID, $project->id);
        $this->assertNull($project->owner_id);
        $this->assertSame(__('frontend.str.projects.default_name'), $project->name);
        $this->assertSame(['Category 1', 'Category 2', 'Category 3'], Category::query()->orderBy('name')->pluck('name')->all());
    }

    public function test_demo_seeds_keep_matching_records_in_other_projects_unchanged(): void
    {
        $owner = $this->admin();
        $other = Project::query()->create(['name' => 'Customer project', 'owner_id' => $owner->id, 'status' => 1]);
        $category = Category::query()->create(['name' => 'Category 1']);
        $template = Templates::query()->create([
            'project_id' => $other->id, 'name' => 'Welcome email for new subscribers', 'body' => 'Customer content', 'prior' => 0,
        ]);
        $subscriber = $this->subscriberFixture([
            'name' => 'Customer subscriber', 'email' => 'demo.subscriber001@phpnewsletter.test',
            'active' => 1, 'token' => 'customer-token',
        ], [$other->id]);
        DB::table('subscriptions')->insert(['category_id' => $category->id, 'subscriber_id' => $subscriber->id]);
        DB::table('redirect')->insert([
            'template_id' => $template->id, 'template' => $template->name,
            'url' => 'https://example.test/start', 'email' => $subscriber->email,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seed(LocalDemoSeeder::class);
        $this->seed(LocalDemoSeeder::class);

        $this->assertSame('Customer content', $template->fresh()->body);
        $this->assertSame('Customer subscriber', $subscriber->fresh()->name);
        $this->assertSame('customer-token', $subscriber->fresh()->token);
        $this->assertDatabaseHas('subscriptions', ['category_id' => $category->id, 'subscriber_id' => $subscriber->id]);
        $this->assertDatabaseHas('redirect', ['template_id' => $template->id, 'template' => $template->name, 'email' => $subscriber->email]);

        $demo = Project::defaultProject();
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseMissing('projects', ['id' => Project::DEFAULT_ID]);
        $this->assertSame(500, $demo->subscribers()->count());
        $this->assertSame(5, $demo->templates()->count());
        $this->assertSame(5, $demo->schedules()->count());
        $this->assertDatabaseCount('categories', 9);
        $this->assertSame(135, DB::table('ready_sent')->where('project_id', $demo->id)->count());
        $this->assertSame(70, DB::table('redirect')->whereIn('template_id', $demo->templates()->select('id'))->count());
        $this->assertAllSubscriptionsBelongToProjectMembers();
    }

    public function test_fake_subscriber_seeds_use_global_categories_and_explicit_default_membership(): void
    {
        $owner = $this->admin();
        foreach (['First', 'Second'] as $name) {
            $project = Project::query()->create(['name' => $name, 'owner_id' => $owner->id, 'status' => 1]);
            Category::query()->create(['name' => 'Category']);
        }

        $this->seed(FakeSubscribersSeeder::class);

        $this->assertDatabaseCount('subscribers', 5000);
        $this->assertSame([Project::DEFAULT_ID], DB::table('project_subscriber')->distinct()->pluck('project_id')->all());
        $this->assertAllSubscriptionsBelongToProjectMembers();
    }

    private function admin(): User
    {
        return User::query()->create(['name' => 'Administrator', 'login' => 'admin', 'role' => 'admin', 'password' => 'secret123']);
    }

    private function assertAllSubscriptionsBelongToProjectMembers(): void
    {
        $this->assertSame(0, DB::table('subscriptions')
            ->join('categories', 'categories.id', '=', 'subscriptions.category_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('project_subscriber')
                    ->whereColumn('project_subscriber.subscriber_id', 'subscriptions.subscriber_id');
            })->count());
    }
}
