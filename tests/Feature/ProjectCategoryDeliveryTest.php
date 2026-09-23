<?php

namespace Tests\Feature;

use App\Console\Commands\{SendEmails, SendUnsentEmails};
use App\Helpers\SendEmailHelper;
use App\Models\{Category, Project, ReadySent, Schedule, ScheduleCategory, Subscribers, Templates, User};
use App\Services\{MailingDelayService, SendMailService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class ProjectCategoryDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProjectCategoryRecordingMailer $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Category delivery administrator', 'login' => 'category-delivery-admin',
            'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $this->actingAs($this->admin);
        $this->app->instance(MailingDelayService::class, new class extends MailingDelayService {
            public function waitBetween(int $completedAttempts): void {}
        });
        $this->mailer = new ProjectCategoryRecordingMailer();
        $service = $this->app->make(ProjectCategorySendMailService::class);
        $service->mailer = $this->mailer;
        $this->app->instance(SendMailService::class, $service);
    }

    #[DataProvider('mailingModes')]
    public function test_project_subscribers_with_one_or_several_global_categories_receive_only_one_copy(string $mode): void
    {
        $project = $this->project('Newsletter');
        $foreignProject = $this->project('Another newsletter');
        $category = $this->category($project->id, 'News');
        $anotherCategory = $this->category($project->id, 'Offers');
        $foreignCategory = $this->category($foreignProject->id, 'Foreign news');
        $single = $this->createSubscriber('single@example.test', [$project->id], [$category->id]);
        $multiple = $this->createSubscriber('multiple@example.test', [$project->id], [$category->id, $anotherCategory->id]);
        // Adding the same normalized identity again must not duplicate its memberships or deliveries.
        $same = $this->createSubscriber(' MULTIPLE@EXAMPLE.TEST ', [$project->id], [$category->id, $anotherCategory->id]);
        $this->assertSame($multiple->id, $same->id);
        $this->assertSame(1, $multiple->projects()->count());
        $this->assertSame(2, $multiple->subscriptions()->count());
        $foreign = $this->createSubscriber('foreign@example.test', [$foreignProject->id], [$foreignCategory->id]);
        $inactive = $this->createSubscriber('inactive@example.test', [$project->id], [$category->id, $anotherCategory->id]);
        $this->post(route('admin.subscribers.status'), ['action' => 0, 'activate' => [$inactive->id]])
            ->assertRedirect()->assertSessionMissing('error');
        $this->assertSame(0, (int) $inactive->fresh()->active);
        $template = $this->template($project->id);

        $this->assertMailing($mode, [$template], [], [$single, $multiple, $foreign, $inactive], [
            ['email' => $single->email, 'template_id' => $template->id],
            ['email' => $multiple->email, 'template_id' => $template->id],
        ]);
    }

    #[DataProvider('mailingModes')]
    public function test_shared_identity_with_global_categories_in_several_projects_receives_each_template_once(string $mode): void
    {
        $first = $this->project('First newsletter');
        $second = $this->project('Second newsletter');
        $defaultCategories = [
            $this->category(Project::DEFAULT_ID, 'Default news'),
            $this->category(Project::DEFAULT_ID, 'Default offers'),
        ];
        $firstCategories = [$this->category($first->id, 'First news'), $this->category($first->id, 'First offers')];
        $secondCategory = $this->category($second->id, 'Second news');
        $shared = $this->createSubscriber('shared@example.test', [Project::DEFAULT_ID, $first->id, $second->id], [
            ...array_column($defaultCategories, 'id'), ...array_column($firstCategories, 'id'), $secondCategory->id,
        ]);
        $this->assertSame(3, $shared->projects()->count());
        $this->assertSame(5, $shared->subscriptions()->count());
        $unselected = $this->createSubscriber('unselected-default@example.test', [Project::DEFAULT_ID], [
            $this->category(Project::DEFAULT_ID, 'Unselected default category')->id,
        ]);
        $templates = [$this->template(Project::DEFAULT_ID), $this->template($first->id), $this->template($second->id)];

        $this->assertMailing($mode, $templates, array_column($defaultCategories, 'id'), [$shared, $unselected],
            array_map(fn (Templates $template) => ['email' => $shared->email, 'template_id' => $template->id], $templates));
    }

    #[DataProvider('mailingModes')]
    public function test_global_categories_filter_default_recipients_without_crossing_project_membership_or_duplicating(string $mode): void
    {
        $project = $this->project('Separate newsletter');
        $categories = [
            Category::query()->create(['name' => 'Global news']),
            Category::query()->create(['name' => 'Global offers']),
        ];
        $categoryIds = array_column($categories, 'id');
        $selected = $this->createSubscriber('selected-global@example.test', [Project::DEFAULT_ID], $categoryIds);
        $shared = $this->createSubscriber('shared-global@example.test', [Project::DEFAULT_ID, $project->id], $categoryIds);
        $foreign = $this->createSubscriber('foreign-global@example.test', [$project->id], $categoryIds);
        $unselected = $this->createSubscriber('unselected-global@example.test', [Project::DEFAULT_ID], []);
        $inactive = $this->createSubscriber('inactive-global@example.test', [Project::DEFAULT_ID], $categoryIds);
        $inactive->update(['active' => 0]);
        $template = $this->template(Project::DEFAULT_ID);

        $this->assertMailing($mode, [$template], $categoryIds, [$selected, $shared, $foreign, $unselected, $inactive], [
            ['email' => $selected->email, 'template_id' => $template->id],
            ['email' => $shared->email, 'template_id' => $template->id],
        ]);
    }

    public static function mailingModes(): array
    {
        return ['manual' => ['manual'], 'emails:send' => ['send'], 'emails:unsent' => ['retry']];
    }

    private function assertMailing(string $mode, array $templates, array $defaultCategoryIds, array $subscribers, array $expected): void
    {
        $payload = ['templateId' => array_column($templates, 'id'), 'categoryId' => $defaultCategoryIds];
        if ($mode === 'manual') {
            $payload['logId'] = $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'start_mailing'])
                ->assertOk()->assertJsonPath('result', true)->json('logId');
            $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'count_send'])
                ->assertOk()->assertJsonPath('total', count($expected))->assertJsonPath('success', 0);
        } else {
            foreach ($templates as $template) {
                $schedule = Schedule::query()->create([
                    'project_id' => $template->project_id, 'template_id' => $template->id, 'event_name' => $template->name,
                    'event_start' => now()->subHour(), 'event_end' => now()->addHour(),
                ]);
                if ((int) $template->project_id === Project::DEFAULT_ID) {
                    foreach ($defaultCategoryIds as $categoryId) {
                        ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $categoryId]);
                    }
                }
                if ($mode === 'retry') {
                    foreach ($subscribers as $subscriber) {
                        // Duplicate historical failures, including ineligible recipients, must not duplicate or expand delivery.
                        foreach ([1, 2] as $attempt) {
                            ReadySent::query()->create([
                                'schedule_id' => $schedule->id, 'project_id' => $schedule->project_id,
                                'subscriber_id' => $subscriber->id, 'email' => $subscriber->email,
                                'template_id' => $template->id, 'template' => $template->name,
                                'success' => 0, 'errorMsg' => 'Simulated prior failure',
                            ]);
                        }
                    }
                }
            }
            $this->app['auth']->forgetGuards();
            $this->assertGuest();
        }

        for ($run = 0; $run < 2; $run++) {
            if ($mode === 'manual') {
                $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'send_out'])
                    ->assertOk()->assertJsonPath('completed', true);
                $this->postJson(route('admin.ajax.action'), $payload + ['action' => 'count_send'])
                    ->assertOk()->assertJsonPath('total', count($expected))->assertJsonPath('success', count($expected));
            } else {
                $command = $this->app->make($mode === 'retry' ? ProjectCategorySendUnsentEmails::class : ProjectCategorySendEmails::class);
                $command->mailer = $this->mailer;
                $command->setLaravel($this->app);
                $tester = new CommandTester($command);
                $this->assertSame(0, $tester->execute([]));
                $this->assertMatchesRegularExpression('/^sent: '.($run === 0 ? count($expected) : 0).'$/m', $tester->getDisplay());
            }
            $this->assertEqualsCanonicalizing($expected, $this->mailer->deliveries, 'Several categories or repeated execution must not duplicate delivery.');
        }

        $successes = ReadySent::query()->where('success', 1)->get();
        $this->assertEqualsCanonicalizing($expected, $successes->map(fn (ReadySent $row) => [
            'email' => $row->email, 'template_id' => (int) $row->template_id,
        ])->unique(fn (array $row) => $row['email'].'/'.$row['template_id'])->values()->all());
        foreach ($successes as $success) {
            $template = collect($templates)->firstWhere('id', $success->template_id);
            $this->assertSame((int) $template->project_id, (int) $success->project_id);
        }
    }

    private function createSubscriber(string $email, array $projectIds, array $categoryIds): Subscribers
    {
        $this->post(route('admin.subscribers.store'), [
            'name' => trim($email), 'email' => $email, 'project_ids' => $projectIds, 'categoryId' => $categoryIds,
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $subscriber = Subscribers::query()->where('email', strtolower(trim($email)))->sole();
        $this->assertEqualsCanonicalizing($projectIds, $subscriber->projects()->pluck('projects.id')->all());
        $this->assertEqualsCanonicalizing($categoryIds, $subscriber->subscriptions()->pluck('category_id')->all());

        return $subscriber;
    }

    private function project(string $name): Project
    {
        return Project::query()->create(['name' => $name, 'owner_id' => $this->admin->id, 'status' => 1]);
    }

    private function category(int $projectId, string $name): Category
    {
        return Category::query()->create(['project_id' => $projectId, 'name' => $name]);
    }

    private function template(int $projectId): Templates
    {
        return Templates::query()->create([
            'project_id' => $projectId, 'name' => 'Newsletter '.$projectId, 'body' => '<p>Project category delivery</p>', 'prior' => 0,
        ]);
    }
}

class ProjectCategoryRecordingMailer extends SendEmailHelper
{
    public array $deliveries = [];

    public function sendEmail(?int $attach = null): array
    {
        $this->deliveries[] = ['email' => $this->email, 'template_id' => $this->templateId];

        return ['result' => true, 'error' => null];
    }
}

class ProjectCategorySendMailService extends SendMailService
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}

class ProjectCategorySendEmails extends SendEmails
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}

class ProjectCategorySendUnsentEmails extends SendUnsentEmails
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}
