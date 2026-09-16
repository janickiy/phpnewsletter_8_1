<?php

namespace Tests\Feature;

use App\Models\Logs;
use App\Models\ReadySent;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualMailingLogReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_mailing_is_available_in_summary_details_and_download(): void
    {
        $admin = User::query()->create([
            'name' => 'Report admin',
            'login' => 'report-admin',
            'description' => null,
            'role' => User::ROLE_ADMIN,
            'password' => 'password',
        ]);
        $template = Templates::query()->create([
            'name' => 'Manual report template',
            'body' => '<p>Manual report body</p>',
            'prior' => 0,
        ]);
        $firstSubscriber = $this->createSubscriber('first@example.test');
        $secondSubscriber = $this->createSubscriber('second@example.test');
        $otherSubscriber = $this->createSubscriber('other@example.test');

        $manualLog = Logs::query()->create([
            'time' => '2026-07-26 02:40:17',
        ]);
        $otherLog = Logs::query()->create([
            'time' => '2026-07-26 02:41:17',
        ]);

        $this->createDelivery($manualLog, $template, $firstSubscriber, 1, 1);
        $this->createDelivery($manualLog, $template, $secondSubscriber, 0, null);
        $this->createDelivery($otherLog, $template, $otherSubscriber, 1, null);

        $summaryResponse = $this->actingAs($admin)->getJson(route('admin.datatable.logs', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $summaryResponse->assertOk();

        $manualSummary = collect($summaryResponse->json('data'))
            ->first(fn (array $row) => (int) $row['id'] === $manualLog->id);

        $this->assertNotNull($manualSummary);
        $this->assertStringContainsString('>2</a>', $manualSummary['count']);
        $this->assertSame(1, (int) $manualSummary['sent']);
        $this->assertSame(1, (int) $manualSummary['unsent']);
        $this->assertSame(1, (int) $manualSummary['read_mail']);
        $this->assertStringContainsString(
            route('admin.log.info', ['id' => $manualLog->id]),
            $manualSummary['count']
        );
        $this->assertStringContainsString(
            route('admin.log.report', ['id' => $manualLog->id]),
            $manualSummary['report']
        );

        $detailsResponse = $this->actingAs($admin)->getJson(route('admin.datatable.info_log', [
            'id' => $manualLog->id,
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $detailsResponse->assertOk();
        $this->assertSame(2, (int) $detailsResponse->json('recordsTotal'));
        $this->assertEqualsCanonicalizing(
            ['first@example.test', 'second@example.test'],
            collect($detailsResponse->json('data'))->pluck('email')->all()
        );

        $this->actingAs($admin)
            ->get(route('admin.log.report', ['id' => $manualLog->id]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_deleting_a_log_preserves_delivery_history(): void
    {
        $template = Templates::query()->create([
            'name' => 'Preserved delivery template',
            'body' => '<p>Delivery history</p>',
            'prior' => 0,
        ]);
        $subscriber = $this->createSubscriber('preserved@example.test');
        $log = Logs::query()->create(['time' => now()]);
        $delivery = $this->createDelivery($log, $template, $subscriber, 1, 1);

        $log->delete();

        $this->assertDatabaseMissing('logs', ['id' => $log->id]);
        $this->assertDatabaseHas('ready_sent', [
            'id' => $delivery->id,
            'email' => $subscriber->email,
            'success' => 1,
            'readMail' => 1,
            'schedule_id' => null,
            'log_id' => null,
        ]);
    }

    private function createSubscriber(string $email): Subscribers
    {
        return Subscribers::query()->create([
            'name' => $email,
            'email' => $email,
            'active' => 1,
            'token' => md5($email),
        ]);
    }

    private function createDelivery(
        Logs $log,
        Templates $template,
        Subscribers $subscriber,
        int $success,
        ?int $readMail
    ): ReadySent {
        return ReadySent::query()->create([
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'template_id' => $template->id,
            'template' => $template->name,
            'success' => $success,
            'errorMsg' => $success === 1 ? null : 'Mailpit rejected the message',
            'readMail' => $readMail,
            'schedule_id' => null,
            'log_id' => $log->id,
        ]);
    }
}
