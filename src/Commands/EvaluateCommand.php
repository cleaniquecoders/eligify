<?php

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Data\Extractor;
use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Console\Command;

class EvaluateCommand extends Command
{
    public $signature = 'eligify:evaluate
                        {criteria : Criteria name or slug to evaluate against}
                        {--data= : JSON data to evaluate (inline)}
                        {--file= : File containing data to evaluate (JSON)}
                        {--model= : Model class and ID (e.g., "App\Models\User:123")}
                        {--batch : Evaluate multiple data sets from file}
                        {--format=table : Output format (table, json, detailed)}
                        {--save : Save evaluation results to database}
                        {--callbacks : Execute configured callbacks}
                        {--verbose-output : Show detailed output}';

    public $description = 'Run eligibility evaluations from command line';

    public function handle(): int
    {
        $criteriaName = $this->argument('criteria');

        // Find criteria
        $criteria = Criteria::where('name', $criteriaName)
            ->orWhere('slug', $criteriaName)
            ->first();

        if (! $criteria) {
            $this->error("Criteria '{$criteriaName}' not found.");

            return self::FAILURE;
        }

        if (! $criteria->is_active) {
            $this->warn("Warning: Criteria '{$criteria->name}' is inactive.");
        }

        // Get data to evaluate
        $data = $this->getData();
        if ($data === null) {
            return self::FAILURE;
        }

        if ($this->option('batch')) {
            return $this->evaluateBatch($criteria, $data);
        } else {
            return $this->evaluateSingle($criteria, $data);
        }
    }

    protected function getData()
    {
        // Check for model option
        if ($model = $this->option('model')) {
            return $this->getModelData($model);
        }

        // Check for inline data
        if ($jsonData = $this->option('data')) {
            return $this->parseJsonData($jsonData);
        }

        // Check for file data
        if ($file = $this->option('file')) {
            return $this->getFileData($file);
        }

        // Interactive mode
        return $this->getInteractiveData();
    }

    protected function getModelData(string $modelSpec): ?array
    {
        if (! str_contains($modelSpec, ':')) {
            $this->error('Model format should be "App\\Models\\ModelName:id"');

            return null;
        }

        [$modelClass, $id] = explode(':', $modelSpec, 2);

        if (! class_exists($modelClass)) {
            $this->error("Model class '{$modelClass}' not found.");

            return null;
        }

        try {
            $model = $modelClass::findOrFail($id);
            $extractor = new Extractor;
            $snapshot = $extractor->extract($model);

            if ($this->option('verbose-output')) {
                $this->info("Extracted data from {$modelClass}#{$id}:");
                $this->line(json_encode($snapshot->toArray(), JSON_PRETTY_PRINT));
                $this->newLine();
            }

            return $snapshot->toArray();
        } catch (\Exception $e) {
            $this->error("Failed to load model: {$e->getMessage()}");

            return null;
        }
    }

    protected function parseJsonData(string $jsonData): ?array
    {
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON data: '.json_last_error_msg());

            return null;
        }

