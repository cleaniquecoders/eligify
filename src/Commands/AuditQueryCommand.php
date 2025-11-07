<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use Illuminate\Console\Command;

class AuditQueryCommand extends Command
{
    protected $signature = 'eligify:audit
                            {--event= : Filter by event type}
                            {--user= : Filter by user ID}
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--search= : Search in context}
                            {--limit=50 : Number of records to show}
                            {--stats : Show audit statistics}
                            {--export= : Export to file (csv|json)}';

    protected $description = 'Query and view audit logs';

    public function handle(): int
    {
        $auditLogger = app(AuditLogger::class);

        if ($this->option('stats')) {
            return $this->showStats($auditLogger);
        }

        $limit = (int) $this->option('limit');
        $audits = collect();

        // Apply filters
        if ($this->option('event')) {
            $audits = $auditLogger->getAuditsByEvent($this->option('event'), $limit);
        } elseif ($this->option('user')) {
            $audits = $auditLogger->getAuditsByUser((int) $this->option('user'), $limit);
        } elseif ($this->option('from') && $this->option('to')) {
            $from = \Carbon\Carbon::parse($this->option('from'));
            $to = \Carbon\Carbon::parse($this->option('to'));
            $audits = $auditLogger->getAuditsByDateRange($from, $to, $limit);
        } elseif ($this->option('search')) {
            $audits = $auditLogger->searchAudits($this->option('search'), $limit);
        } else {
            // Get recent audits
            $audits = \CleaniqueCoders\Eligify\Models\AuditLog::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        }

        if ($audits->isEmpty()) {
            $this->info('No audit logs found matching the criteria.');

            return self::SUCCESS;
        }

        // Handle export
        if ($exportFormat = $this->option('export')) {
            return $this->exportAudits($auditLogger, $audits, $exportFormat);
        }

        // Display results
        $this->displayAudits($audits);

        return self::SUCCESS;
    }

    protected function showStats(AuditLogger $auditLogger): int
    {
        $days = $this->ask('Number of days for statistics', '30');
        $stats = $auditLogger->getAuditStats((int) $days);

        $this->info("Audit Statistics (Last {$stats['period_days']} days)");
        $this->line('');

        $this->info("Total Events: {$stats['total_events']}");
        $this->info('Most Common Event: '.($stats['most_common_event'] ?? 'N/A'));
        $this->line('');

        $this->info('Event Breakdown:');
        $headers = ['Event', 'Count'];
        $rows = [];

        foreach ($stats['event_breakdown'] as $event => $count) {
            $rows[] = [$event, $count];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }

    protected function displayAudits($audits): void
    {
        $headers = ['ID', 'Event', 'Auditable', 'User', 'IP', 'Created At'];
        $rows = [];

        foreach ($audits as $audit) {
            $rows[] = [
                $audit->id,
                $audit->event,
                class_basename($audit->auditable_type).'#'.$audit->auditable_id,
                $audit->user_id ?? 'System',
                $audit->ip_address,
                $audit->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);

        // Show detailed context for first few records
        if ($this->confirm('Show detailed context for recent records?', false)) {
            foreach ($audits->take(3) as $audit) {
                $this->line('');
                $this->info("Audit #{$audit->id} - {$audit->event}");
                $this->line('Context: '.json_encode($audit->context, JSON_PRETTY_PRINT));
            }
        }
    }

    protected function exportAudits(AuditLogger $auditLogger, $audits, string $format): int
    {
        $filename = 'audit-logs-'.now()->format('Y-m-d-H-i-s');

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($audits, $filename);

            case 'json':
                return $this->exportToJson($audits, $filename);

            default:
                $this->error("Unsupported export format: {$format}");

                return self::FAILURE;
        }
    }

    protected function exportToCsv($audits, string $filename): int
    {
        $filepath = storage_path("app/{$filename}.csv");
        $file = fopen($filepath, 'w');

        // CSV headers
        fputcsv($file, ['ID', 'UUID', 'Event', 'Auditable Type', 'Auditable ID', 'User ID', 'IP Address', 'Created At', 'Context']);

        foreach ($audits as $audit) {
            fputcsv($file, [
                $audit->id,
                $audit->uuid,
                $audit->event,
                $audit->auditable_type,
                $audit->auditable_id,
                $audit->user_id,
                $audit->ip_address,
                $audit->created_at->toISOString(),
                json_encode($audit->context),
            ]);
        }

        fclose($file);

        $this->info("Exported {$audits->count()} audit logs to: {$filepath}");

        return self::SUCCESS;
    }

    protected function exportToJson($audits, string $filename): int
    {
        $filepath = storage_path("app/{$filename}.json");

        $data = $audits->map(function ($audit) {
            return [
                'id' => $audit->id,
                'uuid' => $audit->uuid,
                'event' => $audit->event,
                'auditable_type' => $audit->auditable_type,
                'auditable_id' => $audit->auditable_id,
                'user_id' => $audit->user_id,
                'ip_address' => $audit->ip_address,
                'context' => $audit->context,
                'created_at' => $audit->created_at->toISOString(),
            ];
        });

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));

        $this->info("Exported {$audits->count()} audit logs to: {$filepath}");

        return self::SUCCESS;
    }
}
