# Dynamic Value Input Based on Field Type

## Overview

The Rule Editor automatically adapts the value input field based on the selected field type, providing an intuitive and type-safe experience for creating rules. The input type, placeholder text, help text, and validation all change dynamically to match the expected data type.

## Dynamic Input Types

### Numeric Fields (Integer & Numeric)

**Integer:**

- Input type: `number`
- Step: `1` (whole numbers only)
- Placeholder: `e.g., 100`
- Help text: "Enter a whole number (no decimals)"

**Numeric:**

- Input type: `number`
- Step: `0.01` (allows decimals)
- Placeholder: `e.g., 99.99`
- Help text: "Enter a numeric value (decimals allowed)"

**Example:**

```
Field: age
Field Type: Integer
Value Input: [___100___] (number input, step=1)
```

### String Fields

- Input type: `text`
- Placeholder: `e.g., active`
- Help text: "Enter text value"

**Example:**

```
Field: status
Field Type: String
Value Input: [_active_] (text input)
```

### Boolean Fields

- Input type: Radio buttons
- Options: `True` / `False`
- Help text: "Enter true/false or 1/0"

**Example:**

```
Field: is_verified
Field Type: Boolean
Value Input: ○ True  ○ False (radio buttons)
```

### Date Fields

- Input type: `date`
- Placeholder: `e.g., 2024-01-01`
- Help text: "Enter date in YYYY-MM-DD format"
- Browser provides native date picker

**Example:**

```
Field: birth_date
Field Type: Date
Value Input: [📅 2024-01-01] (date picker)
```

### Array Fields

- Input type: `textarea` (multi-line)
- Placeholder: `e.g., ["value1","value2"] or value1,value2`
- Help text: "Enter values as comma-separated or JSON array"

**Example:**

```
Field: tags
Field Type: Array
Value Input:
┌─────────────────────┐
│ value1,value2,value3│
└─────────────────────┘
(textarea)
```

## Multi-Value Operators

When an operator that requires multiple values is selected (`in`, `not_in`, `between`, `not_between`), the input automatically switches to a `textarea` regardless of field type, and the placeholder/help text adjusts accordingly.

### Examples by Field Type

**Integer with IN operator:**

```
Field: age
Field Type: Integer
Operator: in (In Array)
Value Input:
┌─────────────────────┐
│ 18,21,25,30        │
└─────────────────────┘
Placeholder: "e.g., 100,200,300 or [100,200,300]"
```

**String with IN operator:**

```
Field: status
Field Type: String
Operator: in (In Array)
Value Input:
┌─────────────────────────────────┐
│ active,pending,approved        │
└─────────────────────────────────┘
Placeholder: "e.g., active,pending,approved or ["active","pending"]"
```

**Date with BETWEEN operator:**

```
Field: created_at
Field Type: Date
Operator: between (Between)
Value Input:
┌─────────────────────────────┐
│ 2024-01-01,2024-12-31      │
└─────────────────────────────┘
Placeholder: "e.g., 2024-01-01,2024-12-31"
```

## Visual Feedback

### Field Type Info Box

When a field type is selected, an informational blue box appears showing:

- **Field Type Name**: The selected type (e.g., "Integer")
- **Input Type**: The HTML input type being used (e.g., "number")
- **Compatible Operators**: Count of available operators for this type
- **Special Features**: Any special handling (e.g., "Using True/False selection" for Boolean)

**Example:**

```
┌────────────────────────────────────────────────┐
│ ℹ️ Field Type: Integer                        │
│   ✓ Input type: number                        │
│   ✓ 9 compatible operator(s)                  │
└────────────────────────────────────────────────┘
```

### Smart Placeholder Updates

Placeholders dynamically update to show appropriate examples:

| Field Type | Single Value | Multiple Values (IN/BETWEEN) |
|-----------|-------------|------------------------------|
| **Integer** | `e.g., 100` | `e.g., 100,200,300 or [100,200,300]` |
| **Numeric** | `e.g., 99.99` | `e.g., 100,200,300 or [100,200,300]` |
| **String** | `e.g., active` | `e.g., active,pending,approved or ["active","pending"]` |
| **Boolean** | Radio buttons | `true,false` (if multi-value) |
| **Date** | `e.g., 2024-01-01` | `e.g., 2024-01-01,2024-12-31` |
| **Array** | `e.g., ["value1","value2"]` | Same as single value |

## Input Validation

### Browser-Level Validation

Based on input type, browsers provide automatic validation:

- **Number inputs**: Only allow numeric characters, enforce step
- **Date inputs**: Only allow valid dates, provide date picker
- **Radio buttons**: Only one selection possible

### Step Validation for Numbers

- **Integer**: `step="1"` - Only whole numbers
- **Numeric**: `step="0.01"` - Allows up to 2 decimal places
- **Others**: `step="any"` - No restriction

### Example Validation

```html
<!-- Integer Field -->
<input type="number" step="1" min="0" />
<!-- Rejects: 1.5, abc, special chars -->
<!-- Accepts: 0, 1, 100, 9999 -->

<!-- Numeric Field -->
<input type="number" step="0.01" />
<!-- Rejects: abc, special chars -->
<!-- Accepts: 1.5, 99.99, 0.01 -->

<!-- Date Field -->
<input type="date" />
<!-- Provides date picker -->
<!-- Rejects: abc, 32/13/2024 -->
<!-- Accepts: 2024-01-01 -->
```

