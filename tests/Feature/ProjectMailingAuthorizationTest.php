<?php

namespace Tests\Feature;

use App\Helpers\SendEmailHelper;
use App\Models\{Attach, Category, Logs, Project, ReadySent, Schedule, ScheduleCategory, Subscribers, Subscriptions, Templates, User};
use App\Repositories\{AttachRepository, ProcessRepository, ReadySentRepository, ScheduleRepository, SubscriberRepository};
use App\Services\{EmailLinkService, MailingDelayService, SendMailService};
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProjectMailingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $otherManager;
    private Project $project;
    private Project $foreignProject;
    private Templates $template;
    private Templates $foreignTemplate;
    private Category $category;
    private Category $foreignCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::query()->create(['name' => 'Manager A', 'login' => 'manager-a', 'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'password']);
        $this->otherManager = User::query()->create(['name' => 'Manager B', 'login' => 'manager-b', 'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'password']);
        $this->project = Project::query()->create(['name' => 'Accessible project', 'status' => 1, 'owner_id' => $this->manager->id]);
        $this->foreignProject = Project::query()->create(['name' => 'Foreign project', 'status' => 1, 'owner_id' => $this->otherManager->id]);
        $this->template = $this->templateFor($this->project);
        $this->foreignTemplate = $this->templateFor($this->foreignProject);
        $this->category = Category::query()->create(['name' => 'Category A', 'project_id' => $this->project->id]);
        $this->foreignCategory = Category::query()->create(['name' => 'Category B', 'project_id' => $this->foreignProject->id]);
        $this->actingAs($this->manager);
    }

    public function test_templates_use_the_default_or_a_managed_project_and_reject_foreign_identifiers(): void
    {
        $this->get(route('admin.templates.create'))->assertOk()->assertSee(__('frontend.str.projects.default_name'))->assertSee('Accessible project')->assertDontSee('Foreign project');
        $payload = ['name' => 'New template', 'body' => '<p>Hello</p>', 'prior' => 0];
        $this->post(route('admin.templates.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseHas('templates', ['name' => 'New template', 'project_id' => Project::DEFAULT_ID]);
        $this->post(route('admin.templates.store'), $payload + ['project_id' => $this->foreignProject->id])->assertSessionHasErrors('project_id');
        $this->assertDatabaseMissing('templates', ['name' => 'New template', 'project_id' => $this->foreignProject->id]);
        $this->post(route('admin.templates.store'), $payload + ['project_id' => $this->project->id])->assertSessionHasNoErrors()->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseHas('templates', ['name' => 'New template', 'project_id' => $this->project->id]);
        $this->get(route('admin.templates.show', $this->foreignTemplate->id))->assertNotFound();
        $this->get(route('admin.templates.edit', $this->foreignTemplate->id))->assertNotFound();
        $this->put(route('admin.templates.update'), $payload + ['id' => $this->foreignTemplate->id, 'project_id' => $this->project->id])->assertForbidden();
        $this->delete(route('admin.templates.destroy', $this->foreignTemplate->id))->assertNotFound();
        $this->post(route('admin.templates.status'), ['action' => 1, 'templateId' => [$this->template->id, $this->foreignTemplate->id]])->assertSessionHasErrors('templateId.1');
        $this->assertModelExists($this->template);
        $this->assertModelExists($this->foreignTemplate);
    }

    public function test_assigned_project_admin_can_edit_but_assigned_moderator_cannot_send(): void
    {
        $this->foreignProject->members()->attach($this->manager->id, ['role' => User::ROLE_PROJECT_ADMIN]);
        $this->get(route('admin.templates.edit', $this->foreignTemplate->id))->assertOk();
        $moderator = User::query()->create(['name' => 'Moderator', 'login' => 'mail-moderator', 'role' => User::ROLE_MODERATOR, 'password' => 'password']);
        $this->foreignProject->members()->attach($moderator->id, ['role' => User::ROLE_MODERATOR]);
        $this->actingAs($moderator);
        $this->get(route('admin.templates.edit', $this->foreignTemplate->id))->assertForbidden();
        $this->get(route('admin.schedule.create'))->assertForbidden();
        $this->postJson(route('admin.ajax.action'), ['action' => 'start_mailing', 'templateId' => [$this->foreignTemplate->id], 'categoryId' => [$this->foreignCategory->id]])->assertForbidden();
    }

    public function test_even_an_administrator_cannot_move_an_existing_template_to_another_project(): void
    {
        $this->manager->update(['role' => User::ROLE_ADMIN]);
        $this->put(route('admin.templates.update'), ['id' => $this->template->id, 'name' => $this->template->name, 'body' => $this->template->body, 'prior' => 0, 'project_id' => $this->foreignProject->id])->assertSessionHasErrors('project_id');
        $this->assertSame($this->project->id, $this->template->fresh()->project_id);
    }

    public function test_schedule_rejects_categories_for_named_projects_and_scopes_calendar_actions(): void
    {
        $payload = ['event_name' => 'Project schedule', 'template_id' => $this->template->id, 'categoryId' => [$this->foreignCategory->id], 'date_interval' => now()->addDays(3)->format('d.m.Y H:i').' - '.now()->addDays(3)->addHour()->format('d.m.Y H:i')];
        $this->post(route('admin.schedule.store'), $payload)->assertSessionHasErrors('categoryId');
        unset($payload['categoryId']);
        $this->post(route('admin.schedule.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('admin.schedule.index'));
        $this->assertDatabaseHas('schedule', ['event_name' => 'Project schedule', 'project_id' => $this->project->id]);
        $foreign = $this->scheduleFor($this->foreignTemplate, $this->foreignCategory);
        $this->get(route('admin.schedule.edit', $foreign->id))->assertNotFound();
        $this->postJson(route('admin.schedule.calendarEvents'), ['id' => $foreign->id, 'type' => 'delete'])->assertNotFound();
        $this->deleteJson(route('admin.schedule.destroy', $foreign->id))->assertNotFound();
        $events = $this->getJson(route('admin.schedule.list', ['start' => now()->subDays(5)->toDateString(), 'end' => now()->addDays(5)->toDateString()]))->assertOk()->json();
        $this->assertNotContains($foreign->id, array_column($events, 'id'));
        $this->assertModelExists($foreign);
    }

    public function test_manual_delivery_is_project_scoped_and_records_project_snapshot(): void
    {
        $recipient = $this->subscriberFor($this->project, $this->category, 'own@example.test');
        $foreign = $this->subscriberFor($this->foreignProject, $this->foreignCategory, 'foreign@example.test');
        // A shared global category must never cross the recipient project boundary.
        Subscriptions::query()->create(['subscriber_id' => $foreign->id, 'category_id' => $this->category->id]);
        $log = Logs::query()->create(['time' => now(), 'user_id' => $this->manager->id]);
        $mailer = new ProjectRecordingMailer();
        $service = $this->service($mailer);
        $result = $service->sendOut($this->mailingRequest($log));
        $this->assertTrue($result['completed']);
        $this->assertSame(['own@example.test'], $mailer->recipients);
        $this->assertDatabaseHas('ready_sent', ['subscriber_id' => $recipient->id, 'project_id' => $this->project->id, 'log_id' => $log->id]);
        $this->assertDatabaseMissing('ready_sent', ['subscriber_id' => $foreign->id]);
        $progress = $service->countSend($this->mailingRequest($log));
        $this->assertSame(1, $progress['total']);
        $this->assertSame(1, $progress['success']);
    }

    public function test_multi_project_batch_delivers_each_template_only_to_its_own_project(): void
    {
        $this->foreignProject->members()->attach($this->manager->id, ['role' => User::ROLE_PROJECT_ADMIN]);
        $this->subscriberFor($this->project, $this->category, 'batch-a@example.test');
        $this->subscriberFor($this->foreignProject, $this->foreignCategory, 'batch-b@example.test');
        $log = Logs::query()->create(['time' => now(), 'user_id' => $this->manager->id]);
        $mailer = new ProjectRecordingMailer();
        $service = $this->service($mailer);
        $request = Request::create('/ajax', 'POST', ['templateId' => [$this->template->id, $this->foreignTemplate->id], 'logId' => $log->id]);
        $this->assertSame(2, $service->countSend($request)['total']);
        $service->sendOut($request);
        $this->assertSame(['batch-a@example.test', 'batch-b@example.test'], $mailer->recipients);
        $this->assertDatabaseHas('ready_sent', ['template_id' => $this->template->id, 'email' => 'batch-a@example.test', 'project_id' => $this->project->id]);
        $this->assertDatabaseHas('ready_sent', ['template_id' => $this->foreignTemplate->id, 'email' => 'batch-b@example.test', 'project_id' => $this->foreignProject->id]);
        $this->assertDatabaseMissing('ready_sent', ['template_id' => $this->template->id, 'email' => 'batch-b@example.test']);
        $this->assertDatabaseMissing('ready_sent', ['template_id' => $this->foreignTemplate->id, 'email' => 'batch-a@example.test']);
    }

    public function test_manual_delivery_rejects_another_users_log_before_sending(): void
    {
        $log = Logs::query()->create(['time' => now(), 'user_id' => $this->otherManager->id]);
        $mailer = new ProjectRecordingMailer();
        try {
            $this->service($mailer)->sendOut($this->mailingRequest($log));
            $this->fail('Another user\'s log must not be accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame([], $mailer->recipients);
    }

    public function test_foreign_template_cannot_be_used_for_test_email_or_attachment_deletion(): void
    {
        $attachment = Attach::query()->create(['name' => 'Private attachment', 'file_name' => 'private.txt', 'template_id' => $this->foreignTemplate->id]);
        $mailer = new ProjectRecordingMailer();
        try {
            $this->service($mailer)->sendTest(Request::create('/ajax', 'POST', ['id' => $this->foreignTemplate->id, 'project_id' => $this->project->id, 'name' => 'Test', 'body' => 'Body', 'prior' => 0, 'email' => 'test@example.test']));
            $this->fail('A foreign template must not be accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame([], $mailer->recipients);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(AttachRepository::class)->remove($attachment->id);
    }

    public function test_console_recipient_selection_stays_inside_the_schedule_project(): void
    {
        $own = $this->subscriberFor($this->project, $this->category, 'scheduled-own@example.test');
        $foreign = $this->subscriberFor($this->foreignProject, $this->foreignCategory, 'scheduled-foreign@example.test');
        Subscriptions::query()->create(['subscriber_id' => $foreign->id, 'category_id' => $this->category->id]);
        $schedule = $this->scheduleFor($this->template, $this->category);
        $this->app['auth']->forgetGuards();
        $recipients = app(SubscriberRepository::class)->getSubscribersNotReadySent($schedule->id, 'subscribers.id');
        $this->assertSame([$own->id], $recipients->pluck('id')->all());
        $this->project->update(['status' => 0]);
        $this->assertFalse(app(ScheduleRepository::class)->getScheduleEvent()->contains('id', $schedule->id));
    }

    public function test_inactive_project_cannot_send_test_or_manual_mail(): void
    {
        $this->project->update(['status' => 0]);
        $log = Logs::query()->create(['time' => now(), 'user_id' => $this->manager->id]);
        $mailer = new ProjectRecordingMailer();
        foreach (['sendOut', 'sendTest'] as $method) {
            $request = $method === 'sendOut' ? $this->mailingRequest($log) : Request::create('/ajax', 'POST', ['project_id' => $this->project->id, 'name' => 'Test', 'body' => 'Body', 'prior' => 0, 'email' => 'test@example.test']);
            try {
                $this->service($mailer)->{$method}($request);
                $this->fail('An inactive project must not send mail.');
            } catch (HttpException $exception) {
                $this->assertSame('sendOut', $method);
                $this->assertSame(404, $exception->getStatusCode());
            } catch (ModelNotFoundException $exception) {
                $this->assertSame('sendTest', $method);
                $this->assertSame(Project::class, $exception->getModel());
            }
        }
        $this->assertSame([], $mailer->recipients);
    }

    private function templateFor(Project $project): Templates
    {
        return Templates::query()->create(['name' => $project->name.' template', 'body' => '<p>Hello</p>', 'prior' => 0, 'project_id' => $project->id]);
    }

    private function scheduleFor(Templates $template, Category $category): Schedule
    {
        $schedule = Schedule::query()->create(['event_name' => $template->name, 'event_start' => now()->subHour(), 'event_end' => now()->addHour(), 'template_id' => $template->id, 'project_id' => $template->project_id]);
        ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $category->id]);
        return $schedule;
    }

    private function subscriberFor(Project $project, Category $category, string $email): Subscribers
    {
        $subscriber = $this->subscriberFixture(['name' => $email, 'email' => $email, 'token' => md5($email), 'active' => 1], [$project->id]);
        Subscriptions::query()->create(['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);
        return $subscriber;
    }

    private function mailingRequest(Logs $log): Request
    {
        return Request::create('/ajax', 'POST', ['templateId' => [$this->template->id], 'logId' => $log->id]);
    }

    private function service(ProjectRecordingMailer $mailer): SendMailService
    {
        return new class(app(ReadySentRepository::class), app(SubscriberRepository::class), app(ProcessRepository::class), app(MailingDelayService::class), app(EmailLinkService::class), $mailer) extends SendMailService {
            public function __construct(ReadySentRepository $ready, SubscriberRepository $subscribers, ProcessRepository $process, MailingDelayService $delay, EmailLinkService $links, private ProjectRecordingMailer $mailer)
            {
                parent::__construct($ready, $subscribers, $process, $delay, $links);
            }
            protected function createSendEmailHelper(): SendEmailHelper
            {
                return $this->mailer;
            }
        };
    }
}

class ProjectRecordingMailer extends SendEmailHelper
{
    public array $recipients = [];

    public function sendEmail(?int $attach = null): array
    {
        $this->recipients[] = $this->email;
        return ['result' => true, 'error' => null];
    }
}
