<?php

use CleaniqueCoders\Eligify\Commands\MakeMappingCommand;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any test mapping files
    $testPath = app_path('Eligify/Mappings');
    if (File::exists($testPath)) {
        File::deleteDirectory($testPath);
    }
});

afterEach(function () {
    // Clean up any test mapping files
    $testPath = app_path('Eligify/Mappings');
    if (File::exists($testPath)) {
        File::deleteDirectory($testPath);
    }
});

it('can generate a model mapping class', function () {
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
    ])
        ->assertSuccessful();

    $expectedPath = app_path('Eligify/Mappings/UserMapping.php');
    expect(File::exists($expectedPath))->toBeTrue();

    $content = File::get($expectedPath);
    expect($content)
        ->toContain('namespace Workbench\App\Eligify\Mappings')
        ->toContain('class UserMapping extends AbstractModelMapping')
        ->toContain("return 'Workbench\App\Models\User'");
});

it('detects model fields and creates field mappings', function () {
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
    ])
        ->assertSuccessful();

    $expectedPath = app_path('Eligify/Mappings/UserMapping.php');
    $content = File::get($expectedPath);

    expect($content)
        ->toContain('protected array $fieldMappings')
        ->toContain('protected array $computedFields');
});

it('prevents overwriting existing mapping without force flag', function () {
    // Create first mapping
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
    ])
        ->assertSuccessful();

    // Try to create again without --force
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
    ])
        ->assertFailed();
});

it('allows overwriting existing mapping with force flag', function () {
    // Create first mapping
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
    ])
        ->assertSuccessful();

    // Create again with --force
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
        '--force' => true,
    ])
        ->assertSuccessful();
});

it('accepts custom namespace option', function () {
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
        '--namespace' => 'Workbench\App\CustomMappings',
    ])
        ->assertSuccessful();

    $expectedPath = app_path('CustomMappings/UserMapping.php');
    expect(File::exists($expectedPath))->toBeTrue();

    $content = File::get($expectedPath);
    expect($content)->toContain('namespace Workbench\App\CustomMappings');

    // Clean up
    if (File::exists(app_path('CustomMappings'))) {
        File::deleteDirectory(app_path('CustomMappings'));
    }
});

it('accepts custom name option', function () {
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
        '--name' => 'custom-user',
    ])
        ->assertSuccessful();

    $expectedPath = app_path('Eligify/Mappings/CustomUserMapping.php');
    expect(File::exists($expectedPath))->toBeTrue();

    $content = File::get($expectedPath);
    expect($content)->toContain('class CustomUserMapping extends AbstractModelMapping');
});

it('fails when model class does not exist', function () {
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'App\Models\NonExistentModel',
    ])
        ->assertFailed()
        ->expectsOutput('Model class [App\Models\NonExistentModel] does not exist.');
});

it('generates computed fields for common patterns', function () {
    $this->artisan(MakeMappingCommand::class, [
        'model' => 'Workbench\App\Models\User',
    ])
        ->assertSuccessful();

    $expectedPath = app_path('Eligify/Mappings/UserMapping.php');
    $content = File::get($expectedPath);

    // Should detect email_verified_at and create is_verified computed field
    expect($content)
        ->toContain("'is_verified'")
        ->toContain('fn ($model) => !is_null($model->email_verified_at ?? null)');
});
