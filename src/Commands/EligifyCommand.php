<?php

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Models\AuditLog;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Evaluation;
use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Console\Command;

class EligifyCommand extends Command
{
    public $signature = 'eligify
                        {action? : Action to perform (status, stats, health)}
                        {--format=table : Output format (table, json, csv)}';

    public $description = 'Eligify package management and status command';

    public function handle(): int
    {
        $action = $this->argument('action') ?? 'status';

        return match ($action) {
            'status' => $this->showStatus(),
            'stats' => $this->showStatistics(),
            'health' => $this->healthCheck(),
            default => $this->showHelp(),
        };
    }

    protected function showStatus(): int
    {
        $this->info('🎯 Eligify Package Status');
        $this->newLine();

        // Get counts
        $criteriaCount = Criteria::count();
        $rulesCount = Rule::count();
        $evaluationsCount = Evaluation::count();
        $auditCount = AuditLog::count();

        // Show summary table
        $this->table(
            ['Component', 'Count', 'Status'],
            [
                ['Criteria', $criteriaCount, $criteriaCount > 0 ? '✅ Active' : '⚠️  Empty'],
                ['Rules', $rulesCount, $rulesCount > 0 ? '✅ Active' : '⚠️  Empty'],
                ['Evaluations', $evaluationsCount, $evaluationsCount > 0 ? '✅ Active' : 'ℹ️  None'],
                ['Audit Logs', $auditCount, $auditCount > 0 ? '✅ Active' : 'ℹ️  None'],
            ]
        );

        // Show recent criteria
        if ($criteriaCount > 0) {
            $this->newLine();
            $this->info('📋 Recent Criteria:');
            $recentCriteria = Criteria::with('rules')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $criteriaData = $recentCriteria->map(function ($criteria) {
                return [
                    $criteria->name,
                    $criteria->slug,
                    $criteria->rules->count().' rules',
                    $criteria->is_active ? '✅ Active' : '❌ Inactive',
                    $criteria->created_at->format('Y-m-d H:i'),
                ];
            })->toArray();

            $this->table(
                ['Name', 'Slug', 'Rules', 'Status', 'Created'],
                $criteriaData
            );
        }

        return self::SUCCESS;
    }

    protected function showStatistics(): int
    {
        $this->info('📊 Eligify Statistics');
        $this->newLine();

        // Basic counts
        $stats = [
            'Total Criteria' => Criteria::count(),
            'Active Criteria' => Criteria::where('is_active', true)->count(),
            'Total Rules' => Rule::count(),
            'Active Rules' => Rule::where('is_active', true)->count(),
            'Total Evaluations' => Evaluation::count(),
            'Successful Evaluations' => Evaluation::where('passed', true)->count(),
            'Failed Evaluations' => Evaluation::where('passed', false)->count(),
            'Audit Log Entries' => AuditLog::count(),
        ];

        foreach ($stats as $label => $value) {
            $this->line("<info>{$label}:</info> {$value}");
        }

        // Success rate
        $totalEvaluations = Evaluation::count();
        if ($totalEvaluations > 0) {
            $successRate = (Evaluation::where('passed', true)->count() / $totalEvaluations) * 100;
            $this->newLine();
            $this->line('<info>Success Rate:</info> '.number_format($successRate, 2).'%');
        }

        // Top criteria by usage
        $this->newLine();
        $this->info('🏆 Top Criteria by Usage:');

        $topCriteria = Evaluation::selectRaw('criteria_id, COUNT(*) as evaluation_count')
            ->groupBy('criteria_id')
            ->orderBy('evaluation_count', 'desc')
            ->limit(5)
            ->with('criteria')
            ->get();

        if ($topCriteria->isNotEmpty()) {
            $topData = $topCriteria->map(function ($item) {
                $criteria = $item->criteria;

                return [
                    $criteria ? $criteria->name : 'Unknown',
                    $item->evaluation_count,
                    $criteria && $criteria->is_active ? '✅ Active' : '❌ Inactive',
                ];
            })->toArray();

            $this->table(['Criteria', 'Evaluations', 'Status'], $topData);
        } else {
            $this->line('No evaluation data available yet.');
        }

        return self::SUCCESS;
    }

    protected function healthCheck(): int
    {
        $this->info('🔍 Eligify Health Check');
        $this->newLine();

        $checks = [];
        $allPassed = true;

        // Check database tables
        try {
            Criteria::count();
            $checks[] = ['Database Connection', '✅ Connected', 'success'];
        } catch (\Exception $e) {
            $checks[] = ['Database Connection', '❌ Failed: '.$e->getMessage(), 'error'];
            $allPassed = false;
        }

        // Check for orphaned rules
        $orphanedRules = Rule::whereNotIn('criteria_uuid', Criteria::pluck('uuid'))->count();
        if ($orphanedRules === 0) {
            $checks[] = ['Orphaned Rules', '✅ None found', 'success'];
        } else {
            $checks[] = ['Orphaned Rules', "⚠️  {$orphanedRules} found", 'warning'];
        }

        // Check for inactive criteria with rules
        $inactiveCriteriaWithRules = Criteria::where('is_active', false)
            ->whereHas('rules')
            ->count();

        if ($inactiveCriteriaWithRules === 0) {
            $checks[] = ['Inactive Criteria', '✅ All clean', 'success'];
        } else {
            $checks[] = ['Inactive Criteria', "ℹ️  {$inactiveCriteriaWithRules} inactive with rules", 'info'];
        }

        // Check config
        $config = config('eligify');
        if ($config) {
            $checks[] = ['Configuration', '✅ Loaded', 'success'];
        } else {
            $checks[] = ['Configuration', '❌ Not found', 'error'];
            $allPassed = false;
        }

        // Display results
        foreach ($checks as $check) {
            [$component, $status, $type] = $check;
            $color = match ($type) {
                'success' => 'info',
                'error' => 'error',
                'warning' => 'comment',
                default => 'line'
            };
            $this->$color("{$component}: {$status}");
        }

        if ($allPassed) {
            $this->newLine();
            $this->info('🎉 All health checks passed!');
        } else {
            $this->newLine();
            $this->error('❌ Some health checks failed. Please review the issues above.');
        }

        return $allPassed ? self::SUCCESS : self::FAILURE;
    }

    protected function showHelp(): int
    {
        $this->info('🎯 Eligify Package Commands');
        $this->newLine();

        $this->line('<info>Available commands:</info>');
        $this->line('  <comment>eligify status</comment>     Show package status and recent criteria');
        $this->line('  <comment>eligify stats</comment>      Show detailed statistics');
        $this->line('  <comment>eligify health</comment>     Run health check');
        $this->newLine();

        $this->line('<info>Other available commands:</info>');
        $this->line('  <comment>eligify:criteria</comment>   Manage criteria (create, list, edit)');
        $this->line('  <comment>eligify:evaluate</comment>   Run evaluations from CLI');
        $this->line('  <comment>eligify:audit-query</comment>    Query audit logs');
        $this->line('  <comment>eligify:cleanup-audit</comment>  Clean up old audit logs');

        return self::SUCCESS;
    }
}
