<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Logs;
use App\Models\Project;
use App\Models\ReadySent;
use App\Models\Schedule;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use App\Models\Templates;
use App\Models\User;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use App\Services\SendMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultProjectIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;
    private Project $defaultProject;
    private Project $otherProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = $this->user('default-integration-admin', User::ROLE_ADMIN);
        $this->defaultProject = Project::defaultProject();
        $this->assertSame(0, $this->defaultProject->id);
        $this->otherProject = Project::query()->create([
            'name' => 'Another project', 'owner_id' => $this->administrator->id, 'status' => 1,
        ]);
    }

    public function test_public_subscription_accepts_explicit_zero_and_keeps_categories_scoped(): void
    {
        $category = Category::query()->create(['project_id' => 0, 'name' => 'Default readers']);
        $foreignCategory = Category::query()->create(['project_id' => $this->otherProject->id, 'name' => 'Other readers']);
        $this->mock(SendMailService::class)->shouldReceive('sendFrontendSubscriberEmails')->twice();

        $this->get(route('frontend.form', ['project_id' => 0]))->assertOk()
            ->assertSee('name="project_id" value="0"', false)->assertSee($category->name)->assertDontSee($foreignCategory->name);
        $this->getJson(route('frontend.categories', ['project_id' => 0]))->assertOk()
            ->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $category->id);
        $payload = ['project_id' => 0, 'email' => 'default-public@example.test', 'categoryId' => [$category->id]];
        $this->postJson(route('frontend.addsub'), $payload)->assertOk()->assertJsonPath('result', 'success');
        $subscriber = Subscribers::query()->where('email', $payload['email'])->sole();
        $this->assertDatabaseHas('project_subscriber', ['project_id' => 0, 'subscriber_id' => $subscriber->id]);
        $this->assertDatabaseHas('subscriptions', ['category_id' => $category->id, 'subscriber_id' => $subscriber->id]);

        $this->postJson(route('frontend.addsub'), [
            'project_id' => 0, 'email' => 'wrong-category@example.test', 'categoryId' => [$foreignCategory->id],
        ])->assertStatus(422)->assertJsonValidationErrors('categoryId.0');
        $this->postJson(route('frontend.addsub'), ['email' => 'missing-project@example.test'])
            ->assertOk()->assertJsonPath('result', 'success');
        $defaultSubscriber = Subscribers::query()->where('email', 'missing-project@example.test')->sole();
        $this->assertSame([Project::DEFAULT_ID], $defaultSubscriber->projects()->pluck('projects.id')->all());
    }

    public function test_default_project_tracking_accepts_zero_without_bypassing_membership_checks(): void
    {
        $subscriber = $this->subscriberFixture([
            'email' => 'default-tracking@example.test', 'active' => 1, 'token' => str_repeat('a', 32),
        ], [0, $this->otherProject->id]);
        $template = Templates::query()->create(['project_id' => 0, 'name' => 'Default newsletter', 'body' => 'Hello', 'prior' => 0]);
        $log = Logs::query()->create(['time' => now(), 'user_id' => $this->administrator->id]);
        $delivery = ReadySent::query()->create([
            'project_id' => 0, 'subscriber_id' => $subscriber->id, 'email' => $subscriber->email,
            'template_id' => $template->id, 'template' => $template->name, 'success' => 1, 'readMail' => 0, 'log_id' => $log->id,
        ]);
        $url = 'https://example.test/default-project';
        $referral = ['subscriber' => $subscriber->id, 'ref' => rtrim(strtr(base64_encode($url), '+/', '-_'), '=')];

        $this->get(route('frontend.referral', $referral + ['project_id' => 0]))->assertRedirect($url);
        $this->assertDatabaseHas('redirect', ['project_id' => 0, 'email' => $subscriber->email, 'url' => $url]);
        $this->get(route('frontend.referral', $referral + ['project_id' => -1]))->assertNotFound();
        $this->get(route('frontend.referral', $referral))->assertNotFound();
        $this->get(route('frontend.pic', ['subscriber' => $subscriber->id, 'template' => $template->id]))->assertOk();
        $this->assertEquals(1, $delivery->fresh()->readMail);

        $moderator = $this->user('default-report-moderator', User::ROLE_MODERATOR);
        $this->actingAs($moderator)->getJson(route('admin.datatable.info_log', $log->id))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', $subscriber->email);
        $this->postJson(route('admin.log.clear'))->assertForbidden();
        $this->postJson(route('admin.redirect.clear'))->assertForbidden();

        auth()->logout();
        $subscriber->projects()->detach(0);
        $this->get(route('frontend.referral', $referral + ['project_id' => 0]))->assertNotFound();
        $this->get(route('frontend.pic', ['subscriber' => $subscriber->id, 'template' => $template->id]))->assertNotFound();
    }

    public function test_schedule_and_console_recipient_queries_keep_the_zero_project_boundary(): void
    {
        $manager = $this->user('default-schedule-manager', User::ROLE_PROJECT_ADMIN);
        $category = Category::query()->create(['project_id' => 0, 'name' => 'Default schedule readers']);
        $foreignCategory = Category::query()->create(['project_id' => $this->otherProject->id, 'name' => 'Foreign schedule readers']);
        $template = Templates::query()->create(['project_id' => 0, 'name' => 'Default schedule template', 'body' => 'Hello', 'prior' => 0]);
        $subscriber = $this->subscriberFixture(['email' => 'default-recipient@example.test', 'active' => 1, 'token' => str_repeat('b', 32)], [0]);
        $foreignSubscriber = $this->subscriberFixture(['email' => 'foreign-recipient@example.test', 'active' => 1, 'token' => str_repeat('c', 32)], [$this->otherProject->id]);
        foreach ([$subscriber, $foreignSubscriber] as $recipient) {
            Subscriptions::query()->create(['subscriber_id' => $recipient->id, 'category_id' => $category->id]);
        }

        $page = $this->actingAs($manager)->get(route('admin.schedule.create'))->assertOk();
        $this->assertEquals(0, $page->viewData('templateProjects')->get($template->id));
        $this->assertArrayHasKey($category->id, $page->viewData('category_options'));
        $this->assertArrayNotHasKey($foreignCategory->id, $page->viewData('category_options'));
        $payload = [
            'event_name' => 'Default project mailing', 'template_id' => $template->id,
            'categoryId' => [$foreignCategory->id],
            'date_interval' => now()->addDays(3)->format('d.m.Y H:i').' - '.now()->addDays(3)->addHour()->format('d.m.Y H:i'),
        ];
        $this->post(route('admin.schedule.store'), $payload)->assertSessionHasErrors('categoryId.0');
        $payload['categoryId'] = [$category->id];
        $this->post(route('admin.schedule.store'), $payload)->assertSessionHasNoErrors()->assertSessionMissing('error')
            ->assertRedirect(route('admin.schedule.index'));
        $schedule = Schedule::query()->sole();
        $this->assertEquals(0, $schedule->project_id);
        $schedule->update(['event_start' => now()->subHour(), 'event_end' => now()->addHour()]);
        $this->assertSame(1, app(SubscriberRepository::class)->countSubscriptions([$category->id], null, null, 0));

        $this->app['auth']->forgetGuards();
        $this->assertTrue(app(ScheduleRepository::class)->getScheduleEvent()->contains('id', $schedule->id));
        $this->assertSame([$subscriber->id], app(SubscriberRepository::class)->getSubscribersNotReadySent($schedule->id, 'subscribers.id')->pluck('id')->all());
    }

    public function test_subscription_form_defaults_to_common_project_and_orphan_category_label_stays_unassigned(): void
    {
        $moderator = $this->user('default-form-moderator', User::ROLE_MODERATOR);
        $this->otherProject->members()->attach($moderator, ['role' => User::ROLE_MODERATOR]);
        $page = $this->actingAs($moderator)->get(route('admin.pages.subscription_form'))->assertOk();
        $this->assertSame(0, $page->viewData('project')->id);
        $this->assertSame(0, $page->viewData('projects')->first()->id);

        $orphan = Category::query()->create(['project_id' => null, 'name' => 'Category without a project']);
        $page = $this->actingAs($this->administrator)->get(route('admin.category.edit', $orphan->id))->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$page->getContent());
        $this->assertSame(__('frontend.str.projects.subscriber_unassigned'), $document->getElementById('project_id')->getAttribute('value'));
    }

    private function user(string $login, string $role): User
    {
        return User::query()->create(['name' => $login, 'login' => $login, 'role' => $role, 'password' => 'password']);
    }
}
