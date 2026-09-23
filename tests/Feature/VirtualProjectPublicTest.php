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
        $this->mock(SendMailService::class)->shouldReceive('sendFrontendSubscriberEmails')->times(5);

        $this->get(route('frontend.form', ['project_id' => 0]))->assertOk()
            ->assertSee('name="project_id" value="0"', false)->assertSee($category->name);
        $this->getJson(route('frontend.categories', ['project_id' => 0]))->assertOk()
            ->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $category->id);

        foreach ([[], ['project_id' => null], ['project_id' => ''], ['project_id' => 0], ['project_id' => '0']] as $index => $selection) {
            $email = 'virtual-main-'.$index.'@example.test';
            $this->postJson(route('frontend.addsub'), [
                ...$selection, 'email' => $email, 'categoryId' => [$category->id],
            ])->assertOk()->assertJsonPath('result', 'success');

            $subscriber = Subscribers::query()->where('email', $email)->sole();
            $this->assertSame([Project::DEFAULT_ID], $subscriber->projects()->pluck('projects.id')->all());
            $this->assertDatabaseHas('subscriptions', ['category_id' => $category->id, 'subscriber_id' => $subscriber->id]);
        }

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_categories_are_identical_with_or_without_a_project_parameter(): void
    {
        $defaultCategory = Category::query()->create(['project_id' => Project::DEFAULT_ID, 'name' => 'Common readers']);
        $otherProjectId = $this->testProjectId();
        $otherCategory = Category::query()->create(['project_id' => $otherProjectId, 'name' => 'Project readers']);
        Category::query()->create(['project_id' => null, 'name' => 'Unassigned readers']);

        $defaultResponse = $this->getJson(route('frontend.categories'))->assertOk()
            ->assertJsonCount(3, 'items')->assertJsonPath('items.0.id', $defaultCategory->id);
        $explicitResponse = $this->getJson(route('frontend.categories', ['project_id' => Project::DEFAULT_ID]))->assertOk();
        $this->assertSame($explicitResponse->json(), $defaultResponse->json());
        $this->getJson(route('frontend.categories', ['project_id' => $otherProjectId]))->assertOk()
            ->assertJsonCount(3, 'items')->assertExactJson($defaultResponse->json());
    }

    public function test_subscription_without_a_project_keeps_category_and_duplicate_membership_validation(): void
    {
        $otherProjectId = $this->testProjectId();
        $otherCategory = Category::query()->create(['project_id' => $otherProjectId, 'name' => 'Project readers']);
        $this->mock(SendMailService::class)->shouldReceive('sendFrontendSubscriberEmails')->once();

        $this->postJson(route('frontend.addsub'), [
            'email' => 'missing-category@example.test', 'categoryId' => [999999],
        ])->assertUnprocessable()->assertJsonValidationErrors('categoryId.0');
        $this->assertDatabaseMissing('subscribers', ['email' => 'missing-category@example.test']);

        $email = 'explicit-project@example.test';
        $this->postJson(route('frontend.addsub'), [
            'project_id' => $otherProjectId, 'email' => $email, 'categoryId' => [$otherCategory->id],
        ])->assertOk()->assertJsonPath('result', 'success');
        $subscriber = Subscribers::query()->where('email', $email)->sole();
        $this->assertSame([$otherProjectId], $subscriber->projects()->pluck('projects.id')->all());

        $this->postJson(route('frontend.addsub'), ['email' => $email])->assertOk()->assertJsonPath('result', 'success');
        $this->assertEqualsCanonicalizing([Project::DEFAULT_ID, $otherProjectId], $subscriber->projects()->pluck('projects.id')->all());
        $this->assertDatabaseHas('subscriptions', ['category_id' => $otherCategory->id, 'subscriber_id' => $subscriber->id]);
        $this->postJson(route('frontend.addsub'), ['email' => $email])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('subscribers', 1);
    }

    public function test_subscription_embed_uses_a_clean_default_categories_url_and_preserves_other_project_ids(): void
    {
        $project = Project::query()->findOrFail($this->testProjectId());
        $this->actingAs($project->owner);

        $defaultPage = $this->get(route('admin.pages.subscription_form'))->assertOk();
        $defaultCode = $defaultPage->viewData('embedCode');
        $this->assertStringContainsString('url: "'.route('frontend.categories').'"', $defaultCode);
        $this->assertStringNotContainsString(route('frontend.categories', ['project_id' => Project::DEFAULT_ID]), $defaultCode);

        $projectPage = $this->get(route('admin.pages.subscription_form', ['project_id' => $project->id]))->assertOk();
        $this->assertStringContainsString(
            'url: "'.route('frontend.categories', ['project_id' => $project->id]).'"',
            $projectPage->viewData('embedCode')
        );
    }

    public function test_virtual_default_does_not_allow_invalid_or_inactive_projects_or_missing_categories(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner', 'login' => 'virtual-public-owner', 'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $inactiveProject = Project::query()->create([
            'name' => 'Inactive project', 'owner_id' => $owner->id, 'status' => false,
        ]);
        $foreignCategory = Category::query()->create(['project_id' => $inactiveProject->id, 'name' => 'Private readers']);
        $this->mock(SendMailService::class)->shouldNotReceive('sendFrontendSubscriberEmails');

        foreach ([-1, 999999, $inactiveProject->id] as $projectId) {
            $this->postJson(route('frontend.addsub'), [
                'project_id' => $projectId, 'email' => 'invalid-virtual@example.test',
            ])->assertUnprocessable()->assertJsonValidationErrors('project_id');
        }

        $this->get(route('frontend.form', ['project_id' => $inactiveProject->id]))->assertNotFound();
        $this->getJson(route('frontend.categories', ['project_id' => $inactiveProject->id]))->assertNotFound();
        $this->postJson(route('frontend.addsub'), [
            'project_id' => 0, 'email' => 'missing-virtual@example.test', 'categoryId' => [999999],
        ])->assertUnprocessable()->assertJsonValidationErrors('categoryId.0');

        $this->assertDatabaseMissing('projects', ['id' => 0]);
        $this->assertDatabaseCount('subscribers', 0);
        $this->assertDatabaseCount('project_subscriber', 0);
    }
}
