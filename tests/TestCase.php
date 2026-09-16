<?php

namespace Tests;

use App\Helpers\SettingsHelper;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionProperty;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        // Check before providers boot: cached configuration may point at the live database.
        if ($app->configurationIsCached()) {
            throw new RuntimeException('Clear the configuration cache before testing so the isolated test database is used.');
        }

        $this->traitsUsedByTest = class_uses_recursive(static::class);
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function tearDown(): void
    {
        // The legacy helper keeps database state beyond the application lifetime.
        (new ReflectionProperty(SettingsHelper::class, 'instance'))->setValue(null, null);

        parent::tearDown();
    }
}
