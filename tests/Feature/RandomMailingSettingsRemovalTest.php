<?php

namespace Tests\Feature;

use App\Models\Settings;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RandomMailingSettingsRemovalTest extends TestCase
{
    use RefreshDatabase;

    private const array RETIRED_SETTINGS = [
        'RANDOM_SEND',
        'RENDOM_REPLACEMENT_SUBJECT',
        'RANDOM_REPLACEMENT_BODY',
    ];

    public function test_settings_page_omits_randomization_controls_even_with_legacy_values(): void
    {
        foreach (self::RETIRED_SETTINGS as $name) {
            Settings::query()->create(['name' => $name, 'value' => '1']);
        }

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('name="PRECEDENCE"', false)
            ->assertSee('name="CONTENT_TYPE"', false);

        foreach (self::RETIRED_SETTINGS as $name) {
            $response->assertDontSee('name="'.$name.'"', false)
                ->assertDontSee('id="'.$name.'"', false);
        }
    }

    public function test_legacy_submissions_cannot_recreate_retired_randomization_settings(): void
    {
        $data = ['FROM' => 'Saved sender', 'SLEEP' => '3'];

        foreach (self::RETIRED_SETTINGS as $name) {
            $data[$name] = '1';
            $data[strtolower($name)] = '1';
            $data[str_replace('_', ' ', strtolower($name))] = '1';
        }

        $this->actingAs($this->administrator())
            ->put(route('admin.settings.update'), $data)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.settings.index'));

        foreach (self::RETIRED_SETTINGS as $name) {
            $this->assertDatabaseMissing('settings', ['name' => $name]);
        }

        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Saved sender']);
        $this->assertDatabaseHas('settings', ['name' => 'SLEEP', 'value' => '3']);
    }

    public function test_seeded_settings_omit_randomization_and_preserve_existing_values(): void
    {
        Settings::query()->create(['name' => 'FROM', 'value' => 'Saved sender']);

        $this->seed(SettingsSeeder::class);

        foreach (self::RETIRED_SETTINGS as $name) {
            $this->assertDatabaseMissing('settings', ['name' => $name]);
        }

        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Saved sender']);
        $this->assertDatabaseHas('settings', ['name' => 'CONTENT_TYPE', 'value' => 'html']);
        $this->assertDatabaseHas('settings', ['name' => 'PRECEDENCE', 'value' => 'bulk']);
    }

    public function test_upgrade_only_removes_retired_settings_and_rollback_preserves_existing_values(): void
    {
        foreach (self::RETIRED_SETTINGS as $name) {
            Settings::query()->create(['name' => $name, 'value' => '1']);
        }
        Settings::query()->create(['name' => 'FROM', 'value' => 'Saved sender']);

        $migration = require database_path('migrations/2026_09_16_200000_remove_random_mailing_settings.php');
        $migration->up();

        foreach (self::RETIRED_SETTINGS as $name) {
            $this->assertDatabaseMissing('settings', ['name' => $name]);
        }
        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Saved sender']);

        $migration->down();

        foreach (self::RETIRED_SETTINGS as $name) {
            $this->assertDatabaseHas('settings', ['name' => $name, 'value' => '0']);
        }

        Settings::query()->where('name', 'RANDOM_SEND')->update(['value' => '1']);
        $migration->down();

        $this->assertDatabaseHas('settings', ['name' => 'RANDOM_SEND', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Saved sender']);
        $this->assertSame(3, Settings::query()->whereIn('name', self::RETIRED_SETTINGS)->count());
    }

    private function administrator(): User
    {
        return User::query()->create([
            'name' => 'Settings administrator',
            'login' => 'settings-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'secret123',
        ]);
    }
}
