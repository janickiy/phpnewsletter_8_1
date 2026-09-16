<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ScheduleController;
use App\Models\Category;
use App\Models\Logs;
use App\Models\ReadySent;
use App\Models\Schedule;
use App\Models\ScheduleCategory;
use App\Models\Subscribers;
use App\Models\Templates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_removes_schedule_relations_and_preserves_delivery_history(): void
    {
        $template = Templates::query()->create([
            'name' => 'Schedule deletion test',
            'body' => '<p>Test</p>',
            'prior' => 0,
        ]);
        $category = Category::query()->create([
            'name' => 'Schedule deletion category',
        ]);
        $schedule = Schedule::query()->create([
            'event_name' => 'Delete from calendar',
            'event_start' => now()->addDay(),
            'event_end' => now()->addDay()->addHour(),
            'template_id' => $template->id,
        ]);

        ScheduleCategory::query()->create([
            'schedule_id' => $schedule->id,
            'category_id' => $category->id,
        ]);
        $subscriber = Subscribers::query()->create([
            'name' => 'Schedule recipient',
            'email' => 'schedule@example.test',
            'active' => 1,
            'token' => md5('schedule@example.test'),
        ]);
        $log = Logs::query()->create(['time' => now()]);
        $delivery = ReadySent::query()->create([
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'template_id' => $template->id,
            'template' => $template->name,
            'success' => 1,
            'schedule_id' => $schedule->id,
            'log_id' => $log->id,
        ]);

        $response = app(ScheduleController::class)->destroy($schedule->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'id' => $schedule->id,
        ], $response->getData(true));
        $this->assertDatabaseMissing('schedule', [
            'id' => $schedule->id,
        ]);
        $this->assertDatabaseMissing('schedule_category', [
            'schedule_id' => $schedule->id,
        ]);
        $this->assertDatabaseHas('ready_sent', [
            'id' => $delivery->id,
            'email' => $subscriber->email,
            'success' => 1,
            'schedule_id' => null,
            'log_id' => $log->id,
        ]);
    }
}
