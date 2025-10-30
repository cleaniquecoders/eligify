# Input Validation

Validate all inputs to ensure security and data integrity.

## Overview

Proper input validation prevents security vulnerabilities and ensures data quality in your eligibility system.

## Rule Input Validation

### Validate Rule Structure

```php
use Illuminate\Support\Facades\Validator;

class RuleValidator
{
    public static function validate(array $rule): array
    {
        $validator = Validator::make($rule, [
            'field' => 'required|string|max:255',
            'operator' => 'required|string|in:' . implode(',', self::allowedOperators()),
            'value' => 'required',
            'weight' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException(
                'Invalid rule structure: ' . $validator->errors()->first()
            );
        }

        return $validator->validated();
    }

    protected static function allowedOperators(): array
    {
        return ['==', '!=', '>', '>=', '<', '<=', 'in', 'not_in', 'between', 'contains'];
    }
}
```

### Sanitize Field Names

```php
class FieldSanitizer
{
    public static function sanitize(string $field): string
    {
        // Remove potentially dangerous characters
        $field = preg_replace('/[^a-zA-Z0-9._-]/', '', $field);

        // Prevent SQL injection in dynamic queries
        $field = str_replace(['--', ';', '/*', '*/'], '', $field);

        // Limit length
        $field = substr($field, 0, 255);

        return $field;
    }

    public static function validate(string $field): bool
    {
        // Must start with letter or underscore
        if (!preg_match('/^[a-zA-Z_]/', $field)) {
            return false;
        }

        // Can only contain alphanumeric, dots, underscores, hyphens
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $field)) {
            return false;
        }

        // Prevent traversal attacks
        if (str_contains($field, '..')) {
            return false;
        }

        return true;
    }
}
```

### Validate Operator Values

```php
class ValueValidator
{
    public static function validateForOperator(mixed $value, string $operator): bool
    {
        return match($operator) {
            'in', 'not_in' => is_array($value) && count($value) > 0,
            'between' => is_array($value) && count($value) === 2 && $value[0] < $value[1],
            'contains' => is_string($value) || is_numeric($value),
            '==', '!=', '>', '>=', '<', '<=' => is_scalar($value),
            default => false,
        };
    }

    public static function sanitizeValue(mixed $value, string $operator): mixed
    {
        return match($operator) {
            'in', 'not_in' => array_map(fn($v) => self::sanitizeScalar($v), $value),
            'between' => [
                self::sanitizeScalar($value[0]),
                self::sanitizeScalar($value[1]),
            ],
            default => self::sanitizeScalar($value),
        };
    }

    protected static function sanitizeScalar(mixed $value): mixed
    {
        if (is_string($value)) {
            // Remove null bytes
            $value = str_replace("\0", '', $value);

            // Trim whitespace
            $value = trim($value);

            // Limit length
            $value = substr($value, 0, 1000);
        }

        return $value;
    }
}
```

## Request Validation

