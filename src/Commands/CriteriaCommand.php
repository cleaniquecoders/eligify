<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CriteriaCommand extends Command
{
    public $signature = 'eligify:criteria
                        {action : Action to perform (list, create, show, edit, delete, export, import)}
                        {criteria? : Criteria name or slug for show/edit/delete actions}
                        {--format=table : Output format (table, json, csv)}
                        {--active : Filter only active criteria}
                        {--inactive : Filter only inactive criteria}
                        {--file= : File path for export/import operations}
                        {--force : Force operations without confirmation}';

    public $description = 'Manage eligibility criteria and rules';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listCriteria(),
            'create' => $this->createCriteria(),
            'show' => $this->showCriteria(),
            'edit' => $this->editCriteria(),
            'delete' => $this->deleteCriteria(),
            'export' => $this->exportCriteria(),
            'import' => $this->importCriteria(),
            default => $this->showHelp(),
        };
    }

    protected function listCriteria(): int
    {
        $this->info('📋 Eligibility Criteria');
        $this->newLine();

        $query = Criteria::with('rules');

        // Apply filters
        if ($this->option('active')) {
            $query->where('is_active', true);
        } elseif ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        $criteria = $query->orderBy('created_at', 'desc')->get();

        if ($criteria->isEmpty()) {
            $this->warn('No criteria found.');

            return self::SUCCESS;
        }

        $format = $this->option('format');

        if ($format === 'json') {
            $this->line($criteria->toJson(JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $data = $criteria->map(function ($criterion) {
            return [
                $criterion->name,
                $criterion->slug,
                $criterion->rules->count().' rules',
                $criterion->is_active ? '✅ Active' : '❌ Inactive',
                $criterion->created_at->format('Y-m-d H:i'),
                Str::limit($criterion->description ?? '', 50),
            ];
        })->toArray();

        $this->table(
            ['Name', 'Slug', 'Rules', 'Status', 'Created', 'Description'],
            $data
        );

        return self::SUCCESS;
    }

    protected function createCriteria(): int
    {
        $this->info('🆕 Create New Criteria');
        $this->newLine();

        // Get criteria details
        $name = $this->ask('Criteria name');
        if (! $name) {
            $this->error('Criteria name is required.');

            return self::FAILURE;
        }

        $description = $this->ask('Description (optional)');
        $threshold = $this->ask('Pass threshold (0-100)', '65');

        // Create criteria
        try {
            $builder = Eligify::criteria($name);

            if ($description) {
                $builder->description($description);
            }

            if (is_numeric($threshold)) {
                $builder->passThreshold((int) $threshold);
            }

            $this->info("Creating criteria: {$name}");
            $this->newLine();

            // Add rules interactively
            $this->addRulesInteractively($builder);

            // Save criteria
            $builder->save();
            $criteria = $builder->getCriteria();

            $this->info("✅ Criteria '{$name}' created successfully!");
            $this->line("   UUID: {$criteria->uuid}");
            $this->line("   Slug: {$criteria->slug}");
            $this->line("   Rules: {$criteria->rules->count()}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create criteria: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function showCriteria(): int
    {
        $criteriaName = $this->argument('criteria');
        if (! $criteriaName) {
            $this->error('Please provide a criteria name or slug.');

            return self::FAILURE;
        }

        $criteria = $this->findCriteria($criteriaName);
        if (! $criteria) {
            return self::FAILURE;
        }

        $this->info("📋 Criteria: {$criteria->name}");
        $this->newLine();

        // Basic info
        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $criteria->name],
                ['Slug', $criteria->slug],
                ['UUID', $criteria->uuid],
                ['Description', $criteria->description ?? 'None'],
                ['Status', $criteria->is_active ? '✅ Active' : '❌ Inactive'],
                ['Pass Threshold', $criteria->meta['pass_threshold'] ?? 'Default (65)'],
                ['Created', $criteria->created_at->format('Y-m-d H:i:s')],
                ['Updated', $criteria->updated_at->format('Y-m-d H:i:s')],
            ]
        );

        // Rules
        if ($criteria->rules->isNotEmpty()) {
            $this->newLine();
            $this->info('📏 Rules:');

            $rulesData = $criteria->rules->map(function ($rule) {
                return [
                    $rule->field,
                    is_string($rule->operator) ? $rule->operator : $rule->operator->value,
                    $this->formatValue($rule->value),
                    $rule->weight,
                    $rule->is_active ? '✅' : '❌',
                ];
            })->toArray();

            $this->table(
                ['Field', 'Operator', 'Value', 'Weight', 'Active'],
                $rulesData
            );
        } else {
            $this->newLine();
            $this->warn('No rules defined for this criteria.');
        }

        // Usage statistics
        $evaluationCount = \CleaniqueCoders\Eligify\Models\Evaluation::where('criteria_uuid', $criteria->uuid)->count();
        $successCount = \CleaniqueCoders\Eligify\Models\Evaluation::where('criteria_uuid', $criteria->uuid)
            ->where('passed', true)->count();

        if ($evaluationCount > 0) {
            $successRate = ($successCount / $evaluationCount) * 100;
            $this->newLine();
            $this->info('📊 Usage Statistics:');
            $this->line("   Total Evaluations: {$evaluationCount}");
            $this->line("   Successful: {$successCount}");
            $this->line('   Success Rate: '.number_format($successRate, 2).'%');
        }

        return self::SUCCESS;
    }

    protected function editCriteria(): int
    {
        $criteriaName = $this->argument('criteria');
        if (! $criteriaName) {
            $this->error('Please provide a criteria name or slug.');

            return self::FAILURE;
        }

        $criteria = $this->findCriteria($criteriaName);
        if (! $criteria) {
            return self::FAILURE;
        }

        $this->info("✏️  Editing Criteria: {$criteria->name}");
        $this->newLine();

        // Edit basic properties
        $newName = $this->ask('Name', $criteria->name);
        $newDescription = $this->ask('Description', $criteria->description);
        $newThreshold = $this->ask('Pass threshold', $criteria->meta['pass_threshold'] ?? 65);
        $newStatus = $this->confirm('Active?', $criteria->is_active);

        // Update criteria
        $criteria->update([
            'name' => $newName,
            'description' => $newDescription,
            'is_active' => $newStatus,
            'meta' => array_merge($criteria->meta ?? [], [
                'pass_threshold' => (int) $newThreshold,
            ]),
        ]);

        // Edit rules
        if ($this->confirm('Do you want to edit rules?', false)) {
            $this->editRules($criteria);
        }

        $this->info("✅ Criteria '{$criteria->name}' updated successfully!");

        return self::SUCCESS;
    }

    protected function deleteCriteria(): int
    {
        $criteriaName = $this->argument('criteria');
        if (! $criteriaName) {
            $this->error('Please provide a criteria name or slug.');

            return self::FAILURE;
        }

        $criteria = $this->findCriteria($criteriaName);
        if (! $criteria) {
            return self::FAILURE;
        }

        // Check for evaluations
        $evaluationCount = \CleaniqueCoders\Eligify\Models\Evaluation::where('criteria_uuid', $criteria->uuid)->count();

        if ($evaluationCount > 0 && ! $this->option('force')) {
            $this->warn("This criteria has {$evaluationCount} evaluations associated with it.");
            if (! $this->confirm('Are you sure you want to delete it? This will also delete all associated data.')) {
                $this->info('Deletion cancelled.');

                return self::SUCCESS;
            }
        }

        // Delete criteria (will cascade to rules)
        $criteria->delete();

        $this->info("✅ Criteria '{$criteria->name}' deleted successfully!");

        return self::SUCCESS;
    }

    protected function exportCriteria(): int
    {
        $file = $this->option('file') ?? 'criteria_export.json';

        $query = Criteria::with('rules');

        // Apply filters
        if ($this->option('active')) {
            $query->where('is_active', true);
        } elseif ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        $criteria = $query->get();

        $exportData = [
            'exported_at' => now()->toISOString(),
            'version' => '1.0',
            'criteria' => $criteria->map(function ($criterion) {
                return [
                    'name' => $criterion->name,
                    'slug' => $criterion->slug,
                    'description' => $criterion->description,
                    'is_active' => $criterion->is_active,
                    'meta' => $criterion->meta,
                    'rules' => $criterion->rules->map(function ($rule) {
                        return [
                            'field' => $rule->field,
                            'operator' => is_string($rule->operator) ? $rule->operator : $rule->operator->value,
                            'value' => $rule->value,
                            'weight' => $rule->weight,
                            'order' => $rule->order,
                            'is_active' => $rule->is_active,
                        ];
                    }),
                ];
            }),
        ];

        file_put_contents($file, json_encode($exportData, JSON_PRETTY_PRINT));

        $this->info("✅ Exported {$criteria->count()} criteria to {$file}");

        return self::SUCCESS;
    }

    protected function importCriteria(): int
    {
        $file = $this->option('file');
        if (! $file || ! file_exists($file)) {
            $this->error('Please provide a valid file path using --file option.');

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);
        if (! $data || ! isset($data['criteria'])) {
            $this->error('Invalid import file format.');

            return self::FAILURE;
        }

        $this->info("Importing criteria from {$file}...");
        $imported = 0;
        $skipped = 0;

        foreach ($data['criteria'] as $criteriaData) {
            try {
                // Check if criteria already exists
                $existing = Criteria::where('slug', $criteriaData['slug'])->first();
                if ($existing && ! $this->option('force')) {
                    $this->warn("Skipping '{$criteriaData['name']}' - already exists");
                    $skipped++;

                    continue;
                }

                // Create or update criteria
                $builder = Eligify::criteria($criteriaData['name']);
                $builder->description($criteriaData['description'] ?? '')
                    ->active($criteriaData['is_active'] ?? true);

                if (isset($criteriaData['meta']['pass_threshold'])) {
                    $builder->passThreshold($criteriaData['meta']['pass_threshold']);
                }

                // Add rules
                foreach ($criteriaData['rules'] as $ruleData) {
                    $builder->addRule(
                        $ruleData['field'],
                        $ruleData['operator'],
                        $ruleData['value'],
                        $ruleData['weight'] ?? null
                    );
                }

                $builder->save();
                $imported++;
                $this->line("✅ Imported: {$criteriaData['name']}");

            } catch (\Exception $e) {
                $this->error("Failed to import '{$criteriaData['name']}': {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Import completed: {$imported} imported, {$skipped} skipped.");

        return self::SUCCESS;
    }

    protected function findCriteria(string $nameOrSlug): ?Criteria
    {
        $criteria = Criteria::where('name', $nameOrSlug)
            ->orWhere('slug', $nameOrSlug)
            ->first();

        if (! $criteria) {
            $this->error("Criteria '{$nameOrSlug}' not found.");

            return null;
        }

        return $criteria;
    }

    protected function addRulesInteractively($builder): void
    {
        $this->info('Add rules to your criteria (press enter with empty field to finish):');
        $this->newLine();

        while (true) {
            $field = $this->ask('Field name');
            if (! $field) {
                break;
            }

            // Show available operators
            $operators = collect(RuleOperator::cases())->pluck('value')->toArray();
            $operator = $this->choice('Operator', $operators, '>=');

            $value = $this->ask('Value');
            $weight = $this->ask('Weight (optional)', null);
            $priority = $this->choice('Priority', ['low', 'medium', 'high', 'critical'], 'medium');

            try {
                $builder->addRule(
                    $field,
                    $operator,
                    $this->parseValue($value),
                    $weight ? (int) $weight : null,
                    RulePriority::from($priority)
                );
                $this->line("✅ Added rule: {$field} {$operator} {$value}");
            } catch (\Exception $e) {
                $this->error("Failed to add rule: {$e->getMessage()}");
            }

            $this->newLine();
        }
    }

    protected function editRules(Criteria $criteria): void
    {
        if ($criteria->rules->isEmpty()) {
            $this->warn('No rules to edit.');

            return;
        }

        foreach ($criteria->rules as $rule) {
            $this->info("Editing rule: {$rule->field} {$rule->operator->value} ".$this->formatValue($rule->value));

            if ($this->confirm('Delete this rule?', false)) {
                $rule->delete();
                $this->line('✅ Rule deleted');

                continue;
            }

            if ($this->confirm('Edit this rule?', false)) {
                $newField = $this->ask('Field', $rule->field);
                $operators = collect(RuleOperator::cases())->pluck('value')->toArray();
                $newOperator = $this->choice('Operator', $operators, $rule->operator->value);
                $newValue = $this->ask('Value', $this->formatValue($rule->value));
                $newWeight = $this->ask('Weight', $rule->weight);

                $rule->update([
                    'field' => $newField,
                    'operator' => RuleOperator::from($newOperator),
                    'value' => $this->parseValue($newValue),
                    'weight' => (int) $newWeight,
                ]);

                $this->line('✅ Rule updated');
            }
        }
    }

    protected function parseValue($value)
    {
        // Try to parse as JSON first
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Try to parse as numeric
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Try to parse as boolean
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }

        // Return as string
        return $value;
    }

    protected function formatValue($value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    protected function showHelp(): int
    {
        $this->info('📋 Criteria Management Commands');
        $this->newLine();

        $this->line('<info>Available actions:</info>');
        $this->line('  <comment>list</comment>      List all criteria');
        $this->line('  <comment>create</comment>    Create new criteria interactively');
        $this->line('  <comment>show</comment>      Show detailed info about a criteria');
        $this->line('  <comment>edit</comment>      Edit existing criteria');
        $this->line('  <comment>delete</comment>    Delete criteria');
        $this->line('  <comment>export</comment>    Export criteria to JSON file');
        $this->line('  <comment>import</comment>    Import criteria from JSON file');
        $this->newLine();

        $this->line('<info>Options:</info>');
        $this->line('  <comment>--format=table|json|csv</comment>  Output format for list command');
        $this->line('  <comment>--active</comment>                Filter only active criteria');
        $this->line('  <comment>--inactive</comment>              Filter only inactive criteria');
        $this->line('  <comment>--file=path</comment>             File path for export/import');
        $this->line('  <comment>--force</comment>                 Force operations without confirmation');

        return self::SUCCESS;
    }
}
