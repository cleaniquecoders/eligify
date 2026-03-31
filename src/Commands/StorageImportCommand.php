<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Storage\DatabaseStorageDriver;
use CleaniqueCoders\Eligify\Storage\FilesystemStorageDriver;
use Illuminate\Console\Command;

class StorageImportCommand extends Command
{
    protected $signature = 'eligify:storage-import
                            {criteria? : Specific criteria slug to import (imports all if omitted)}
                            {--disk=local : Laravel filesystem disk to import from}
                            {--path=eligify : Path within the disk}';

    protected $description = 'Import eligify criteria from JSON files into database';

    public function handle(): int
    {
        $disk = $this->option('disk');
        $path = $this->option('path');
        $criteriaSlug = $this->argument('criteria');

        $fileDriver = new FilesystemStorageDriver($disk, $path);
        $dbDriver = new DatabaseStorageDriver;

        if ($criteriaSlug) {
            return $this->importSingle($fileDriver, $dbDriver, $criteriaSlug);
        }

        return $this->importAll($fileDriver, $dbDriver);
    }

    protected function importSingle(FilesystemStorageDriver $fileDriver, DatabaseStorageDriver $dbDriver, string $slug): int
    {
        $data = $fileDriver->exportCriteria($slug);

        if (! $data) {
            $this->error("Criteria '{$slug}' not found in {$this->option('disk')}:{$this->option('path')}/");

            return self::FAILURE;
        }

        $dbDriver->importCriteria($data);
        $this->info("Imported criteria '{$slug}' from file to database.");

        return self::SUCCESS;
    }

    protected function importAll(FilesystemStorageDriver $fileDriver, DatabaseStorageDriver $dbDriver): int
    {
        $allCriteria = $fileDriver->getAllActiveCriteria();

        if ($allCriteria->isEmpty()) {
            $this->warn("No criteria files found in {$this->option('disk')}:{$this->option('path')}/");

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($allCriteria as $criteria) {
            $slug = $criteria->getAttribute('slug');
            $data = $fileDriver->exportCriteria($slug);
            if ($data) {
                $dbDriver->importCriteria($data);
                $count++;
                $this->line("  Imported: {$slug}");
            }
        }

        $this->info("Imported {$count} criteria from files to database.");

        return self::SUCCESS;
    }
}