### Evaluation Request

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('evaluate-eligibility');
    }

    public function rules(): array
    {
        return [
            'criteria' => 'required|string|max:255|alpha_dash',
            'entity_type' => 'required|string|max:255',
            'entity_id' => 'required|integer|min:1',
            'context' => 'nullable|string|max:255',
        ];
    }

    public function prepareForValidation(): void
    {
        // Sanitize inputs before validation
        $this->merge([
            'criteria' => $this->sanitizeCriteria($this->criteria),
            'entity_type' => $this->sanitizeClassName($this->entity_type),
        ]);
    }

    protected function sanitizeCriteria(?string $criteria): ?string
    {
        if (!$criteria) {
            return null;
        }

        // Only allow alphanumeric, dash, underscore
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $criteria);
    }

    protected function sanitizeClassName(?string $className): ?string
    {
        if (!$className) {
            return null;
        }

        // Only allow valid PHP class name characters
        return preg_replace('/[^a-zA-Z0-9\\\\]/', '', $className);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verify entity type exists
            if (!class_exists($this->entity_type)) {
                $validator->errors()->add('entity_type', 'Entity type does not exist.');
            }

            // Verify entity exists
            if (class_exists($this->entity_type)) {
                $model = new $this->entity_type;
                if (!$model::where('id', $this->entity_id)->exists()) {
                    $validator->errors()->add('entity_id', 'Entity not found.');
                }
            }
        });
    }
}
```

### Create Criteria Request

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidRuleStructure;

class CreateCriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-criteria');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'unique:eligify_criteria,name',
            ],
            'description' => 'nullable|string|max:1000',
            'rules' => [
                'required',
                'array',
                'min:1',
                'max:50', // Limit number of rules
            ],
            'rules.*' => ['required', 'array', new ValidRuleStructure],
            'rules.*.field' => 'required|string|max:255',
            'rules.*.operator' => 'required|string|in:' . implode(',', $this->allowedOperators()),
            'rules.*.value' => 'required',
            'rules.*.weight' => 'nullable|numeric|min:0|max:1',
            'scoring_method' => 'required|string|in:weighted,pass_fail,percentage',
            'threshold' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ];
    }

    protected function allowedOperators(): array
    {
        return ['==', '!=', '>', '>=', '<', '<=', 'in', 'not_in', 'between', 'contains'];
    }

    public function prepareForValidation(): void
    {
        // Sanitize name
        if ($this->has('name')) {
            $this->merge([
                'name' => $this->sanitizeName($this->name),
            ]);
        }

        // Sanitize rule fields
        if ($this->has('rules')) {
            $this->merge([
                'rules' => collect($this->rules)->map(function ($rule) {
                    if (isset($rule['field'])) {
                        $rule['field'] = FieldSanitizer::sanitize($rule['field']);
                    }
                    if (isset($rule['value']) && isset($rule['operator'])) {
                        $rule['value'] = ValueValidator::sanitizeValue(
                            $rule['value'],
                            $rule['operator']
                        );
                    }
                    return $rule;
                })->all(),
            ]);
        }
    }

    protected function sanitizeName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
    }
}
```

### Custom Validation Rules

```php
// app/Rules/ValidRuleStructure.php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidRuleStructure implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Must have required keys
        if (!isset($value['field'], $value['operator'], $value['value'])) {
            return false;
        }

        // Validate field name format
        if (!FieldSanitizer::validate($value['field'])) {
            return false;
        }

        // Validate operator
        $allowedOperators = ['==', '!=', '>', '>=', '<', '<=', 'in', 'not_in', 'between', 'contains'];
        if (!in_array($value['operator'], $allowedOperators)) {
            return false;
        }

        // Validate value for operator
        if (!ValueValidator::validateForOperator($value['value'], $value['operator'])) {
            return false;
        }

        // Validate weight if present
        if (isset($value['weight'])) {
            if (!is_numeric($value['weight']) || $value['weight'] < 0 || $value['weight'] > 1) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'The :attribute has an invalid rule structure.';
    }
}
```

## Prevent Injection Attacks

### SQL Injection Prevention

```php
// Always use parameter binding
use Illuminate\Support\Facades\DB;

// ✅ Good - Parameterized query
$results = DB::table('eligify_audits')
    ->where('criteria_name', $criteriaName)
    ->get();

// ❌ Bad - Direct string concatenation
$results = DB::select("SELECT * FROM eligify_audits WHERE criteria_name = '{$criteriaName}'");
```

### Code Injection Prevention

```php
// Never use eval() with user input
// ❌ Bad
eval("\$result = {$userInput};");

// ✅ Good - Use safe evaluation
class SafeEvaluator
{
    public static function evaluateExpression(string $field, string $operator, mixed $value): bool
    {
        // Use predefined operators only
        return match($operator) {
            '==' => $field == $value,
            '!=' => $field != $value,
            '>' => $field > $value,
            '>=' => $field >= $value,
            '<' => $field < $value,
            '<=' => $field <= $value,
            default => throw new \InvalidArgumentException('Invalid operator'),
        };
    }
}
```

### XSS Prevention

```php
// Always escape output in views
<!-- ✅ Good -->
<div>{{ $criteria->name }}</div>

<!-- ❌ Bad -->
<div>{!! $criteria->name !!}</div>

// Sanitize user-generated HTML
use Illuminate\Support\Str;

$clean = Str::of($userInput)->stripTags()->toString();
```

