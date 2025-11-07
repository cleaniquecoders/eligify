<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Actions;

use Carbon\CarbonImmutable;
use CleaniqueCoders\Eligify\Models\AuditLog;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Evaluation;
use CleaniqueCoders\Eligify\Models\Rule;
use Lorisleiva\Actions\Concerns\AsAction;

class GetDashboardMetrics
{
    use AsAction;

    public function handle(): array
    {
        $now = CarbonImmutable::now();
        $since = $now->subDay();

        $criteriaCount = Criteria::query()->count();
        $rulesCount = Rule::query()->count();

        $evalQuery = Evaluation::query()->where('evaluated_at', '>=', $since);
        $evalCount24h = (clone $evalQuery)->count();
        $passed24h = (clone $evalQuery)->where('passed', true)->count();

        $passRate24h = $evalCount24h > 0
            ? round(($passed24h / $evalCount24h) * 100, 2)
            : null;

        $recentActivity = AuditLog::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['event', 'auditable_type', 'auditable_id', 'created_at'])
            ->map(function ($log) {
                return [
                    'event' => $log->event,
                    'entity' => class_basename($log->auditable_type).'#'.$log->auditable_id,
                    'created_at' => $log->created_at,
                ];
            })
            ->all();

        return [
            'criteria_count' => $criteriaCount,
            'rules_count' => $rulesCount,
            'evaluations_24h' => $evalCount24h,
            'pass_rate_24h' => $passRate24h, // percentage or null
            'recent_activity' => $recentActivity,
        ];
    }
}
