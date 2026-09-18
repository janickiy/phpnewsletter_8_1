<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\Subscribers;
use App\Models\User;
use App\Services\SendMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VirtualProjectPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_subscription_works_without_any_stored_project(): void
    {
        $category = Category::query()->create(['project_id' => 0, 'name' => 'Common readers']);
        $this->assertDatabaseCount('projects', 0);
        $this->mock(SendMailService::class)->shouldReceive('sendFrontendSubscriberEmails')->once();

        $this->get(route('frontend.form', ['project_id' => 0]))->assertOk()
            ->assertSee('name="project_id" value="0"', false)->assertSee($category->name);
        $this->getJson(route('frontend.categories', ['project_id' => 0]))->assertOk()
            ->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $category->id);

        $this->postJson(route('frontend.addsub'), [
            'project_id' => 0, 'email' => 'virtual-main@example.test', 'categoryId' => [$category->id],
        ])->assertOk()->assertJsonPath('result', 'success');

        $subscriber = Subscribers::query()->sole();
        $this->assertDatabaseHas('project_subscriber', ['project_id' => 0, 'subscriber_id' => $subscriber->id]);
        $this->assertDatabaseHas('subscriptions', ['category_id' => $category->id, 'subscriber_id' => $subscriber->id]);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_virtual_default_does_not_allow_invalid_or_inactive_projects_or_foreign_categories(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner', 'login' => 'virtual-public-owner', 'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $inactiveProject = Project::query()->create([
            'name' => 'Inactive project', 'owner_id' => $owner->id, 'status' => false,
        ]);
        $foreignCategory = Category::query()->create(['project_id' => $inactiveProject->id, 'name' => 'Private readers']);
        $this->mock(SendMailService::class)->shouldNotReceive('sendFrontendSubscriberEmails');

        foreach ([null, -1, 999999, $inactiveProject->id] as $projectId) {
            $this->postJson(route('frontend.addsub'), [
                'project_id' => $projectId, 'email' => 'invalid-virtual@example.test',
            ])->assertUnprocessable()->assertJsonValidationErrors('project_id');
        }

        $this->get(route('frontend.form', ['project_id' => $inactiveProject->id]))->assertNotFound();
        $this->getJson(route('frontend.categories', ['project_id' => $inactiveProject->id]))->assertNotFound();
        $this->postJson(route('frontend.addsub'), [
            'project_id' => 0, 'email' => 'foreign-virtual@example.test', 'categoryId' => [$foreignCategory->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('categoryId.0');

        $this->assertDatabaseMissing('projects', ['id' => 0]);
        $this->assertDatabaseCount('subscribers', 0);
        $this->assertDatabaseCount('project_subscriber', 0);
    }
}