## Rate Limiting

### Prevent Abuse

```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

// Limit evaluation requests
RateLimiter::for('evaluations', function (Request $request) {
    return Limit::perMinute(60)
        ->by($request->user()?->id ?: $request->ip())
        ->response(function () {
            return response()->json([
                'message' => 'Too many evaluation requests. Please slow down.',
            ], 429);
        });
});

// Apply to routes
Route::middleware('throttle:evaluations')->post('/evaluate', function () {
    // ...
});
```

### Progressive Rate Limiting

```php
RateLimiter::for('evaluations', function (Request $request) {
    $userId = $request->user()?->id;

    // Authenticated users get higher limit
    if ($userId) {
        return Limit::perMinute(100)->by($userId);
    }

    // Anonymous users get lower limit
    return Limit::perMinute(10)->by($request->ip());
});
```

## Data Sanitization

### Sanitize Before Storage

```php
use CleaniqueCoders\Eligify\Models\Criteria;

class Criteria extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Criteria $criteria) {
            // Sanitize name
            $criteria->name = preg_replace('/[^a-zA-Z0-9_-]/', '', $criteria->name);

            // Sanitize description
            $criteria->description = strip_tags($criteria->description);

            // Sanitize rules
            $criteria->rules = collect($criteria->rules)
                ->map(function ($rule) {
                    $rule['field'] = FieldSanitizer::sanitize($rule['field']);
                    $rule['value'] = ValueValidator::sanitizeValue(
                        $rule['value'],
                        $rule['operator']
                    );
                    return $rule;
                })
                ->all();
        });
    }
}
```

## File Upload Validation

### Validate Imports

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCriteriaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:json,csv',
                'max:2048', // 2MB
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file')) {
                $content = file_get_contents($this->file('file')->path());

                // Validate JSON structure
                if ($this->file('file')->getClientOriginalExtension() === 'json') {
                    $data = json_decode($content, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $validator->errors()->add('file', 'Invalid JSON format.');
                        return;
                    }

                    // Validate required fields
                    if (!isset($data['name'], $data['rules'])) {
                        $validator->errors()->add('file', 'Missing required fields.');
                    }
                }
            }
        });
    }
}
```

## Environment-Specific Validation

### Strict Validation in Production

```php
// config/eligify.php
return [
    'validation' => [
        'strict' => env('ELIGIFY_STRICT_VALIDATION', app()->environment('production')),
        'max_rules' => env('ELIGIFY_MAX_RULES', 50),
        'max_field_length' => env('ELIGIFY_MAX_FIELD_LENGTH', 255),
        'max_value_length' => env('ELIGIFY_MAX_VALUE_LENGTH', 1000),
    ],
];
```

## Testing Validation

### Test Input Validation

```php
use App\Http\Requests\CreateCriteriaRequest;

test('validates criteria name format', function () {
    $request = CreateCriteriaRequest::create('/api/criteria', 'POST', [
        'name' => 'invalid name!@#', // Invalid characters
        'rules' => [
            ['field' => 'income', 'operator' => '>=', 'value' => 3000],
        ],
        'scoring_method' => 'weighted',
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
});

test('rejects SQL injection attempts', function () {
    $malicious = "'; DROP TABLE users; --";

    $request = CreateCriteriaRequest::create('/api/criteria', 'POST', [
        'name' => $malicious,
        'rules' => [
            ['field' => 'income', 'operator' => '>=', 'value' => 3000],
        ],
        'scoring_method' => 'weighted',
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
});
```

## Best Practices

1. **Validate Early**: Check inputs at the request level
2. **Sanitize Always**: Clean all user inputs before processing
3. **Whitelist, Don't Blacklist**: Define allowed values, not forbidden ones
4. **Limit Input Size**: Prevent DoS attacks with size limits
5. **Use Type Hints**: Leverage PHP's type system
6. **Test Edge Cases**: Test with malicious and malformed inputs

## Related Documentation

- [Authorization](authorization.md)
- [Best Practices](best-practices.md)
- [Vulnerability Reporting](vulnerability-reporting.md)
