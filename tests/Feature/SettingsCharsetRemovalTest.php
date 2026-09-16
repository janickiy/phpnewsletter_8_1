<?php

namespace Tests\Feature;

use App\Models\Settings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettingsCharsetRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_has_no_outgoing_charset_control_even_with_a_legacy_value(): void
    {
        Settings::query()->create(['name' => 'CHARSET', 'value' => 'windows-1251']);

        $this->actingAs($this->administrator())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertDontSee('name="CHARSET"', false)
            ->assertDontSee('id="CHARSET"', false)
            ->assertSee('name="PRECEDENCE"', false)
            ->assertSee('name="CONTENT_TYPE"', false);
    }

    public function test_legacy_settings_submissions_cannot_recreate_the_outgoing_charset_setting(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('admin.settings.update'), [
                'CHARSET' => 'windows-1251',
                'charset' => 'koi8-r',
                'Charset' => 'iso-8859-1',
                'FROM' => 'Updated sender',
                'SLEEP' => '3',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseMissing('settings', ['name' => 'CHARSET']);
        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Updated sender']);
        $this->assertDatabaseHas('settings', ['name' => 'SLEEP', 'value' => '3']);
    }

    public function test_database_seeding_works_without_charsets_and_preserves_existing_settings(): void
    {
        Settings::query()->create(['name' => 'FROM', 'value' => 'Saved sender']);

        $this->seed(DatabaseSeeder::class);

        $this->assertFalse(Schema::hasTable('charsets'));
        $this->assertDatabaseHas('categories', ['name' => 'Category 1']);
        $this->assertDatabaseMissing('settings', ['name' => 'CHARSET']);
        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Saved sender']);
        $this->assertDatabaseHas('settings', ['name' => 'CONTENT_TYPE', 'value' => 'html']);
        $this->assertDatabaseHas('settings', ['name' => 'PRECEDENCE', 'value' => 'bulk']);
    }

    public function test_upgrade_removes_only_the_retired_setting_and_rollback_restores_utf8_default(): void
    {
        Settings::query()->create(['name' => 'CHARSET', 'value' => 'windows-1251']);
        Settings::query()->create(['name' => 'FROM', 'value' => 'Saved sender']);

        $migration = require database_path('migrations/2026_09_16_190000_remove_outgoing_charset_setting.php');
        $migration->up();

        $this->assertDatabaseMissing('settings', ['name' => 'CHARSET']);
        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Saved sender']);

        $migration->down();

        $this->assertDatabaseHas('settings', ['name' => 'CHARSET', 'value' => 'utf-8']);
        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Saved sender']);
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
