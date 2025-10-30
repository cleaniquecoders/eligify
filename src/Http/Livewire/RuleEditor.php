<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\Eligify\Enums\FieldType;
use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Support\MappingRegistry;
use Livewire\Component;

class RuleEditor extends Component
{
    public string $mode = 'create'; // create|edit

    public ?int $criteriaId = null;

    public ?int $ruleId = null;

    public ?string $selectedMapping = null; // Selected mapping class

    public bool $useManualInput = false; // Toggle manual field input

    public string $field = '';

    public string $operator = '==';

    public ?string $fieldType = null;

    public ?string $priority = null;

    public $value = '';

    public int $weight = 1;

    public int $order = 0;

    public bool $is_active = true;

    public ?Rule $rule = null;

    public ?Criteria $criteria = null;

    public function mount(string $mode = 'create', ?int $criteriaId = null, ?int $ruleId = null): void
    {
        $this->mode = in_array($mode, ['create', 'edit']) ? $mode : 'create';
        $this->criteriaId = $criteriaId;
        $this->ruleId = $ruleId;

        if ($criteriaId) {
            $this->criteria = Criteria::query()->findOrFail($criteriaId);
        }

        if ($this->mode === 'edit' && $ruleId) {
            $this->rule = Rule::query()->findOrFail($ruleId);
            $this->criteriaId = $this->rule->criteria_id;
            $this->criteria = $this->rule->criteria;
            $this->field = (string) $this->rule->field;
            $this->operator = (string) $this->rule->operator;
            $this->value = is_array($this->rule->value) ? json_encode($this->rule->value) : (string) $this->rule->value;
            $this->weight = (int) $this->rule->weight;
            $this->order = (int) $this->rule->order;
            $this->is_active = (bool) $this->rule->is_active;

            // Load field_type and priority if stored in meta
            $this->fieldType = $this->rule->meta['field_type'] ?? null;
            $this->priority = $this->rule->meta['priority'] ?? null;
        }

        // Set default order for new rules
        if ($this->mode === 'create' && $this->criteria) {
            $this->order = $this->criteria->rules()->max('order') + 1 ?? 0;
        }
    }

