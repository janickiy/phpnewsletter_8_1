<?php

namespace Tests\Feature;

use App\Models\{Category, Project, ReadySent, Schedule, ScheduleCategory, Subscribers, Subscriptions, Templates, User};
use App\Repositories\SubscriberRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduleOptionalCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Project $project;
    private Project $foreignProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::query()->create([
            'name' => 'Schedule manager', 'login' => 'schedule-manager',
            'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'test-password',
        ]);
        $otherManager = User::query()->create([
            'name' => 'Other manager', 'login' => 'other-manager',
            'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'test-password',
        ]);
        $this->project = Project::query()->create([
            'name' => 'Project without categories', 'status' => 1, 'owner_id' => $this->manager->id,
        ]);
        $this->foreignProject = Project::query()->create([
            'name' => 'Other project', 'status' => 1, 'owner_id' => $otherManager->id,
        ]);
        $this->actingAs($this->manager);
    }

    #[DataProvider('emptyCategorySelections')]
    public function test_named_project_schedule_can_be_created_without_categories(array $categoryInput): void
    {
        $template = $this->template($this->project->id);

        $this->post(route('admin.schedule.store'), $this->payload($template) + $categoryInput)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.schedule.index'));

        $this->assertDatabaseHas('schedule', [
            'event_name' => 'Optional categories schedule',
            'template_id' => $template->id,
            'project_id' => $template->project_id,
        ]);
        $this->assertDatabaseCount('schedule_category', 0);
    }

    #[DataProvider('emptyCategorySelections')]
    public function test_editing_a_named_project_schedule_can_clear_legacy_categories(array $categoryInput): void
    {
        $template = $this->template($this->project->id);
        $schedule = $this->schedule($template);
        $category = Category::query()->create(['name' => 'Existing category', 'project_id' => $template->project_id]);
        ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $category->id]);

        $this->put(route('admin.schedule.update'), $this->payload($template) + ['id' => $schedule->id] + $categoryInput)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.schedule.index'));

        $this->assertSame('Optional categories schedule', $schedule->fresh()->event_name);
        $this->assertDatabaseMissing('schedule_category', ['schedule_id' => $schedule->id]);
        $this->get(route('admin.schedule.edit', $schedule->id))
            ->assertOk()
            ->assertViewHas('categoryId', fn ($ids) => count($ids) === 0);
    }

    #[DataProvider('emptyCategorySelections')]
    public function test_default_project_requires_categories_when_creating_and_editing(array $categoryInput): void
    {
        $template = $this->template(Project::DEFAULT_ID);
        $schedule = $this->schedule($template);
        $category = Category::query()->create(['name' => 'Default category', 'project_id' => Project::DEFAULT_ID]);
        ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $category->id]);
        $before = $schedule->fresh()->getAttributes();

        $this->post(route('admin.schedule.store'), $this->payload($template) + $categoryInput)
            ->assertSessionHasErrors('categoryId');
        $this->put(route('admin.schedule.update'), $this->payload($template) + ['id' => $schedule->id] + $categoryInput)
            ->assertSessionHasErrors('categoryId');

        $this->assertDatabaseCount('schedule', 1);
        $this->assertSame($before, $schedule->fresh()->getAttributes());
        $this->assertDatabaseHas('schedule_category', ['schedule_id' => $schedule->id, 'category_id' => $category->id]);
    }

    public function test_editing_can_switch_to_a_template_in_a_project_without_categories(): void
    {
        $schedule = $this->schedule($this->template(Project::DEFAULT_ID));
        $category = Category::query()->create(['name' => 'Default category', 'project_id' => Project::DEFAULT_ID]);
        ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $category->id]);
        $template = $this->template($this->project->id);

        $this->put(route('admin.schedule.update'), $this->payload($template) + ['id' => $schedule->id])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('schedule', ['id' => $schedule->id, 'project_id' => $this->project->id, 'template_id' => $template->id]);
        $this->assertDatabaseMissing('schedule_category', ['schedule_id' => $schedule->id]);
    }

    public function test_named_project_rejects_category_selection_and_inaccessible_templates(): void
    {
        $template = $this->template($this->project->id);
        $foreignTemplate = $this->template($this->foreignProject->id);
        $foreignCategory = Category::query()->create(['name' => 'Foreign category', 'project_id' => $this->foreignProject->id]);
        $schedule = $this->schedule($template);
        $before = $schedule->fresh()->getAttributes();

        $this->put(route('admin.schedule.update'), $this->payload($template) + [
            'id' => $schedule->id, 'categoryId' => [$foreignCategory->id],
        ])->assertSessionHasErrors('categoryId');
        $this->post(route('admin.schedule.store'), $this->payload($foreignTemplate))->assertSessionHasErrors('template_id');
        $this->put(route('admin.schedule.update'), $this->payload($foreignTemplate) + ['id' => $schedule->id])
            ->assertSessionHasErrors('template_id');

        $this->assertSame($before, $schedule->fresh()->getAttributes());
        $this->assertDatabaseCount('schedule', 1);
    }

    public function test_default_schedule_can_create_and_edit_with_any_global_category(): void
    {
        $template = $this->template(Project::DEFAULT_ID);
        $first = Category::query()->create(['name' => 'Global news']);
        $second = Category::query()->create(['name' => 'Global offers']);
        $this->get(route('admin.schedule.create'))->assertOk()->assertViewHas('category_options', [
            $first->id => $first->name, $second->id => $second->name,
        ]);

        $this->post(route('admin.schedule.store'), $this->payload($template) + ['categoryId' => [$first->id]])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $schedule = Schedule::query()->sole();
        $this->assertDatabaseHas('schedule_category', ['schedule_id' => $schedule->id, 'category_id' => $first->id]);

        $this->put(route('admin.schedule.update'), $this->payload($template) + [
            'id' => $schedule->id, 'categoryId' => [$second->id],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseMissing('schedule_category', ['schedule_id' => $schedule->id, 'category_id' => $first->id]);
        $this->assertDatabaseHas('schedule_category', ['schedule_id' => $schedule->id, 'category_id' => $second->id]);
    }

    public function test_named_project_targets_only_active_members_for_delivery_and_retries(): void
    {
        $projectId = $this->project->id;
        $schedule = $this->schedule($this->template($projectId));
        $uncategorized = $this->subscriber($projectId, 'uncategorized');
        $categorized = $this->subscriber($projectId, 'categorized');
        // Multiple project/category memberships must not duplicate a recipient.
        $categorized->projects()->attach($this->foreignProject->id);
        foreach (['First', 'Second'] as $name) {
            $category = Category::query()->create(['name' => $name, 'project_id' => $projectId]);
            Subscriptions::query()->create(['subscriber_id' => $categorized->id, 'category_id' => $category->id]);
        }
        $sent = $this->subscriber($projectId, 'sent');
        $failed = $this->subscriber($projectId, 'failed');
        $recentFailed = $this->subscriber($projectId, 'recent-failed');
        $inactive = $this->subscriber($projectId, 'inactive', ['active' => 0]);
        $foreign = $this->subscriber($this->foreignProject->id, 'foreign');
        $outsider = $this->subscriber(Project::DEFAULT_ID, 'outsider');
        $this->subscriber($projectId, 'inactive-unprocessed', ['active' => 0]);
        $this->subscriber($this->foreignProject->id, 'foreign-unprocessed');
        $this->subscriber(Project::DEFAULT_ID, 'outsider-unprocessed');
        $this->recordAttempt($schedule, $sent, 1);
        foreach ([$failed, $recentFailed, $inactive, $foreign, $outsider] as $recipient) {
            $this->recordAttempt($schedule, $recipient, 0);
        }
        $categorized->update(['timeSent' => now()]);
        $recentFailed->update(['timeSent' => now()]);
        $this->app['auth']->forgetGuards();
        $repository = app(SubscriberRepository::class);

        $this->assertSame([$uncategorized->id, $categorized->id], $repository->getSubscribersNotReadySent($schedule->id, 'subscribers.id')->pluck('id')->all());
        $this->assertSame([$failed->id, $recentFailed->id], $repository->getSubscribersUnSent($schedule->id, 'subscribers.id')->pluck('id')->all());
        $this->assertSame([$categorized->id], $repository->getSubscribersNotReadySent($schedule->id, 'subscribers.id DESC', 1)->pluck('id')->all());
        $this->assertSame([$recentFailed->id], $repository->getSubscribersUnSent($schedule->id, 'subscribers.id DESC', 1)->pluck('id')->all());
        $interval = "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '2' HOUR)";
        $this->assertSame([$uncategorized->id], $repository->getSubscribersNotReadySent($schedule->id, 'subscribers.id', null, $interval)->pluck('id')->all());
        $this->assertSame([$failed->id], $repository->getSubscribersUnSent($schedule->id, 'subscribers.id', null, $interval)->pluck('id')->all());
        $this->assertCount(0, $repository->getSubscribersNotReadySent($schedule->id + 100, 'subscribers.id'));
        $this->assertCount(0, $repository->getSubscribersUnSent($schedule->id + 100, 'subscribers.id'));
    }

    #[DataProvider('projectTypes')]
    public function test_categories_filter_the_default_project_but_do_not_restrict_named_projects(bool $defaultProject): void
    {
        $projectId = $defaultProject ? Project::DEFAULT_ID : $this->project->id;
        $schedule = $this->schedule($this->template($projectId));
        $category = Category::query()->create(['name' => 'Selected category', 'project_id' => $projectId]);
        ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $category->id]);
        $selected = $this->subscriber($projectId, 'selected');
        $uncategorized = $this->subscriber($projectId, 'uncategorized');
        $foreign = $this->subscriber($this->foreignProject->id, 'foreign');
        foreach ([$selected, $foreign] as $recipient) {
            Subscriptions::query()->create(['subscriber_id' => $recipient->id, 'category_id' => $category->id]);
        }
        $this->app['auth']->forgetGuards();
        $repository = app(SubscriberRepository::class);

        $expected = $defaultProject ? [$selected->id] : [$selected->id, $uncategorized->id];
        $this->assertSame($expected, $repository->getSubscribersNotReadySent($schedule->id, 'subscribers.id')->pluck('id')->all());
        foreach ([$selected, $uncategorized, $foreign] as $recipient) {
            $this->recordAttempt($schedule, $recipient, 0);
        }
        $this->assertCount(0, $repository->getSubscribersNotReadySent($schedule->id, 'subscribers.id'));
        $this->assertSame($expected, $repository->getSubscribersUnSent($schedule->id, 'subscribers.id')->pluck('id')->all());
    }

    public static function projectTypes(): array
    {
        return ['named project' => [false], 'default project' => [true]];
    }

    public static function emptyCategorySelections(): array
    {
        return [
            'field omitted' => [[]],
            'empty array' => [['categoryId' => []]],
            'null' => [['categoryId' => null]],
        ];
    }

    private function template(int $projectId): Templates
    {
        return Templates::query()->create([
            'name' => 'Test template', 'body' => '<p>Hello</p>', 'prior' => 0, 'project_id' => $projectId,
        ]);
    }

    private function schedule(Templates $template): Schedule
    {
        return Schedule::query()->create([
            'event_name' => 'Original schedule', 'template_id' => $template->id, 'project_id' => $template->project_id,
            'event_start' => now()->subHour(), 'event_end' => now()->addHour(),
        ]);
    }

    private function payload(Templates $template): array
    {
        return [
            'event_name' => 'Optional categories schedule', 'template_id' => $template->id,
            'date_interval' => now()->addDays(3)->format('d.m.Y H:i').' - '.now()->addDays(3)->addHour()->format('d.m.Y H:i'),
        ];
    }

    private function subscriber(int $projectId, string $name, array $attributes = []): Subscribers
    {
        return $this->subscriberFixture($attributes + [
            'name' => $name, 'email' => $name.'@example.test', 'token' => md5($name), 'active' => 1,
        ], [$projectId]);
    }

    private function recordAttempt(Schedule $schedule, Subscribers $subscriber, int $success): void
    {
        ReadySent::query()->create([
            'subscriber_id' => $subscriber->id, 'email' => $subscriber->email,
            'template_id' => $schedule->template_id, 'template' => 'Test template',
            'schedule_id' => $schedule->id, 'project_id' => $schedule->project_id, 'success' => $success,
        ]);
    }
}
