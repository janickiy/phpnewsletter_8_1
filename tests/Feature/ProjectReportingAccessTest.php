<?php

namespace Tests\Feature;

use App\Models\Logs;
use App\Models\Project;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Subscribers;
use App\Models\Schedule;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectReportingAccessTest extends TestCase
{
    use RefreshDatabase;

    private Project $own;
    private Project $other;
    private User $moderator;
    private User $manager;
    private Logs $mixedLog;
    private Logs $foreignLog;
    private ReadySent $ownDelivery;
    private ReadySent $foreignDelivery;
    private const SHARED_URL = 'https://example.test/shared';
    private const FOREIGN_URL = 'https://example.test/secret';

    protected function setUp(): void
    {
        parent::setUp();
        $owner = $this->user('owner', User::ROLE_ADMIN);
        $this->moderator = $this->user('moderator', User::ROLE_MODERATOR);
        $this->manager = $this->user('manager', User::ROLE_PROJECT_ADMIN);
        $this->own = Project::query()->create(['name' => 'Assigned project', 'status' => 1, 'owner_id' => $owner->id]);
        $this->other = Project::query()->create(['name' => 'Secret project', 'status' => 1, 'owner_id' => $owner->id]);
        $this->own->members()->attach($this->moderator, ['role' => User::ROLE_MODERATOR]);
        $this->own->members()->attach($this->manager, ['role' => User::ROLE_PROJECT_ADMIN]);
        $this->mixedLog = Logs::query()->create(['time' => now(), 'user_id' => $owner->id]);
        $this->foreignLog = Logs::query()->create(['time' => now(), 'user_id' => $owner->id]);
        $this->ownDelivery = $this->delivery($this->own, $this->mixedLog, 'allowed@example.test', 1);
        $this->foreignDelivery = $this->delivery($this->other, $this->mixedLog, 'secret@example.test', 0);
        $this->delivery($this->other, $this->foreignLog, 'foreign@example.test', 0);
        Redirect::query()->create(['project_id' => $this->own->id, 'url' => self::SHARED_URL, 'email' => 'allowed@example.test']);
        Redirect::query()->create(['project_id' => $this->other->id, 'url' => self::SHARED_URL, 'email' => 'secret@example.test']);
        Redirect::query()->create(['project_id' => $this->other->id, 'url' => self::FOREIGN_URL, 'email' => 'foreign@example.test']);
        $this->actingAs($this->moderator);
    }

    public function test_mixed_mailing_datatables_only_include_assigned_project_rows_and_counts(): void
    {
        $result = $this->getJson(route('admin.datatable.logs'))->assertOk()->assertJsonPath('recordsTotal', 1);
        $result->assertJsonPath('data.0.id', $this->mixedLog->id);
        $this->assertSame('1', strip_tags($result->json('data.0.count')));
        $this->assertEquals(1, $result->json('data.0.sent'));
        $this->assertEquals(0, $result->json('data.0.unsent'));
        $this->getJson(route('admin.datatable.info_log', $this->mixedLog->id))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'allowed@example.test');
        $this->getJson(route('admin.datatable.info_log'))->assertOk()->assertJsonCount(1, 'data');
        foreach (['admin.log.info', 'admin.log.report', 'admin.datatable.info_log'] as $route) {
            $this->get(route($route, $this->foreignLog->id))->assertNotFound();
        }
    }

    public function test_mailing_download_summary_and_rows_do_not_disclose_foreign_part_of_same_log(): void
    {
        $response = $this->get(route('admin.log.report', $this->mixedLog->id))->assertOk();
        $xml = $this->worksheet($response->streamedContent());
        $this->assertStringContainsString('allowed@example.test', $xml);
        $this->assertStringNotContainsString('secret@example.test', $xml);
        $this->assertStringContainsString(__('frontend.str.total').': 1', $xml);
        $this->assertStringContainsString(__('frontend.str.sent').': 100%', $xml);
        $this->assertStringContainsString('A1:F3', $xml);
    }

    public function test_shared_redirect_url_reports_only_include_assigned_project(): void
    {
        $encoded = $this->encoded(self::SHARED_URL);
        $list = $this->getJson(route('admin.datatable.redirect'))->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.url', self::SHARED_URL);
        $this->assertSame('1', strip_tags($list->json('data.0.count')));
        $this->getJson(route('admin.datatable.info_redirect', $encoded))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'allowed@example.test');
        $response = $this->get(route('admin.redirect.report', $encoded))->assertOk();
        $xml = $this->worksheet($response->streamedContent());
        $this->assertStringContainsString('allowed@example.test', $xml);
        $this->assertStringNotContainsString('secret@example.test', $xml);
        $this->assertStringContainsString('A1:B2', $xml);
        foreach (['admin.redirect.info', 'admin.redirect.report'] as $route) {
            $this->get(route($route, $this->encoded(self::FOREIGN_URL)))->assertNotFound();
        }
        $this->getJson(route('admin.datatable.info_redirect', $this->encoded(self::FOREIGN_URL)))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_moderator_cannot_clear_reports_and_project_admin_only_clears_assigned_rows(): void
    {
        $this->postJson(route('admin.log.clear'))->assertForbidden();
        $this->postJson(route('admin.redirect.clear'))->assertForbidden();
        $this->assertDatabaseCount('ready_sent', 3);
        $this->assertDatabaseCount('redirect', 3);
        $this->actingAs($this->manager);
        $this->postJson(route('admin.log.clear'))->assertOk()->assertJsonPath('success', true);
        $this->postJson(route('admin.redirect.clear'))->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('ready_sent', ['id' => $this->ownDelivery->id]);
        $this->assertDatabaseHas('ready_sent', ['id' => $this->foreignDelivery->id]);
        $this->assertDatabaseCount('ready_sent', 2);
        $this->assertDatabaseHas('logs', ['id' => $this->mixedLog->id]);
        $this->assertDatabaseHas('logs', ['id' => $this->foreignLog->id]);
        $this->assertDatabaseMissing('redirect', ['project_id' => $this->own->id]);
        $this->assertDatabaseCount('redirect', 2);
    }

    public function test_ajax_requires_authentication_and_blocks_moderator_mailing_actions(): void
    {
        foreach (['send_test_email', 'start_mailing', 'send_out', 'count_send', 'remove_schedule', 'remove_attach', 'log_online', 'process'] as $action) {
            $this->postJson(route('admin.ajax.action'), ['action' => $action])->assertForbidden();
        }
        auth()->logout();
        foreach (['get_categories', 'send_out', 'count_send', 'log_online', 'start_mailing'] as $action) {
            $this->postJson(route('admin.ajax.action'), ['action' => $action])->assertUnauthorized();
        }
    }

    public function test_ajax_mutations_reject_get_without_changing_data(): void
    {
        $this->actingAs($this->manager);
        $schedule = Schedule::query()->create([
            'project_id' => $this->own->id,
            'template_id' => $this->ownDelivery->template_id,
            'event_name' => 'Must remain',
            'event_start' => now()->addDay(),
            'event_end' => now()->addDays(2),
        ]);
        foreach (['remove_schedule', 'remove_attach', 'start_mailing', 'send_out', 'send_test_email', 'process', 'start_update', 'change_lng'] as $action) {
            $this->getJson(route('admin.ajax.action', ['action' => $action, 'id' => $schedule->id, 'command' => 'stop']))->assertStatus(405);
        }
        $this->assertDatabaseHas('schedule', ['id' => $schedule->id]);
        $this->assertDatabaseCount('logs', 2);
        $this->assertDatabaseCount('ready_sent', 3);
    }

    public function test_public_tracking_cannot_mix_projects_and_redirect_stores_project_snapshot(): void
    {
        auth()->logout();
        $this->ownDelivery->update(['readMail' => 0]);
        $this->get(route('frontend.pic', ['subscriber' => $this->ownDelivery->subscriber_id, 'template' => $this->foreignDelivery->template_id]))->assertNotFound();
        $this->assertEquals(0, $this->ownDelivery->fresh()->readMail);
        $this->get(route('frontend.pic', ['subscriber' => $this->ownDelivery->subscriber_id, 'template' => $this->ownDelivery->template_id]))->assertOk();
        $this->assertEquals(1, $this->ownDelivery->fresh()->readMail);
        $url = 'https://example.test/tracked';
        $this->get(route('frontend.referral', ['subscriber' => $this->ownDelivery->subscriber_id, 'ref' => base64_encode($url)]))->assertRedirect($url);
        $this->assertDatabaseHas('redirect', ['project_id' => $this->own->id, 'url' => $url, 'email' => 'allowed@example.test']);
        $this->own->update(['status' => 0]);
        $this->get(route('frontend.referral', ['subscriber' => $this->ownDelivery->subscriber_id, 'ref' => base64_encode($url)]))->assertNotFound();
    }

    public function test_dashboard_counts_and_visible_sections_are_scoped_for_moderator(): void
    {
        $response = $this->get(route('admin.dashboard.index'))->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['subscribers']);
        $this->assertEquals(1, $stats['sentTotal']);
        $this->assertEquals(1, $stats['sentSuccess']);
        $this->assertEquals(0, $stats['sentFailed']);
        $this->assertEquals(1, $stats['clicks']);
        $response->assertDontSee('secret@example.test')->assertDontSee('foreign@example.test');
        foreach (['admin.templates.index', 'admin.schedule.index', 'admin.settings.index', 'admin.users.index', 'admin.smtp.index'] as $route) {
            $response->assertDontSee('href="'.route($route).'"', false);
        }
        $response->assertSee('href="'.route('admin.subscribers.index').'"', false);
    }

    private function user(string $login, string $role): User
    {
        return User::query()->create(['name' => ucfirst($login), 'login' => $login, 'role' => $role, 'password' => 'password']);
    }

    private function delivery(Project $project, Logs $log, string $email, int $success): ReadySent
    {
        $subscriber = $this->subscriberFixture(['email' => $email, 'active' => 1, 'token' => bin2hex(random_bytes(16))], [$project->id]);
        $template = Templates::query()->create(['project_id' => $project->id, 'name' => $project->name.' template', 'body' => 'Body', 'prior' => 0]);
        return ReadySent::query()->create(['project_id' => $project->id, 'subscriber_id' => $subscriber->id, 'email' => $email, 'template_id' => $template->id, 'template' => $template->name, 'success' => $success, 'readMail' => $success, 'log_id' => $log->id]);
    }

    private function encoded(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    private function worksheet(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'project_report_');
        file_put_contents($path, $contents);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path));
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);
        $this->assertIsString($xml);
        return $xml;
    }
}
