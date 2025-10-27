<?php

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Models\AuditLog;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['eligify.audit.enabled' => true]);
    $this->auditLogger = app(AuditLogger::class);
});

it('can log evaluation completed', function () {
    $criteria = Criteria::factory()->create(['name' => 'test_criteria']);
    $data = ['score' => 80];
    $result = ['passed' => true, 'score' => 100];

    $this->auditLogger->logEvaluation($criteria, $data, $result);

    expect(AuditLog::count())->toBe(2); // criteria_created + evaluation_completed

    $evaluationAudit = AuditLog::where('event', 'evaluation_completed')->first();
    expect($evaluationAudit)->not->toBeNull();
    expect($evaluationAudit->event)->toBe('evaluation_completed');
    expect($evaluationAudit->auditable_type)->toBe(Criteria::class);
    expect($evaluationAudit->auditable_id)->toBe($criteria->id);
    expect($evaluationAudit->context['criteria_name'])->toBe('test_criteria');
    expect($evaluationAudit->context['result'])->toBe($result);
});

it('can log rule operations', function () {
    $criteria = Criteria::factory()->create();
    $rule = Rule::factory()->create(['criteria_id' => $criteria->id]);

    // Rule created audit should be automatically logged by observer
    expect(AuditLog::where('event', 'rule_created')->count())->toBe(1);

    // Test rule update logging
    $originalAttributes = $rule->getAttributes();
    $rule->update(['field' => 'new_field']);

    // Should have created and updated events
    expect(AuditLog::where('event', 'rule_updated')->count())->toBe(1);

    $updateAudit = AuditLog::where('event', 'rule_updated')->first();
    expect($updateAudit->context['changes']['field']['from'])->toBe($originalAttributes['field']);
    expect($updateAudit->context['changes']['field']['to'])->toBe('new_field');
});

it('can query audit logs by event', function () {
    $criteria = Criteria::factory()->create();

    // Create some audit logs
    $this->auditLogger->logEvaluation($criteria, [], []);

    $evaluationAudits = $this->auditLogger->getAuditsByEvent('evaluation_completed');
    $criteriaAudits = $this->auditLogger->getAuditsByEvent('criteria_created');

    expect($evaluationAudits->count())->toBe(1);
    expect($criteriaAudits->count())->toBe(1); // From observer when criteria was created
});

it('can query audit logs by date range', function () {
    $criteria = Criteria::factory()->create();

    // Create audit logs
    $this->auditLogger->logEvaluation($criteria, [], []);

    $from = now()->subHour();
    $to = now()->addHour();

    $audits = $this->auditLogger->getAuditsByDateRange($from, $to);

    expect($audits->count())->toBeGreaterThan(0);
});

it('can search audit logs', function () {
    $criteria = Criteria::factory()->create(['name' => 'searchable_criteria']);
    $this->auditLogger->logEvaluation($criteria, [], []);

    $results = $this->auditLogger->searchAudits('searchable');

    expect($results->count())->toBeGreaterThan(0);
});

it('can get audit statistics', function () {
    // Clear existing audit logs for a clean test
    AuditLog::truncate();

    $criteria = Criteria::factory()->create();

    // At this point we should have 1 criteria_created log
    expect(AuditLog::count())->toBe(1);
    expect(AuditLog::first()->event)->toBe('criteria_created');

    // Create a rule (this will also trigger RuleObserver)
    $rule = Rule::factory()->create(['criteria_id' => $criteria->id]);

    // Check actual count after rule creation (might not create audit log if observers are disabled)
    $countAfterRule = AuditLog::count();

    // Create additional audit events manually - check each step
    $this->auditLogger->logEvaluation($criteria, [], []);
    $countAfterFirstEval = AuditLog::count();
    expect($countAfterFirstEval)->toBe($countAfterRule + 1);

    $this->auditLogger->logEvaluation($criteria, [], []);
    $countAfterSecondEval = AuditLog::count();
    expect($countAfterSecondEval)->toBe($countAfterRule + 2);

    $this->auditLogger->logRuleExecuted($rule, ['test' => 'data'], true);
    $finalCount = AuditLog::count();
    expect($finalCount)->toBe($countAfterRule + 3);

    $stats = $this->auditLogger->getAuditStats(1);

    expect($stats['total_events'])->toBe($finalCount);
    expect($stats['event_breakdown'])->toHaveKey('evaluation_completed');
    expect($stats['event_breakdown'])->toHaveKey('criteria_created');

    // Verify that evaluation_completed has exactly 2 occurrences
    expect($stats['event_breakdown']['evaluation_completed'])->toBe(2);

    // Most common should be evaluation_completed (2 occurrences)
    expect($stats['most_common_event'])->toBe('evaluation_completed');
});

