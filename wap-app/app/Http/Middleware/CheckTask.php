<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTask
{
    public function handle(Request $request, Closure $next, string $taskName)
    {
        if (!auth()->user() || !auth()->user()->hasTask($taskName)) {
            abort(403);
        }

        return $next($request);
    }
}
