<?php

namespace Tests\Feature;

use App\Helpers\StringHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class PhpInfoPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_php_info_keeps_diagnostics_without_environment_or_request_secrets(): void
    {
        $this->actingAs(User::query()->create(['name' => 'Moderator', 'login' => 'phpinfo-moderator', 'role' => User::ROLE_MODERATOR, 'password' => 'password']));
        $marker = 'php-info-private-marker-'.bin2hex(random_bytes(8));
        $_ENV['PHPINFO_PRIVATE_TEST'] = $marker;
        $_SERVER['PHPINFO_PRIVATE_TEST'] = $marker;
        try {
            $response = $this->get(route('admin.pages.phpinfo'))->assertOk();
            $response->assertSee(PHP_VERSION)->assertDontSee($marker)->assertDontSee('PHPINFO_PRIVATE_TEST');
            $information = $response->viewData('phpinfo');
            $this->assertArrayHasKey('General', $information);
            $this->assertArrayNotHasKey('Environment', $information);
            $this->assertArrayNotHasKey('PHP Variables', $information);
        } finally {
            unset($_ENV['PHPINFO_PRIVATE_TEST'], $_SERVER['PHPINFO_PRIVATE_TEST']);
        }
    }

    public function test_php_info_redacts_credentials_paths_and_variables_from_parsed_html_rows(): void
    {
        $information = [
            'General' => ['PHP Version' => '8.4.25', 'Loaded Configuration File' => '/private/php.ini'],
            'Environment' => ['APP_KEY' => 'env-marker'],
            'PHP Variables' => ['_SERVER[AUTHORIZATION]' => 'request-marker'],
            'Core' => ['memory_limit' => ['local' => '256M', 'master' => '128M'], 'include_path' => '/private/code', 'custom.endpoint' => 'https://user:password@example.test', 'private_key' => 'key-marker', 'extension.location' => '/private/extension.so', 'pdo.dsn.custom' => 'mysql:dbname=test;password=dsn-marker'],
            'mysqli' => ['Client API library version' => 'mysqlnd 8.4.25', 'mysqli.default_pw' => 'password-marker'],
        ];
        $filtered = (new ReflectionMethod(StringHelper::class, 'sanitizePhpInfo'))->invoke(null, $information);
        $this->assertSame('8.4.25', $filtered['General']['PHP Version']);
        $this->assertSame(['local' => '256M', 'master' => '128M'], $filtered['Core']['memory_limit']);
        $serialized = json_encode($filtered);
        foreach (['env-marker', 'request-marker', '/private/', 'key-marker', 'user:password', 'password-marker', 'dsn-marker'] as $secret) {
            $this->assertStringNotContainsString($secret, $serialized);
        }
    }
}
