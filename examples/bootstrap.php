<?php

/**
 * Bootstrap file for Eligify examples
 *
 * This file sets up a minimal Laravel application environment
 * so that examples can run as standalone PHP scripts.
 */

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;

// Load Composer autoloader
require __DIR__.'/../vendor/autoload.php';

// Create application container
$app = new Container;
Container::setInstance($app);

// Set up facades
Facade::setFacadeApplication($app);

// Set up configuration repository
$app->singleton('config', function () {
    return new \Illuminate\Config\Repository([
        'eligify' => require __DIR__.'/../config/eligify.php',
        'database' => [
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
            ],
        ],
    ]);
});

// Set up events dispatcher
$app->singleton('events', function ($app) {
    return new Dispatcher($app);
});

// Set up cache manager
$app->singleton('cache', function ($app) {
    return new \Illuminate\Cache\CacheManager($app);
});

$app->singleton('cache.store', function ($app) {
    return $app['cache']->driver();
});

// Register array cache driver
$app['config']->set('cache.default', 'array');
$app['config']->set('cache.stores.array', [
    'driver' => 'array',
    'serialize' => false,
]);

// Register Eligify as a singleton
$app->singleton(\CleaniqueCoders\Eligify\Eligify::class, function () {
    return new \CleaniqueCoders\Eligify\Eligify;
});

// Set up database using Eloquent Capsule
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Make the capsule database manager and schema builder available in the container
$app->instance('db', $capsule->getDatabaseManager());
$app->singleton('db.schema', function () use ($capsule) {
    return $capsule->schema();
});

// Run migrations from stub
$stubFile = __DIR__.'/../database/migrations/create_eligify_table.php.stub';
if (file_exists($stubFile)) {
    $migrationClass = require $stubFile;
    if (is_object($migrationClass)) {
        $migrationClass->up();
    }
}

// Return the application instance
return $app;
