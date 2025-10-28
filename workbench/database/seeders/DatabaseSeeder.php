<?php

namespace Workbench\Database\Seeders;

use Carbon\CarbonImmutable;
use CleaniqueCoders\Eligify\Models\AuditLog;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Evaluation;
use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // UserFactory::new()->times(10)->create();

        UserFactory::new()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed minimal Eligify data for dashboard demo
        $criteria = Criteria::query()->create([
            'name' => 'Demo Approval',
            'slug' => 'demo_approval',
            'description' => 'Demo criteria for workbench',
            'is_active' => true,
            'meta' => [],
        ]);

        Rule::query()->create([
            'criteria_id' => $criteria->id,
            'field' => 'applicant.income',
            'operator' => '>=',
            'value' => [3000],
            'weight' => 50,
            'order' => 1,
            'is_active' => true,
            'meta' => [],
        ]);

        Evaluation::query()->create([
            'criteria_id' => $criteria->id,
            'evaluable_type' => 'App\\Models\\User',
            'evaluable_id' => 1,
            'slug' => 'demo_eval',
            'passed' => true,
            'score' => 85.5,
            'failed_rules' => [],
            'rule_results' => [],
            'decision' => 'Approved',
            'context' => [],
            'meta' => [],
            'evaluated_at' => CarbonImmutable::now(),
        ]);

        AuditLog::query()->create([
            'event' => 'criteria_created',
            'auditable_type' => Criteria::class,
            'auditable_id' => $criteria->id,
            'slug' => 'criteria_created_'.$criteria->id,
            'old_values' => null,
            'new_values' => ['name' => $criteria->name],
            'context' => [],
            'user_type' => null,
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'workbench',
            'meta' => [],
        ]);
    }
}
