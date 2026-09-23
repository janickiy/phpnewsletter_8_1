<?php

namespace Tests\Feature;

use App\Helpers\SendEmailHelper;
use App\Models\{Category, Logs, Project, ReadySent, Subscribers, Subscriptions, Templates, User};
use App\Services\SendMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ManualProjectMailingTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Project $project;
    private Templates $template;
    private ManualProjectRecordingMailer $mailer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::query()->create([
            'name' => 'Mailing manager', 'login' => 'manual-project-manager',
            'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'password',
        ]);
        $this->project = Project::query()->create([
            'name' => 'Project without categories', 'owner_id' => $this->manager->id, 'status' => true,
        ]);
        $this->template = $this->template($this->project->id);
        $this->actingAs($this->manager);
        $this->mailer = new ManualProjectRecordingMailer();
        $service = $this->app->make(ManualProjectSendMailService::class);
        $service->mailer = $this->mailer;
        $this->app->instance(SendMailService::class, $service);
    }

    #[DataProvider('emptyCategories')]
    public function test_named_project_can_start_count_and_send_without_categories(array $categoryInput): void
    {
        $recipient = $this->subscriber('active@example.test', [$this->project->id]);
        $this->subscriber('inactive@example.test', [$this->project->id], false);
        $this->subscriber('default-only@example.test', [Project::DEFAULT_ID]);
        $this->subscriber('orphan@example.test', []);

        $payload = ['templateId' => [$this->template->id]] + $categoryInput;
        $start = $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'start_mailing'])
            ->assertOk()->assertJsonPath('result', true);
        $logId = $start->json('logId');
        $payload['logId'] = $logId;
        $this->assertDatabaseHas('logs', ['id' => $logId, 'user_id' => $this->manager->id]);
        $this->assertDatabaseCount('categories', 0);

        $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'count_send'])
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('success', 0);
        $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'send_out'])
            ->assertOk()->assertJsonPath('result', true)->assertJsonPath('completed', true);
        $this->assertSame([$recipient->email], array_column($this->mailer->deliveries, 'email'));
        $this->assertDatabaseCount('ready_sent', 1);
        $this->assertDatabaseHas('ready_sent', [
            'subscriber_id' => $recipient->id, 'project_id' => $this->project->id,
            'template_id' => $this->template->id, 'log_id' => $logId, 'success' => 1,
        ]);
        $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'count_send'])
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('success', 1)
            ->assertJsonPath('unsuccessful', 0)->assertJsonPath('leftsend', 100);
    }

    #[DataProvider('requiredCategoryRequests')]
    public function test_default_templates_require_categories_before_starting_or_sending(
        string $action,
        bool $mixed,
        array $categoryInput,
    ): void {
        $defaultTemplate = $this->template(Project::DEFAULT_ID);
        $templateIds = $mixed ? [$this->template->id, $defaultTemplate->id] : [$defaultTemplate->id];
        $payload = ['action' => $action, 'templateId' => $templateIds] + $categoryInput;
        if ($action !== 'start_mailing') {
            $payload['logId'] = $this->log()->id;
        }

        $this->postJson(route('admin.ajax.action'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('categoryId');
        $this->assertDatabaseCount('logs', $action === 'start_mailing' ? 0 : 1);
        $this->assertDatabaseCount('ready_sent', 0);
        $this->assertDatabaseCount('process', 0);
        $this->assertSame([], $this->mailer->deliveries);
    }

    public function test_mixed_batch_filters_only_default_templates_by_category_and_preserves_project_boundaries(): void
    {
        $defaultTemplate = $this->template(Project::DEFAULT_ID);
        $selected = Category::query()->create(['name' => 'Selected default category', 'project_id' => Project::DEFAULT_ID]);
        $other = Category::query()->create(['name' => 'Other default category', 'project_id' => Project::DEFAULT_ID]);
        $legacyProjectCategory = Category::query()->create(['name' => 'Legacy named category', 'project_id' => $this->project->id]);
        $namedUncategorized = $this->subscriber('named-uncategorized@example.test', [$this->project->id]);
        $shared = $this->subscriber('shared@example.test', [Project::DEFAULT_ID, $this->project->id]);
        $defaultSelected = $this->subscriber('default-selected@example.test', [Project::DEFAULT_ID]);
        $defaultUnselected = $this->subscriber('default-unselected@example.test', [Project::DEFAULT_ID]);
        $inactive = $this->subscriber('inactive-selected@example.test', [Project::DEFAULT_ID, $this->project->id], false);
        $this->subscribe($shared, $other);
        $this->subscribe($shared, $legacyProjectCategory);
        $this->subscribe($defaultSelected, $selected);
        $this->subscribe($defaultUnselected, $other);
        $this->subscribe($inactive, $selected);
        // Sharing a global category does not grant default-project membership.
        $this->subscribe($namedUncategorized, $selected);
        $payload = [
            'templateId' => [$this->template->id, $defaultTemplate->id],
            'categoryId' => [$selected->id],
        ];
        $payload['logId'] = $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'start_mailing'])
            ->assertOk()->assertJsonPath('result', true)->json('logId');

        $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'count_send'])
            ->assertOk()->assertJsonPath('total', 3);
        $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'send_out'])
            ->assertOk()->assertJsonPath('completed', true);
        $this->assertEqualsCanonicalizing([
            ['email' => $namedUncategorized->email, 'template_id' => $this->template->id],
            ['email' => $shared->email, 'template_id' => $this->template->id],
            ['email' => $defaultSelected->email, 'template_id' => $defaultTemplate->id],
        ], $this->mailer->deliveries);
        $this->assertDatabaseCount('ready_sent', 3);
        $this->assertDatabaseHas('ready_sent', [
            'subscriber_id' => $shared->id, 'template_id' => $this->template->id,
            'project_id' => $this->project->id, 'log_id' => $payload['logId'],
        ]);
        $this->assertDatabaseHas('ready_sent', [
            'subscriber_id' => $defaultSelected->id, 'template_id' => $defaultTemplate->id,
            'project_id' => Project::DEFAULT_ID, 'log_id' => $payload['logId'],
        ]);
        $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'count_send'])
            ->assertOk()->assertJsonPath('total', 3)->assertJsonPath('success', 3)->assertJsonPath('leftsend', 100);
    }

    public function test_global_category_options_are_available_for_manual_mailing_and_ajax_regardless_of_project(): void
    {
        $first = Category::query()->create(['name' => 'Global news']);
        $second = Category::query()->create(['name' => 'Global offers']);
        $expected = [$first->id => $first->name, $second->id => $second->name];

        $this->get(route('admin.templates.index'))->assertOk()->assertViewHas('categoryOptions', $expected);
        foreach ([Project::DEFAULT_ID, $this->project->id, 999999] as $projectId) {
            $response = $this->postJson(route('admin.ajax.action'), [
                'action' => 'get_categories', 'project_id' => $projectId,
            ])->assertOk();
            $this->assertSame(array_keys($expected), array_column($response->json('items'), 'id'));
        }
    }

    public function test_named_project_attempts_are_counted_once_and_history_from_another_batch_does_not_skip_recipients(): void
    {
        $successful = $this->subscriber('success@example.test', [$this->project->id]);
        $failed = $this->subscriber('failed@example.test', [$this->project->id]);
        $this->mailer->failures = [$failed->email];
        $oldLog = $this->log();
        ReadySent::query()->create([
            'project_id' => $this->project->id, 'subscriber_id' => $successful->id,
            'email' => $successful->email, 'template_id' => $this->template->id,
            'template' => $this->template->name, 'success' => 1, 'log_id' => $oldLog->id,
        ]);
        $log = $this->log();
        $payload = ['templateId' => [$this->template->id], 'logId' => $log->id];

        foreach ([1, 2] as $attempt) {
            $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'send_out'])
                ->assertOk()->assertJsonPath('completed', true);
            $this->assertCount(2, $this->mailer->deliveries, 'Resuming a batch must not repeat successful or failed attempts.');
        }
        $this->assertSame(2, ReadySent::query()->where('log_id', $log->id)->count());
        $this->assertDatabaseHas('ready_sent', [
            'subscriber_id' => $failed->id, 'log_id' => $log->id, 'success' => 0, 'errorMsg' => 'Test rejection',
        ]);
        $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'count_send'])
            ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('success', 1)
            ->assertJsonPath('unsuccessful', 1)->assertJsonPath('leftsend', 100);
    }

    #[DataProvider('mailingActions')]
    public function test_unknown_category_identifiers_are_rejected(string $action): void
    {
        $payload = ['action' => $action, 'templateId' => [$this->template->id], 'categoryId' => [999999]];
        if ($action !== 'start_mailing') {
            $payload['logId'] = $this->log()->id;
        }
        $response = $this->postJson(route('admin.ajax.action'), $payload);
        if ($action === 'start_mailing') {
            $response->assertForbidden();
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('categoryId.0');
        }
        $this->assertDatabaseCount('logs', $action === 'start_mailing' ? 0 : 1);
        $this->assertDatabaseCount('ready_sent', 0);
        $this->assertSame([], $this->mailer->deliveries);
    }

    #[DataProvider('unavailableTemplateRequests')]
    public function test_category_free_mailing_still_denies_foreign_or_inactive_templates(string $action, bool $inactive): void
    {
        if ($inactive) {
            $this->project->update(['status' => false]);
        } else {
            $otherManager = User::query()->create([
                'name' => 'Other manager', 'login' => 'other-manual-manager',
                'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'password',
            ]);
            $this->project->update(['owner_id' => $otherManager->id]);
        }
        $this->subscriber('never-send@example.test', [$this->project->id]);
        $payload = ['action' => $action, 'templateId' => [$this->template->id]];
        if ($action !== 'start_mailing') {
            $payload['logId'] = $this->log()->id;
        }
        $this->postJson(route('admin.ajax.action'), $payload)
            ->assertStatus($action === 'start_mailing' ? 403 : 404);
        $this->assertDatabaseCount('logs', $action === 'start_mailing' ? 0 : 1);
        $this->assertDatabaseCount('ready_sent', 0);
        $this->assertSame([], $this->mailer->deliveries);
    }

    public static function emptyCategories(): array
    {
        return [
            'omitted categories' => [[]],
            'empty category array' => [['categoryId' => []]],
            'null categories' => [['categoryId' => null]],
        ];
    }

    public static function mailingActions(): array
    {
        return ['start' => ['start_mailing'], 'send' => ['send_out'], 'count' => ['count_send']];
    }

    public static function requiredCategoryRequests(): array
    {
        $cases = [];
        foreach (self::mailingActions() as $actionName => [$action]) {
            foreach (['default' => false, 'mixed' => true] as $selection => $mixed) {
                foreach (self::emptyCategories() as $inputName => [$input]) {
                    $cases["$actionName $selection $inputName"] = [$action, $mixed, $input];
                }
            }
        }

        return $cases;
    }

    public static function unavailableTemplateRequests(): array
    {
        $cases = [];
        foreach (self::mailingActions() as $actionName => [$action]) {
            foreach (['foreign' => false, 'inactive' => true] as $state => $inactive) {
                $cases["$actionName $state"] = [$action, $inactive];
            }
        }

        return $cases;
    }

    private function template(int $projectId): Templates
    {
        return Templates::query()->create([
            'project_id' => $projectId, 'name' => 'Template '.$projectId, 'body' => '<p>Newsletter</p>', 'prior' => 0,
        ]);
    }

    private function subscriber(string $email, array $projectIds, bool $active = true): Subscribers
    {
        return $this->subscriberFixture([
            'name' => $email, 'email' => $email, 'token' => md5($email), 'active' => $active ? 1 : 0,
        ], $projectIds);
    }

    private function subscribe(Subscribers $subscriber, Category $category): void
    {
        Subscriptions::query()->create(['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);
    }

    private function log(): Logs
    {
        return Logs::query()->create(['time' => now(), 'user_id' => $this->manager->id]);
    }
}

class ManualProjectRecordingMailer extends SendEmailHelper
{
    public array $deliveries = [];
    public array $failures = [];

    public function sendEmail(?int $attach = null): array
    {
        $this->deliveries[] = ['email' => $this->email, 'template_id' => $this->templateId];
        $failed = in_array($this->email, $this->failures, true);

        return ['result' => !$failed, 'error' => $failed ? 'Test rejection' : null];
    }
}

class ManualProjectSendMailService extends SendMailService
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}