        return $data;
    }

    protected function getFileData(string $file): ?array
    {
        if (! file_exists($file)) {
            $this->error("File '{$file}' not found.");

            return null;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON in file: '.json_last_error_msg());

            return null;
        }

        return $data;
    }

    protected function getInteractiveData(): array
    {
        $this->info('📝 Interactive Data Entry');
        $this->line('Enter data as key-value pairs (press enter with empty key to finish):');
        $this->newLine();

        $data = [];

        while (true) {
            $key = $this->ask('Field name');
            if (! $key) {
                break;
            }

            $value = $this->ask('Value');
            $data[$key] = $this->parseValue($value);

            $this->line("✅ Added: {$key} = ".$this->formatValue($data[$key]));
        }

        return $data;
    }

    protected function evaluateSingle(Criteria $criteria, array $data): int
    {
        $this->info("🎯 Evaluating against criteria: {$criteria->name}");
        if ($this->option('verbose-output')) {
            $this->line("Criteria UUID: {$criteria->uuid}");
            $this->line("Rules count: {$criteria->rules->count()}");
        }
        $this->newLine();

        try {
            $eligify = app(Eligify::class);

            // Run evaluation
            if ($this->option('callbacks')) {
                // For callbacks, we need to use a CriteriaBuilder
                $builder = Eligify::criteria($criteria->name);
                $result = $eligify->evaluateWithCallbacks($builder, $data);
            } else {
                $result = $eligify->evaluate($criteria, $data, $this->option('save', true));
            }

            // Display results
            $this->displayResult($result, $data);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Evaluation failed: {$e->getMessage()}");

            if ($this->option('verbose-output')) {
                $this->line("Stack trace: {$e->getTraceAsString()}");
            }

            return self::FAILURE;
        }
    }

    protected function evaluateBatch(Criteria $criteria, array $dataCollection): int
    {
        if (! isset($dataCollection[0]) || ! is_array($dataCollection[0])) {
            $this->error('Batch data should be an array of data objects.');

            return self::FAILURE;
        }

        $count = count($dataCollection);
        $this->info("🎯 Batch evaluating {$count} records against: {$criteria->name}");
        $this->newLine();

        try {
            $eligify = app(Eligify::class);

            if ($this->option('callbacks')) {
                // For callbacks, we need to use a CriteriaBuilder
                $builder = Eligify::criteria($criteria->name);
                $results = $eligify->evaluateBatchWithCallbacks($builder, $dataCollection);
            } else {
                $results = $eligify->evaluateBatch($criteria, $dataCollection, $this->option('save', true));
            }

            // Display batch results
            $this->displayBatchResults($results);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Batch evaluation failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function displayResult(array $result, array $data): void
    {
        $format = $this->option('format');

        if ($format === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            return;
        }

        // Status
        $status = $result['passed'] ? '✅ PASSED' : '❌ FAILED';
        $this->line("<info>Result:</info> {$status}");
        $this->line("<info>Score:</info> {$result['score']}");
        $this->line("<info>Decision:</info> {$result['decision']}");
        $this->newLine();

        // Failed rules
        if (! empty($result['failed_rules'])) {
            $this->warn('❌ Failed Rules:');
            foreach ($result['failed_rules'] as $rule) {
                $this->line("   • {$rule['field']} {$rule['operator']} {$this->formatValue($rule['expected'])} (got: {$this->formatValue($rule['actual'])})");
            }
            $this->newLine();
        }

        // Detailed format
        if ($format === 'detailed' || $this->option('verbose-output')) {
            $this->info('📊 Detailed Information:');

            // Show input data
            $this->line('<comment>Input Data:</comment>');
            foreach ($data as $key => $value) {
                $this->line("   {$key}: {$this->formatValue($value)}");
            }
            $this->newLine();

            // Show all rule results if available
            if (isset($result['rule_results'])) {
                $this->line('<comment>Rule Results:</comment>');
                foreach ($result['rule_results'] as $ruleResult) {
                    $status = $ruleResult['passed'] ? '✅' : '❌';
                    $this->line("   {$status} {$ruleResult['field']} {$ruleResult['operator']} {$this->formatValue($ruleResult['expected'])}");
                }
            }
        }
    }

    protected function displayBatchResults(array $results): void
    {
        $passed = collect($results)->where('passed', true)->count();
        $failed = count($results) - $passed;
        $successRate = count($results) > 0 ? ($passed / count($results)) * 100 : 0;

        $this->info('📊 Batch Results Summary:');
        $this->line('   Total: '.count($results));
        $this->line("   Passed: {$passed}");
        $this->line("   Failed: {$failed}");
        $this->line('   Success Rate: '.number_format($successRate, 2).'%');
        $this->newLine();

        $format = $this->option('format');

        if ($format === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

            return;
        }

        // Show summary table
        $tableData = [];
        $counter = 1;
        foreach ($results as $index => $result) {
            $tableData[] = [
                $counter++,
                $result['passed'] ? '✅ PASSED' : '❌ FAILED',
                $result['score'],
                $result['decision'],
                isset($result['error']) ? $result['error'] : '',
            ];
        }

        $this->table(
            ['#', 'Result', 'Score', 'Decision', 'Error'],
            $tableData
        );

        // Show failed cases details if verbose
        if ($this->option('verbose-output') && $failed > 0) {
            $this->newLine();
            $this->warn('❌ Failed Cases Details:');

            foreach ($results as $index => $result) {
                if (! $result['passed']) {
                    $caseNumber = $index + 1;
                    $this->line("Case #{$caseNumber}:");
                    foreach ($result['failed_rules'] ?? [] as $rule) {
                        $this->line("   • {$rule['field']} {$rule['operator']} {$this->formatValue($rule['expected'])} (got: {$this->formatValue($rule['actual'])})");
                    }
                    $this->newLine();
                }
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

        if (is_null($value)) {
            return 'null';
        }

        return (string) $value;
    }
}
