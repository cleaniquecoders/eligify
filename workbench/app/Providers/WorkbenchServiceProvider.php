<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Configure Eligify UI early so the package provider sees it during boot
        config([
            'eligify.ui.enabled' => true,
            'eligify.ui.route_prefix' => 'eligify',
            'eligify.ui.middleware' => ['web'],
            'eligify.ui.auth' => function ($request) {
                return app()->environment('local');
            },
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Optionally define the Gate for completeness (not strictly needed due to closure)
        Gate::define('viewEligify', function ($user = null) {
            return app()->environment('local');
        });
    }
}