    public function rules(): array
    {
        $operatorValues = array_map(fn ($op) => $op->value, RuleOperator::cases());

        return [
            'field' => ['required', 'string', 'max:255'],
            'operator' => ['required', 'string', 'in:'.implode(',', $operatorValues)],
            'fieldType' => ['nullable', 'string', 'in:'.implode(',', array_map(fn ($ft) => $ft->value, FieldType::cases()))],
            'priority' => ['nullable', 'string', 'in:'.implode(',', array_map(fn ($p) => $p->value, RulePriority::cases()))],
            'value' => ['required'],
            'weight' => ['required', 'integer', 'min:0', 'max:100'],
            'order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function save()
    {
        $this->validate();

        // Parse value - if it's JSON, decode it; otherwise, keep as is
        $parsedValue = $this->parseValue($this->value);

        $data = [
            'criteria_id' => $this->criteriaId,
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $parsedValue,
            'weight' => $this->weight,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
        ];

        // Store field_type and priority in meta if provided
        $meta = [];
        if ($this->fieldType) {
            $meta['field_type'] = $this->fieldType;
        }
        if ($this->priority) {
            $meta['priority'] = $this->priority;
        }
        if (! empty($meta)) {
            $data['meta'] = array_merge($this->rule->meta ?? [], $meta);
        }

        if ($this->mode === 'edit' && $this->rule) {
            $this->rule->update($data);
            session()->flash('status', 'Rule updated successfully.');

            return $this->redirect(route('eligify.criteria.show', $this->criteriaId));
        }

        Rule::create($data);
        session()->flash('status', 'Rule created successfully.');

        return $this->redirect(route('eligify.criteria.show', $this->criteriaId));
    }

    /**
     * Parse the value input - handle arrays, JSON, and scalar values
     */
    protected function parseValue($value)
    {
        // If it's already an array, return it
        if (is_array($value)) {
            return $value;
        }

        // Try to decode as JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Get the operator enum to check if it requires multiple values
        $operatorEnum = $this->getOperatorEnum();
        if ($operatorEnum && $operatorEnum->requiresMultipleValues()) {
            return array_map('trim', explode(',', $value));
        }

        // Return as scalar value
        return $value;
    }

    /**
     * Get the RuleOperator enum instance for current operator
     */
    protected function getOperatorEnum(): ?RuleOperator
    {
        return RuleOperator::tryFrom($this->operator);
    }

    /**
     * Get available operators based on field type
     */
    public function getAvailableOperatorsProperty(): array
    {
        // If field type is specified, return operators for that type
        if ($this->fieldType && $fieldTypeEnum = FieldType::tryFrom($this->fieldType)) {
            $operators = RuleOperator::forFieldType($fieldTypeEnum);

            return collect($operators)->mapWithKeys(function ($operator) {
                return [$operator->value => $operator->label()];
            })->toArray();
        }

        // Return all operators
        return collect(RuleOperator::cases())->mapWithKeys(function ($operator) {
            return [$operator->value => $operator->label()];
        })->toArray();
    }

    /**
     * Get all field types
     */
    public function getFieldTypesProperty(): array
    {
        return collect(FieldType::cases())->mapWithKeys(function ($type) {
            return [$type->value => $type->label()];
        })->toArray();
    }

    /**
     * Get all priorities
     */
    public function getPrioritiesProperty(): array
    {
        return collect(RulePriority::cases())->mapWithKeys(function ($priority) {
            return [$priority->value => $priority->label()];
        })->toArray();
    }

    /**
     * Update available operators when field type changes
     */
    public function updatedFieldType()
    {
        // Reset operator to first available for the new field type
        $availableOperators = $this->getAvailableOperatorsProperty();
        if (! empty($availableOperators) && ! isset($availableOperators[$this->operator])) {
            $this->operator = array_key_first($availableOperators);
        }
    }

    /**
     * Update weight when priority changes
     */
    public function updatedPriority()
    {
        if ($this->priority && $priorityEnum = RulePriority::tryFrom($this->priority)) {
            // Set weight based on priority, but allow manual override
            $this->weight = $priorityEnum->getWeight() * 10; // Scale to 0-100
        }
    }

    /**
     * Get the HTML input type based on field type
     */
    public function getValueInputTypeProperty(): string
    {
        if (! $this->fieldType) {
            return 'text';
        }

        $fieldTypeEnum = FieldType::tryFrom($this->fieldType);

        return match ($fieldTypeEnum) {
            FieldType::INTEGER, FieldType::NUMERIC => 'number',
            FieldType::DATE => 'date',
            FieldType::BOOLEAN => 'checkbox',
            FieldType::ARRAY, FieldType::STRING => 'text',
            default => 'text',
        };
    }

    /**
     * Get placeholder text based on field type and operator
     */
    public function getValuePlaceholderProperty(): string
    {
        if (! $this->fieldType) {
            return 'e.g., 1000 or [1000, 5000] for arrays';
        }

        $fieldTypeEnum = FieldType::tryFrom($this->fieldType);
        $operatorEnum = $this->getOperatorEnum();

        if ($operatorEnum && $operatorEnum->requiresMultipleValues()) {
            return match ($fieldTypeEnum) {
                FieldType::INTEGER, FieldType::NUMERIC => 'e.g., 100,200,300 or [100,200,300]',
                FieldType::STRING => 'e.g., active,pending,approved or ["active","pending"]',
                FieldType::DATE => 'e.g., 2024-01-01,2024-12-31',
                default => 'Enter comma-separated values or JSON array',
            };
        }

        return match ($fieldTypeEnum) {
            FieldType::INTEGER => 'e.g., 100',
            FieldType::NUMERIC => 'e.g., 99.99',
            FieldType::STRING => 'e.g., active',
            FieldType::BOOLEAN => 'true or false',
            FieldType::DATE => 'e.g., 2024-01-01',
            FieldType::ARRAY => 'e.g., ["value1","value2"] or value1,value2',
            default => 'Enter value',
        };
    }

    /**
     * Get help text for the value input
     */
    public function getValueHelpTextProperty(): string
    {
        if (! $this->fieldType) {
            return 'Single value (e.g., 1000) or array for \'in\'/\'between\' operators (e.g., 100,200 or ["active","pending"])';
        }

        $fieldTypeEnum = FieldType::tryFrom($this->fieldType);
        $operatorEnum = $this->getOperatorEnum();

        if ($operatorEnum && $operatorEnum->requiresMultipleValues()) {
            return 'Enter multiple values as comma-separated (e.g., 100,200,300) or JSON array';
        }

        return match ($fieldTypeEnum) {
            FieldType::INTEGER => 'Enter a whole number (no decimals)',
            FieldType::NUMERIC => 'Enter a numeric value (decimals allowed)',
            FieldType::STRING => 'Enter text value',
            FieldType::BOOLEAN => 'Enter true/false or 1/0',
            FieldType::DATE => 'Enter date in YYYY-MM-DD format',
            FieldType::ARRAY => 'Enter values as comma-separated or JSON array',
            default => 'Enter the value to compare against',
        };
    }

    /**
     * Check if the current field type should use a boolean input
     */
    public function getIsBooleanInputProperty(): bool
    {
        if (! $this->fieldType) {
            return false;
        }

        $fieldTypeEnum = FieldType::tryFrom($this->fieldType);

        return $fieldTypeEnum === FieldType::BOOLEAN;
    }

    /**
     * Check if operator requires multiple values
     */
    public function getRequiresMultipleValuesProperty(): bool
    {
        $operatorEnum = $this->getOperatorEnum();

        return $operatorEnum && $operatorEnum->requiresMultipleValues();
    }

    /**
     * Get step attribute for numeric inputs
     */
    public function getNumericStepProperty(): string
    {
        if (! $this->fieldType) {
            return 'any';
        }

        $fieldTypeEnum = FieldType::tryFrom($this->fieldType);

        return match ($fieldTypeEnum) {
            FieldType::INTEGER => '1',
            FieldType::NUMERIC => '0.01',
            default => 'any',
        };
    }

    /**
     * Get all available mapping classes with metadata
     */
    public function getMappingClassesProperty(): array
    {
        return MappingRegistry::all();
    }

    /**
     * Get available fields for the selected mapping
     */
    public function getAvailableFieldsProperty(): array
    {
        if (! $this->selectedMapping) {
            return [];
        }

        return MappingRegistry::getFields($this->selectedMapping);
    }

    /**
     * Handle when mapping selection changes
     */
    public function updatedSelectedMapping()
    {
        // Reset field when mapping changes
        $this->field = '';
        $this->fieldType = null;

        // If a mapping is selected, disable manual input
        $this->useManualInput = false;
    }

    /**
     * Handle when field selection changes
     */
    public function updatedField()
    {
        // Auto-populate field type from mapping metadata if available
        if ($this->selectedMapping && ! $this->useManualInput) {
            $fields = $this->getAvailableFieldsProperty();
            if (isset($fields[$this->field])) {
                $fieldMeta = $fields[$this->field];
                $this->fieldType = $fieldMeta['type'] ?? null;

                // Reset operator to first available for the new field type
                $this->updatedFieldType();
            }
        }
    }

    /**
     * Toggle manual input mode
     */
    public function toggleManualInput()
    {
        $this->useManualInput = ! $this->useManualInput;

        if ($this->useManualInput) {
            // Clear mapping selection when switching to manual
            $this->selectedMapping = null;
            $this->field = '';
            $this->fieldType = null;
        } else {
            // Clear field when switching back to mapping mode
            $this->field = '';
            $this->fieldType = null;
        }
    }

    public function render()
    {
        return view('eligify::livewire.rule-editor');
    }
}
