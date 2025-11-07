<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Middleware to authorize access to the Eligify dashboard
 *
 * Uses a similar pattern to Laravel Telescope for authorization
 */
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
                // Log authorization errors if configured
                if (config('eligify.security.log_violations', true)) {
                    logger()->warning('Eligify dashboard authorization closure failed', [
                        'error' => $e->getMessage(),
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                }

                // Fall through to deny if the closure throws
            }

            abort(403, 'Access denied to Eligify dashboard');
        }

        // Otherwise, check the configured Gate (similar to Telescope)
        $gateName = config('eligify.ui.gate', 'viewEligify');
        if (Gate::has($gateName) && Gate::allows($gateName)) {
            return $next($request);
        }

        // Log failed authorization attempts
        if (config('eligify.security.log_violations', true)) {
            $user = $request->user();
            logger()->info('Eligify dashboard access denied', [
                'gate' => $gateName,
                'user_id' => $user ? $user->id : null,
                'ip' => $request->ip(),
            ]);
        }

        // Final safe fallback: allow only in local environment by default
        if (app()->environment('local')) {
            return $next($request);
        }

        abort(403, 'Access denied to Eligify dashboard');
    }
}