## User Experience Flow

### Creating a Numeric Rule

1. User enters field name: `income`
2. User selects field type: `Numeric`
3. **System updates:**
   - Shows info box: "Input type: number"
   - Filters operators to numeric ones
   - Changes value input to number type with step="0.01"
   - Updates placeholder to "e.g., 99.99"
4. User selects operator: `>=`
5. User enters value: `3000.50` (validated as number)
6. Rule saved with correct numeric type

### Creating an Array Rule

1. User enters field name: `categories`
2. User selects field type: `Array`
3. **System updates:**
   - Shows info box
   - Changes value input to textarea
   - Updates placeholder with array examples
4. User selects operator: `in`
5. User enters value: `tech,business,finance`
6. System automatically parses as array: `["tech","business","finance"]`

### Changing Field Type Mid-Edit

1. User has `String` field type selected
2. Input shows as text box
3. User changes to `Integer`
4. **System immediately:**
   - Switches input to number type
   - Updates placeholder
   - Filters operators (removes string-specific ones)
   - If current operator invalid, resets to first available

## Implementation Details

### Component Properties

```php
// Computed properties that react to field type changes
$this->valueInputType      // 'text', 'number', 'date', 'checkbox'
$this->valuePlaceholder    // Dynamic placeholder text
$this->valueHelpText       // Context-aware help text
$this->isBooleanInput      // Boolean for radio button rendering
$this->requiresMultipleValues  // Boolean for textarea rendering
$this->numericStep         // '1', '0.01', or 'any'
```

### View Logic

```blade
@if($this->isBooleanInput && !$this->requiresMultipleValues)
    <!-- Radio buttons for boolean -->
@elseif($this->requiresMultipleValues || $fieldType === 'array')
    <!-- Textarea for arrays/multi-value -->
@else
    <!-- Standard input with dynamic type -->
@endif
```

## Best Practices

### For Users

1. **Always set field type** for better input validation
2. **Use the appropriate type** for your data
3. **Follow placeholder examples** for correct formatting
4. **For arrays**, use comma-separated values or JSON

### For Developers

1. **Field type is optional** - system falls back to text input
2. **Value parsing handles multiple formats** - arrays can be entered as comma-separated or JSON
3. **Input changes are instant** - powered by Livewire reactive properties
4. **Validation happens client-side and server-side**

## Examples

### Age Verification Rule (Integer)

```
Field: age
Field Type: Integer          → Input changes to number, step=1
Operator: >= (filtered)
Value: 18                    → Only accepts whole numbers
Result: Clean integer validation
```

### Price Range Rule (Numeric)

```
Field: price
Field Type: Numeric          → Input changes to number, step=0.01
Operator: between
Value: 10.99,99.99          → Textarea for range, accepts decimals
Result: Precise decimal values
```

### Status Check Rule (String)

```
Field: status
Field Type: String           → Text input
Operator: in
Value: active,pending,approved  → Textarea for multiple values
Result: Array of strings
```

### Verification Flag Rule (Boolean)

```
Field: is_verified
Field Type: Boolean          → Radio buttons appear
Operator: ==
Value: [Radio: ○ True ● False]  → User-friendly selection
Result: Clean boolean value
```

### Date Range Rule (Date)

```
Field: birth_date
Field Type: Date             → Date picker input
Operator: between
Value: 1990-01-01,2000-12-31  → Textarea with date format hint
Result: Valid date range
```

## Testing

Comprehensive tests ensure dynamic behavior:

```php
test('value input type changes based on field type', function () {
    $component->set('fieldType', FieldType::NUMERIC->value);
    expect($component->get('valueInputType'))->toBe('number');
});

test('boolean field type shows radio buttons', function () {
    $component->set('fieldType', FieldType::BOOLEAN->value);
    expect($component->get('isBooleanInput'))->toBeTrue();
});

test('multi-value operator switches to textarea', function () {
    $component->set('operator', RuleOperator::IN->value);
    expect($component->get('requiresMultipleValues'))->toBeTrue();
});
```

## Benefits

✅ **Type Safety** - Browser-level validation prevents invalid input
✅ **Better UX** - Users see appropriate inputs for data types
✅ **Reduced Errors** - Validation happens before submission
✅ **Visual Feedback** - Info box shows what's happening
✅ **Smart Defaults** - System provides relevant examples
✅ **Flexible** - Still accepts text input if no type selected
✅ **Reactive** - Changes happen instantly via Livewire

## Troubleshooting

**Q: Input type doesn't change when I select field type?**
A: Ensure you have Livewire properly loaded and check browser console for errors.

**Q: Can I enter decimals in an integer field?**
A: No, integer fields enforce `step="1"` which only allows whole numbers.

**Q: Boolean input shows text field instead of radio buttons?**
A: This happens when a multi-value operator is selected. Boolean doesn't support array values, so change the operator.

**Q: Date picker not showing?**
A: Some older browsers may not support `<input type="date">`. The field will fall back to text input in those cases.

**Q: Array values not parsing correctly?**
A: Use comma-separated format (`value1,value2`) or valid JSON (`["value1","value2"]`). Mixed formats may cause parsing issues.
