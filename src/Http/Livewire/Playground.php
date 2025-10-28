<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use Livewire\Component;

class Playground extends Component
{
    public ?int $selectedCriteriaId = null;

    public ?Criteria $selectedCriteria = null;

    public string $testDataJson = '';

    public ?array $evaluationResult = null;

    public ?string $error = null;

    public bool $showExamples = false;

    public array $quickExamples = [];

    protected $queryString = ['selectedCriteriaId'];

    public function mount(?int $criteriaId = null): void
    {
        // Only load criteria if explicitly provided via URL
        if ($criteriaId) {
            $this->selectedCriteriaId = $criteriaId;
            $this->loadCriteria();
        } else {
            // Start with nothing selected
            $this->selectedCriteriaId = null;
            $this->selectedCriteria = null;
            $this->testDataJson = '';
        }

        $this->initializeQuickExamples();
    }

    public function loadCriteria(): void
    {
        if (! $this->selectedCriteriaId) {
            $this->selectedCriteria = null;
            $this->testDataJson = '';
            $this->evaluationResult = null;
            $this->error = null;

            return;
        }

        try {
            $this->selectedCriteria = Criteria::query()
                ->with(['rules' => fn ($q) => $q->active()->ordered()])
                ->findOrFail($this->selectedCriteriaId);

            // Auto-generate sample data based on rules
            $this->testDataJson = $this->generateSampleData();
            $this->error = null;

            $this->js('$wire.$refresh()');
        } catch (\Exception $e) {
            $this->error = 'Criteria not found: '.$e->getMessage();
            $this->selectedCriteria = null;
        }
    }

    public function updatedSelectedCriteriaId(): void
    {
        $this->loadCriteria();
        $this->evaluationResult = null;
    }

    public function evaluate(): void
    {
        $this->error = null;
        $this->evaluationResult = null;

        if (! $this->selectedCriteria) {
            $this->error = 'Please select a criteria first.';

            return;
        }

        if (empty(trim($this->testDataJson))) {
            $this->error = 'Please provide test data in JSON format.';

            return;
        }

        try {
            $data = json_decode($this->testDataJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error = 'Invalid JSON: '.json_last_error_msg();

                return;
            }

            // Evaluate without saving to database
            $eligify = app(Eligify::class);
            $this->evaluationResult = $eligify->evaluate(
                $this->selectedCriteria,
                $data,
                saveEvaluation: false
            );
        } catch (\Exception $e) {
            $this->error = 'Evaluation error: '.$e->getMessage();
        }
    }

    public function resetPlayground(): void
    {
        $this->selectedCriteriaId = null;
        $this->selectedCriteria = null;
        $this->testDataJson = '';
        $this->evaluationResult = null;
        $this->error = null;
    }

    public function loadExample(string $key): void
    {
        if (isset($this->quickExamples[$key])) {
            $this->testDataJson = json_encode($this->quickExamples[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $this->showExamples = false;
        }
    }

    public function formatJson(): void
    {
        try {
            $data = json_decode($this->testDataJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->testDataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        } catch (\Exception $e) {
            // Silently fail - user is still typing
        }
    }

    public function generateFromRules(): void
    {
        if (! $this->selectedCriteria) {
            $this->error = 'Please select a criteria first.';

            return;
        }

        if ($this->selectedCriteria->rules->isEmpty()) {
            $this->error = 'No rules found for this criteria.';

            return;
        }

        try {
            $this->testDataJson = $this->generateSampleData();
            $this->error = null;

            $this->js('$wire.$refresh()');
        } catch (\Exception $e) {
            $this->error = 'Failed to generate sample data: '.$e->getMessage();
        }
    }

    public function generateSampleData(): string
    {
        if (! $this->selectedCriteria || $this->selectedCriteria->rules->isEmpty()) {
            return json_encode([
                'example_field' => 'example_value',
            ], JSON_PRETTY_PRINT);
        }

        $sampleData = [];

        foreach ($this->selectedCriteria->rules as $rule) {
            $field = $rule->field;
            $value = $this->generateSampleValue($rule);

            // Handle dot notation for nested objects
            if (str_contains($field, '.')) {
                $this->setNestedValue($sampleData, $field, $value);
            } else {
                $sampleData[$field] = $value;
            }
        }

        return json_encode($sampleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Set a value in a nested array using dot notation
     */
    protected function setNestedValue(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    protected function generateSampleValue($rule)
    {
        $operator = $rule->operator;
        $ruleValue = $rule->value;
        $fieldType = $rule->meta['field_type'] ?? null;

        // Generate based on operator and expected value
        return match ($operator) {
            '==', '=' => $ruleValue,
            '!=', '<>' => is_numeric($ruleValue) ? $ruleValue + 1 : 'different_value',
            '>', '>=' => is_numeric($ruleValue) ? $ruleValue + 10 : $ruleValue,
            '<', '<=' => is_numeric($ruleValue) ? $ruleValue - 10 : $ruleValue,
            'in' => is_array($ruleValue) && ! empty($ruleValue) ? $ruleValue[0] : 'value',
            'not_in' => 'other_value',
            'contains' => $ruleValue,
            'starts_with' => $ruleValue.'_example',
            'ends_with' => 'example_'.$ruleValue,
            'between' => is_array($ruleValue) && count($ruleValue) >= 2
                ? ($ruleValue[0] + $ruleValue[1]) / 2
                : 50,
            'exists' => 'some_value',
            'not_exists' => null,
            default => $ruleValue,
        };
    }

    protected function initializeQuickExamples(): void
    {
        $this->quickExamples = [
            'numeric' => [
                'age' => 25,
                'income' => 5000,
                'credit_score' => 720,
            ],
            'string' => [
                'status' => 'active',
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'boolean' => [
                'is_verified' => true,
                'is_active' => true,
                'has_consent' => false,
            ],
            'mixed' => [
                'age' => 30,
                'status' => 'active',
                'is_verified' => true,
                'income' => 75000,
                'credit_score' => 750,
                'employment_status' => 'employed',
            ],
        ];
    }

    public function getAvailableCriteriaProperty()
    {
        return Criteria::query()
            ->where('is_active', true)
            ->withCount('rules')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('eligify::livewire.playground');
    }
}
