<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Services\SendMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSubscriptionCorsTest extends TestCase
{
    use RefreshDatabase;

    private const EXTERNAL_ORIGIN = 'http://site1.local';

    public function test_external_form_can_read_only_default_project_categories_without_credentials(): void
    {
        $category = Category::query()->create(['project_id' => Project::DEFAULT_ID, 'name' => 'Common readers']);
        Category::query()->create(['project_id' => $this->testProjectId(), 'name' => 'Another project']);
        Category::query()->create(['project_id' => null, 'name' => 'Unassigned readers']);

        $this->getJson(route('frontend.categories'), ['Origin' => self::EXTERNAL_ORIGIN])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeaderMissing('Access-Control-Allow-Credentials')
            ->assertExactJson(['items' => [['id' => $category->id, 'name' => $category->name]]]);
    }

    public function test_external_form_can_read_validation_errors_without_creating_subscribers_or_sending_mail(): void
    {
        $this->mock(SendMailService::class)->shouldNotReceive('sendFrontendSubscriberEmails');

        $this->post(route('frontend.addsub'), [
            'project_id' => Project::DEFAULT_ID,
            'name' => 'Invalid subscriber',
            'email' => 'invalid-email',
        ], ['Origin' => self::EXTERNAL_ORIGIN, 'Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeaderMissing('Access-Control-Allow-Credentials')
            ->assertJsonPath('result', 'errors')
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('subscribers', 0);
        $this->assertDatabaseCount('project_subscriber', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_external_subscription_post_preflight_is_allowed(): void
    {
        $this->options(route('frontend.addsub'), [], [
            'Origin' => self::EXTERNAL_ORIGIN,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeader('Access-Control-Allow-Methods', 'POST')
            ->assertHeader('Access-Control-Allow-Headers', 'content-type')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_login_does_not_expose_cross_origin_responses(): void
    {
        $this->get(route('login'), ['Origin' => self::EXTERNAL_ORIGIN])
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
