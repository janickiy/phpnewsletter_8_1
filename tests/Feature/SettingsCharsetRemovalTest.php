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
        $this->administrator();
        Settings::query()->create(['name' => 'FROM', 'value' => 'Saved sender']);

        $this->seed(DatabaseSeeder::class);

        $this->assertFalse(Schema::hasTable('charsets'));
        $this->assertDatabaseHas('categories', ['name' => 'Category 1']);
        $this->assertDatabaseMissing('settings', ['name' => 'CHARSET']);
        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => 'Saved sender']);
        $this->assertDatabaseHas('settings', ['name' => 'CONTENT_TYPE', 'value' => 'html']);
        $this->assertDatabaseHas('settings', ['name' => 'PRECEDENCE', 'value' => 'bulk']);
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
