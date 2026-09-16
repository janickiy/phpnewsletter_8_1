<?php

namespace Tests;

use App\Helpers\SettingsHelper;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionProperty;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        // The legacy helper keeps database state beyond the application lifetime.
        (new ReflectionProperty(SettingsHelper::class, 'instance'))->setValue(null, null);

        parent::tearDown();
    }
}
