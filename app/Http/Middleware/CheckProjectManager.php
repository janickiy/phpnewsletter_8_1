<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProjectManager
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->user()) {
            return redirect()->guest(route('login'));
        }

        abort_unless($request->user()->canManageProjects(), 403);
        return $next($request);
    }
}
