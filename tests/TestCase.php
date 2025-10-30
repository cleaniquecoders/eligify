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

        // Load and run all migration stubs in a deterministic order
        $migrationsPath = __DIR__.'/../database/migrations';
        $stubs = glob($migrationsPath.'/*.php.stub') ?: [];

        // Ensure base tables are created before add/pivot migrations
        $preferredOrder = [
            'create_eligify_table.php.stub',
            'add_visibility_columns_to_eligify_criteria.php.stub',
            'create_eligify_criteriables_table.php.stub',
        ];

        usort($stubs, function ($a, $b) use ($preferredOrder) {
            $aBase = basename($a);
            $bBase = basename($b);

            $aPos = array_search($aBase, $preferredOrder, true);
            $bPos = array_search($bBase, $preferredOrder, true);

            $aPos = $aPos === false ? PHP_INT_MAX : $aPos;
            $bPos = $bPos === false ? PHP_INT_MAX : $bPos;

            if ($aPos === $bPos) {
                return strcmp($aBase, $bBase);
            }

            return $aPos <=> $bPos;
        });

        foreach ($stubs as $stub) {
            $migration = include $stub;
            if (is_object($migration) && method_exists($migration, 'up')) {
                $migration->up();
            }
        }

        // Create users table for testing
        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
