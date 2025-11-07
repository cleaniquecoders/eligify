<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use Illuminate\Console\Command;

class CleanupAuditLogsCommand extends Command
{
    protected $signature = 'eligify:cleanup-audit
                            {--days= : Number of days to retain (overrides config)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old audit logs based on retention policy';

    public function handle(): int
    {
        $auditLogger = app(AuditLogger::class);

        $retentionDays = $this->option('days')
            ? (int) $this->option('days')
            : config('eligify.audit.retention_days', 365);

        $isDryRun = $this->option('dry-run');

        $this->info("Cleaning up audit logs older than {$retentionDays} days...");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No logs will actually be deleted');

            // Show what would be deleted
            $cutoffDate = now()->subDays($retentionDays);
            $count = \CleaniqueCoders\Eligify\Models\AuditLog::where('created_at', '<', $cutoffDate)->count();

            $this->info("Would delete {$count} audit log records created before {$cutoffDate->format('Y-m-d H:i:s')}");

            return self::SUCCESS;
        }

        if (! config('eligify.audit.auto_cleanup', true)) {
            $this->warn('Auto cleanup is disabled in configuration. Use --force to override this setting.');

            if (! $this->confirm('Do you want to proceed anyway?')) {
                $this->info('Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        $deletedCount = $auditLogger->cleanup();

        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} old audit log records.");
        } else {
            $this->info('No old audit logs found to clean up.');
        }

        return self::SUCCESS;
    }
}
