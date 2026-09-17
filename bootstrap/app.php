<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\Install;
use App\Http\Middleware\Locale;
use App\Http\Middleware\RemoveSubscriber;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(Install::class);
        $middleware->append(Locale::class);

        // Resolve the preferred language again after web cookies are decrypted.
        $middleware->web(append: [Locale::class, RemoveSubscriber::class]);
        $middleware->alias([
            'permission' => CheckPermission::class,
            'project-manager' => \App\Http\Middleware\CheckProjectManager::class,
        ]);
        $middleware->throttleApi('api');

        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo(fn (): string => route('admin.dashboard.index'));
        $middleware->preventRequestForgery(except: ['add-sub', '*/add-sub']);
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions()
    ->create();
