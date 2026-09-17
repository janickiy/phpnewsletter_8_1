<?php

namespace Tests\Feature;

use App\Models\Subscribers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberLifecycleLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_mixed_case_token_can_unsubscribe_a_subscriber(): void
    {
        $subscriber = $this->createSubscriber('HMCOKYgcSZ0Rm2P0vG4TMkIjjgA1kAZT');

        $response = $this->get(route('frontend.unsubscribe', [
            'subscriber' => $subscriber->id,
            'token' => $subscriber->token,
        ]));

        $response
            ->assertOk()
            ->assertSee('class="unsubscribe-page"', false)
            ->assertSee('class="unsubscribe-message error-box"', false);

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscriber->id,
            'active' => 0,
        ]);
    }

    public function test_mixed_case_token_can_confirm_a_subscriber(): void
    {
        $subscriber = $this->createSubscriber('AbCdEf0123456789AbCdEf0123456789', false);

        $response = $this->get(route('frontend.subscribe', [
            'subscriber' => $subscriber->id,
            'token' => $subscriber->token,
        ]));

        $response
            ->assertOk()
            ->assertSee('class="subscribe-page"', false)
            ->assertSee('class="subscribe-message error-box"', false);

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscriber->id,
            'active' => 1,
        ]);
    }

    public function test_invalid_token_still_returns_not_found(): void
    {
        $subscriber = $this->createSubscriber('HMCOKYgcSZ0Rm2P0vG4TMkIjjgA1kAZT');

        $this->get(route('frontend.unsubscribe', [
            'subscriber' => $subscriber->id,
            'token' => 'XMCOKYgcSZ0Rm2P0vG4TMkIjjgA1kAZT',
        ]))->assertNotFound();

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscriber->id,
            'active' => 1,
        ]);
    }

    private function createSubscriber(string $token, bool $active = true): Subscribers
    {
        return $this->subscriberFixture(['name' => 'Lifecycle subscriber',
            'email' => strtolower($token) . '@example.test',
            'active' => $active ? 1 : 0,
            'token' => $token,
        ], [$this->testProjectId()]);
    }
}
