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
    /** Create a global subscriber and attach the requested project memberships. */
    protected function subscriberFixture(array $attributes, array $projectIds = []): \App\Models\Subscribers
    {
        $subscriber = \App\Models\Subscribers::query()->create($attributes);
        $subscriber->projects()->attach($projectIds);

        return $subscriber;
    }

    protected function testProjectId(): int
    {
        $owner = \App\Models\User::query()->where('role', \App\Models\User::ROLE_ADMIN)->first()
            ?? \App\Models\User::query()->create([
                'name' => 'Project fixture owner',
                'login' => 'project-owner-'.\Illuminate\Support\Str::random(12),
                'role' => \App\Models\User::ROLE_ADMIN,
                'password' => 'test-password',
            ]);

        return \App\Models\Project::query()->firstOrCreate(
            ['name' => 'Test project', 'owner_id' => $owner->id],
            ['status' => true]
        )->id;
    }

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
