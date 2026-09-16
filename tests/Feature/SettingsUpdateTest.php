<?php

namespace Tests\Feature;

use App\DTO\Update\SettingsUpdateData;
use App\Models\CustomHeaders;
use App\Models\Settings;
use App\Repositories\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_update_creates_missing_values_and_keeps_headers_separate(): void
    {
        Settings::query()->create([
            'name' => 'SLEEP',
            'value' => '1',
        ]);

        app(SettingsRepository::class)->setSettings(
            new SettingsUpdateData([
                'SLEEP' => '3',
                'ORGANIZATION' => 'PHP Newsletter',
                'header_name' => ['X-Test-Header'],
                'header_value' => ['enabled'],
            ])
        );

        $this->assertDatabaseHas('settings', [
            'name' => 'SLEEP',
            'value' => '3',
        ]);
        $this->assertDatabaseHas('settings', [
            'name' => 'ORGANIZATION',
            'value' => 'PHP Newsletter',
        ]);
        $this->assertSame(1, Settings::query()->where('name', 'SLEEP')->count());
        $this->assertDatabaseMissing('settings', [
            'name' => 'header_name',
        ]);
        $this->assertDatabaseHas(CustomHeaders::getTableName(), [
            'name' => 'X-Test-Header',
            'value' => 'enabled',
        ]);
    }
}
