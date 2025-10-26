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

        // Load and run the migration stub directly
        $migrationStub = __DIR__.'/../database/migrations/create_eligify_table.php.stub';
        if (file_exists($migrationStub)) {
            (include $migrationStub)->up();
        }
    }
}
