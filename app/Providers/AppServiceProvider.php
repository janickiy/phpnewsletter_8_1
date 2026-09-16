<?php

namespace App\Providers;

use App\Helpers\PermissionsHelper;
use App\Helpers\SettingsHelper;
use App\Helpers\StringHelper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        $loader->alias('PermissionsHelper', PermissionsHelper::class);
        $loader->alias('SettingsHelper', SettingsHelper::class);
        $loader->alias('StringHelper', StringHelper::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
