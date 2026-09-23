<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\ScheduleCategory;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduleDateEditingTest extends TestCase
{
    use RefreshDatabase;

    private Schedule $schedule;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-23 12:00:00'));
        $this->actingAs(User::query()->create([
            'name' => 'Schedule administrator',
            'login' => 'schedule-date-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'test-password',
        ]));
        $template = Templates::query()->create([
            'project_id' => Project::DEFAULT_ID,
            'name' => 'Schedule date test',
            'body' => '<p>Test</p>',
            'prior' => 0,
        ]);
        $this->category = Category::query()->create([
            'project_id' => Project::DEFAULT_ID,
            'name' => 'Schedule test category',
        ]);
        $this->schedule = Schedule::query()->create([
            'project_id' => Project::DEFAULT_ID,
            'event_name' => 'Original schedule',
            'event_start' => '2026-09-20 03:00:00',
            'event_end' => '2026-09-21 04:00:00',
            'template_id' => $template->id,
        ]);
        ScheduleCategory::query()->create([
            'schedule_id' => $this->schedule->id,
            'category_id' => $this->category->id,
        ]);
    }

    #[DataProvider('validIntervals')]
    public function test_existing_schedule_can_be_edited_without_a_future_start_requirement(
        string $interval,
        string $start,
        string $end,
    ): void
    {
        $this->from(route('admin.schedule.edit', $this->schedule->id))
            ->put(route('admin.schedule.update'), $this->payload($interval))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.schedule.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedule', [
            'id' => $this->schedule->id,
            'event_name' => 'Updated schedule',
            'event_start' => $start,
            'event_end' => $end,
            'template_id' => $this->schedule->template_id,
            'project_id' => Project::DEFAULT_ID,
        ]);
        $this->assertEquals([$this->category->id], $this->schedule->fresh()->categories->pluck('id')->all());
    }

    public static function validIntervals(): array
    {
        return [
            'extend the end while keeping a past start' => ['20.09.2026 03:00 - 25.09.2026 04:00', '2026-09-20 03:00:00', '2026-09-25 04:00:00'],
            'reschedule to later today' => ['23.09.2026 13:00 - 23.09.2026 14:00', '2026-09-23 13:00:00', '2026-09-23 14:00:00'],
            'edit a completed schedule without changing its dates' => ['20.09.2026 03:00 - 21.09.2026 04:00', '2026-09-20 03:00:00', '2026-09-21 04:00:00'],
        ];
    }

    #[DataProvider('invalidIntervals')]
    public function test_invalid_dates_do_not_change_the_saved_schedule(string $interval, string $field): void
    {
        $before = $this->schedule->fresh()->getAttributes();
        $editUrl = route('admin.schedule.edit', $this->schedule->id);

        $this->from($editUrl)
            ->put(route('admin.schedule.update'), $this->payload($interval))
            ->assertRedirect($editUrl)
            ->assertSessionHasErrors($field)
            ->assertSessionHasInput('date_interval', trim($interval));

        $this->assertSame($before, $this->schedule->fresh()->getAttributes());
        $this->assertEquals([$this->category->id], $this->schedule->fresh()->categories->pluck('id')->all());
    }

    public static function invalidIntervals(): array
    {
        return [
            'end before start' => ['23.09.2026 14:00 - 23.09.2026 13:00', 'event_end'],
            'equal dates' => ['23.09.2026 13:00 - 23.09.2026 13:00', 'event_end'],
            'invalid calendar date' => ['31.09.2026 13:00 - 01.10.2026 14:00', 'event_start'],
            'missing end' => ['23.09.2026 13:00 - ', 'event_end'],
        ];
    }

    private function payload(string $interval): array
    {
        return [
            'id' => $this->schedule->id,
            'event_name' => 'Updated schedule',
            'template_id' => $this->schedule->template_id,
            'categoryId' => [$this->category->id],
            'date_interval' => $interval,
        ];
    }
}
