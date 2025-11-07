<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class MakeAllMappingsCommand extends Command
{
    public $signature = 'eligify:make-all-mappings
                        {--path=app/Models : Directory path to scan for models (relative to base_path)}
                        {--namespace=App\\Models : Base namespace for models}
                        {--force : Overwrite existing mapping classes}
                        {--dry-run : Show what would be generated without creating files}';

    public $description = 'Generate model mapping classes for all models in a directory';

    protected array $generatedMappings = [];

    protected array $skippedMappings = [];

    protected array $failedMappings = [];

    public function handle(): int
    {
        $path = $this->option('path');
        $namespace = $this->option('namespace');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        // Resolve full path
        $fullPath = base_path($path);

        if (! File::isDirectory($fullPath)) {
            $this->error("Directory [{$fullPath}] does not exist.");

            return self::FAILURE;
        }

        $this->info("Scanning directory: {$fullPath}");
        $this->newLine();

        // Find all PHP files
        $files = File::allFiles($fullPath);
        $modelClasses = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Build fully qualified class name
            $relativePath = str_replace($fullPath.'/', '', $file->getPathname());
            $relativePath = str_replace('.php', '', $relativePath);
            $relativePath = str_replace('/', '\\', $relativePath);

            $className = $namespace.'\\'.$relativePath;

            // Check if class exists and is an Eloquent model
            if (class_exists($className) && is_subclass_of($className, Model::class)) {
                $modelClasses[] = $className;
            }
        }

        if (empty($modelClasses)) {
            $this->warn("No Eloquent models found in [{$fullPath}].");

            return self::SUCCESS;
        }

        $this->info('Found '.count($modelClasses).' model(s):');
        foreach ($modelClasses as $modelClass) {
            $this->line('  - '.$modelClass);
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn('Running in dry-run mode. No files will be created.');
            $this->newLine();
        }

        // Confirm before proceeding
        if (! $dryRun && ! $force) {
            if (! $this->confirm('Do you want to generate mappings for these models?', true)) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        // Generate mapping for each model
        $progressBar = $this->output->createProgressBar(count($modelClasses));
        $progressBar->start();

        foreach ($modelClasses as $modelClass) {
            $result = $this->generateMapping($modelClass, $force, $dryRun);

            if ($result === 'generated') {
                $this->generatedMappings[] = $modelClass;
            } elseif ($result === 'skipped') {
                $this->skippedMappings[] = $modelClass;
            } else {
                $this->failedMappings[] = $modelClass;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($dryRun);

        return self::SUCCESS;
    }

    protected function generateMapping(string $modelClass, bool $force, bool $dryRun): string
    {
        try {
            // Call the make:mapping command for this model
            $arguments = [
                'model' => $modelClass,
                '--force' => $force,
            ];

            if ($dryRun) {
                // Just check if mapping exists
                $className = class_basename($modelClass).'Mapping';
                $namespace = $this->getDefaultNamespace($modelClass);
                $path = $this->getPath($namespace, $className);

                if (File::exists($path) && ! $force) {
                    return 'skipped';
                }

                return 'generated';
            }

            $exitCode = $this->call('eligify:make-mapping', $arguments);

            return $exitCode === self::SUCCESS ? 'generated' : 'failed';
        } catch (\Exception $e) {
            return 'failed';
        }
    }

    protected function getDefaultNamespace(string $modelClass): string
    {
        // Extract the base namespace from the model class
        $modelNamespace = substr($modelClass, 0, strrpos($modelClass, '\\Models\\'));

        // Default to App if no namespace is found
        if (empty($modelNamespace)) {
            return 'App\\Eligify\\Mappings';
        }

        return ltrim($modelNamespace.'\\Eligify\\Mappings', '\\');
    }

    protected function getPath(string $namespace, string $className): string
    {
        // Handle both App\ and Workbench\App\ prefixes
        $namespacePath = $namespace;
        $namespacePath = str_replace('Workbench\\App\\', '', $namespacePath);
        $namespacePath = str_replace('App\\', '', $namespacePath);
        $namespacePath = str_replace('\\', '/', $namespacePath);

        return app_path($namespacePath.'/'.$className.'.php');
    }

    protected function displaySummary(bool $dryRun): void
    {
        $this->info('=== Summary ===');
        $this->newLine();

        if (! empty($this->generatedMappings)) {
            $this->line('<fg=green>✓</> Generated ('.count($this->generatedMappings).'):</fg=green>');
            foreach ($this->generatedMappings as $modelClass) {
                $this->line('  - '.$modelClass);
            }
            $this->newLine();
        }

        if (! empty($this->skippedMappings)) {
            $this->line('<fg=yellow>⊘</> Skipped ('.count($this->skippedMappings).'):</fg=yellow>');
            foreach ($this->skippedMappings as $modelClass) {
                $this->line('  - '.$modelClass.' (already exists)');
            }
            $this->newLine();
        }

        if (! empty($this->failedMappings)) {
            $this->line('<fg=red>✗</> Failed ('.count($this->failedMappings).'):</fg=red>');
            foreach ($this->failedMappings as $modelClass) {
                $this->line('  - '.$modelClass);
            }
            $this->newLine();
        }

        $total = count($this->generatedMappings) + count($this->skippedMappings) + count($this->failedMappings);

        if ($dryRun) {
            $this->info("Dry run completed. {$total} model(s) processed.");
        } else {
            $this->info("Generation completed. {$total} model(s) processed.");
        }
    }
}
