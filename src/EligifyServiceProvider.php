<?php

namespace CleaniqueCoders\Eligify;

use CleaniqueCoders\Eligify\Commands\EligifyCommand;
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
            ->hasCommand(EligifyCommand::class);
    }
}
