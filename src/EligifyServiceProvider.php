<?php

namespace CleaniqueCoders\Eligify;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Commands\AuditQueryCommand;
use CleaniqueCoders\Eligify\Commands\CleanupAuditLogsCommand;
use CleaniqueCoders\Eligify\Commands\CriteriaCommand;
use CleaniqueCoders\Eligify\Commands\EligifyCommand;
use CleaniqueCoders\Eligify\Commands\EvaluateCommand;
use CleaniqueCoders\Eligify\Engine\RuleEngine;
use CleaniqueCoders\Eligify\Events\CriteriaCreated;
use CleaniqueCoders\Eligify\Events\EvaluationCompleted;
use CleaniqueCoders\Eligify\Events\RuleExecuted;
use CleaniqueCoders\Eligify\Http\Middleware\AuthorizeDashboard;
use CleaniqueCoders\Eligify\Listeners\LogCriteriaCreated;
use CleaniqueCoders\Eligify\Listeners\LogEvaluationCompleted;
use CleaniqueCoders\Eligify\Listeners\LogRuleExecuted;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Observers\CriteriaObserver;
use CleaniqueCoders\Eligify\Observers\RuleObserver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EligifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('eligify')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_eligify_table')
            ->hasCommands([
                EligifyCommand::class,
                CriteriaCommand::class,
                EvaluateCommand::class,
                AuditQueryCommand::class,
                CleanupAuditLogsCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        // Register model observers for audit logging
        if (config('eligify.audit.enabled', true)) {
            Criteria::observe(CriteriaObserver::class);
            Rule::observe(RuleObserver::class);

            // Register event listeners for comprehensive audit logging
            Event::listen(EvaluationCompleted::class, LogEvaluationCompleted::class);
            Event::listen(CriteriaCreated::class, LogCriteriaCreated::class);
            Event::listen(RuleExecuted::class, LogRuleExecuted::class);

            // Schedule automatic audit cleanup
            $this->scheduleAuditCleanup();
        }

        // Register UI routes and middleware if dashboard is enabled
        if (config('eligify.ui.enabled', false)) {
            $this->registerUiAuthorization();
            $this->registerUiRoutes();
        } else {
            // Provide a safe default Gate if one is not defined, even when UI is disabled
            $this->ensureDefaultGate();
        }
    }

    /**
     * Schedule automatic audit log cleanup
     */
    protected function scheduleAuditCleanup(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $cleanupSchedule = config('eligify.audit.cleanup_schedule');

        if (! $cleanupSchedule || ! config('eligify.audit.auto_cleanup', true)) {
            return;
        }

        $command = $schedule->command(CleanupAuditLogsCommand::class);

        match ($cleanupSchedule) {
            'daily' => $command->daily(),
            'weekly' => $command->weekly(),
            'monthly' => $command->monthly(),
            default => $command->cron($cleanupSchedule),
        };
    }

    public function packageRegistered(): void
    {
        // Register the audit logger as a singleton
        $this->app->singleton(AuditLogger::class, function ($app) {
            return new AuditLogger;
        });

        // Register the rule engine as a singleton
        $this->app->singleton(RuleEngine::class, function ($app) {
            return new RuleEngine;
        });

        // Register the main Eligify class
        $this->app->singleton(Eligify::class, function ($app) {
            return new Eligify;
        });
    }

    /**
     * Register dashboard routes behind auth middleware and prefix
     */
    protected function registerUiRoutes(): void
    {
        // Register middleware alias
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        if (! array_key_exists('eligify.authorize', $router->getMiddleware())) {
            $router->aliasMiddleware('eligify.authorize', AuthorizeDashboard::class);
        }

        // Load package routes
        $this->loadRoutesFrom(__DIR__.'/../routes/eligify.php');
    }

    /**
     * Configure authorization strategy similar to Laravel Telescope
     */
    protected function registerUiAuthorization(): void
    {
        // If an auth closure is provided in config, nothing else to do here;
        // the middleware will call it. Otherwise, ensure a sensible default Gate exists.
        $this->ensureDefaultGate();
    }

    /**
     * Ensure a default Gate for viewing the dashboard exists with safe defaults
     */
    protected function ensureDefaultGate(): void
    {
        $gateName = config('eligify.ui.gate', 'viewEligify');

        // Define a default gate only if the application hasn't defined it
        if (! Gate::has($gateName)) {
            Gate::define($gateName, function ($user = null) {
                // Default: allow only in local environment if no explicit auth configured
                return app()->environment('local');
            });
        }
    }
}
