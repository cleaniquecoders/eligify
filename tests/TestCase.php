<?php

namespace CleaniqueCoders\Eligify\Tests;

use CleaniqueCoders\Eligify\EligifyServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CleaniqueCoders\\Eligify\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            EligifyServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Load and run migrations
        $migrationPath = __DIR__.'/../database/migrations';
        if (is_dir($migrationPath)) {
            foreach (glob($migrationPath.'/*.php') as $migration) {
                (include $migration)->up();
            }
        }
    }
}
