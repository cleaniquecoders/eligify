<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Storage\DatabaseStorageDriver;
use CleaniqueCoders\Eligify\Storage\FilesystemStorageDriver;
use Illuminate\Console\Command;

class StorageExportCommand extends Command
{
    protected $signature = 'eligify:storage-export
                            {criteria? : Specific criteria slug to export (exports all if omitted)}
                            {--disk=local : Laravel filesystem disk to export to}
                            {--path=eligify : Path within the disk}';

    protected $description = 'Export eligify criteria from database to JSON files';

    public function handle(): int
    {
        $disk = $this->option('disk');
        $path = $this->option('path');
        $criteriaSlug = $this->argument('criteria');

        $dbDriver = new DatabaseStorageDriver;
        $fileDriver = new FilesystemStorageDriver($disk, $path);

        if ($criteriaSlug) {
            return $this->exportSingle($dbDriver, $fileDriver, $criteriaSlug);
        }

        return $this->exportAll($dbDriver, $fileDriver);
    }

    protected function exportSingle(DatabaseStorageDriver $dbDriver, FilesystemStorageDriver $fileDriver, string $slug): int
    {
        $data = $dbDriver->exportCriteria($slug);

        if (! $data) {
            $this->error("Criteria '{$slug}' not found in database.");

            return self::FAILURE;
        }

        $fileDriver->importCriteria($data);
        $this->info("Exported criteria '{$slug}' to {$this->option('disk')}:{$this->option('path')}/{$slug}.json");

        return self::SUCCESS;
    }

    protected function exportAll(DatabaseStorageDriver $dbDriver, FilesystemStorageDriver $fileDriver): int
    {
        $allCriteria = $dbDriver->getAllActiveCriteria();

        if ($allCriteria->isEmpty()) {
            $this->warn('No active criteria found in database.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($allCriteria as $criteria) {
            $slug = $criteria->getAttribute('slug');
            $data = $dbDriver->exportCriteria($slug);
            if ($data) {
                $fileDriver->importCriteria($data);
                $count++;
                $this->line("  Exported: {$slug}");
            }
        }

        $this->info("Exported {$count} criteria to {$this->option('disk')}:{$this->option('path')}/");

        return self::SUCCESS;
    }
}