it('can cleanup old audit logs', function () {
    // Ensure cleanup is enabled
    config(['eligify.audit.auto_cleanup' => true]);
    config(['eligify.audit.retention_days' => 365]);

    // Create a fresh AuditLogger instance with the new config
    $auditLogger = new \CleaniqueCoders\Eligify\Audit\AuditLogger;

    $criteria = Criteria::factory()->create();

    // Count audit logs after criteria creation (observers may create logs)
    $initialCount = AuditLog::count();

    // Create an old audit log with explicit timestamp - use Carbon for precision
    $oldTimestamp = now()->subDays(400);

    // Use DB facade to insert directly to ensure timestamps are set correctly
    DB::table('eligify_audit_logs')->insert([
        'uuid' => (string) str()->uuid(),
        'event' => 'test_event',
        'auditable_type' => Criteria::class,
        'auditable_id' => $criteria->id,
        'context' => '{}',
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    // Verify the old log was created
    expect(AuditLog::where('event', 'test_event')->count())->toBe(1);

    // Create a recent audit log
    $auditLogger->logEvaluation($criteria, [], []);

    $totalAfterSetup = AuditLog::count();
    expect($totalAfterSetup)->toBe($initialCount + 2);

    // Verify the old audit log exists and is old enough
    $cutoffDate = now()->subDays(365);
    $oldLogs = AuditLog::where('created_at', '<', $cutoffDate)->get();
    expect($oldLogs->count())->toBe(1);
    expect($oldLogs->first()->event)->toBe('test_event');

    // Run cleanup (should delete only the old audit log)
    $deletedCount = $auditLogger->cleanup();

    expect($deletedCount)->toBe(1);
    expect(AuditLog::count())->toBe($initialCount + 1);

    // Verify the remaining logs don't include the old test event
    expect(AuditLog::where('event', 'test_event')->count())->toBe(0);
});

it('respects sensitive data configuration', function () {
    config(['eligify.audit.include_sensitive_data' => false]);

    $criteria = Criteria::factory()->create();
    $sensitiveData = [
        'name' => 'John Doe',
        'password' => 'secret123',
        'ssn' => '123-45-6789',
    ];

    $this->auditLogger->logEvaluation($criteria, $sensitiveData, []);

    $evaluationAudit = AuditLog::where('event', 'evaluation_completed')->first();
    expect($evaluationAudit->context['input_data'])->toBe(['[REDACTED]']);
});

it('can export audit logs', function () {
    $criteria = Criteria::factory()->create();
    $this->auditLogger->logEvaluation($criteria, [], []);

    $export = $this->auditLogger->exportAudits();

    expect($export)->toBeArray();
    expect($export)->toHaveCount(2); // criteria_created + evaluation_completed
    expect($export[0])->toHaveKey('event');
    expect($export[0])->toHaveKey('created_at');

    // Verify both expected events are present
    $events = collect($export)->pluck('event')->toArray();
    expect($events)->toContain('criteria_created');
    expect($events)->toContain('evaluation_completed');
});
