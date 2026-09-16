<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ScheduleController;
use App\Models\Category;
use App\Models\Schedule;
use App\Models\ScheduleCategory;
use App\Models\Templates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_returns_deleted_event_id_and_removes_schedule_relations(): void
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
    }
}
