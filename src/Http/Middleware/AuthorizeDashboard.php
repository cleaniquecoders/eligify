<?php

namespace CleaniqueCoders\Eligify\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuthorizeDashboard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // If a custom authorization closure is provided, use it
        $auth = config('eligify.ui.auth');
        if (is_callable($auth)) {
            try {
                if ($auth($request) === true) {
                    return $next($request);
                }
            } catch (\Throwable $e) {
                // Fall through to deny if the closure throws
            }

            abort(403);
        }

        // Otherwise, check the configured Gate (similar to Telescope)
        $gateName = config('eligify.ui.gate', 'viewEligify');
        if (Gate::has($gateName) && Gate::allows($gateName)) {
            return $next($request);
        }

        // Final safe fallback: allow only in local environment by default
        if (app()->environment('local')) {
            return $next($request);
        }

        abort(403);
    }
}
