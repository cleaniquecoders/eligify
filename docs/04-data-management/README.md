# Data Management

This section covers how Eligify manages and processes data during eligibility evaluation.

## Overview

Eligify provides powerful data management features including model mapping, data extraction, snapshots, and dynamic value input.

## Documentation in this Section

### Model Mapping

- **[Getting Started](model-mapping/getting-started.md)** - Quick start with model mapping
- **[Patterns](model-mapping/patterns.md)** - All 4 mapping patterns explained
- **[Relationship Mapping](model-mapping/relationship-mapping.md)** - Deep dive into relationships
- **[Generator](model-mapping/generator.md)** - Automated mapper generation

### Snapshot System

- **[Usage Guide](snapshot/usage.md)** - Using the snapshot system
- **[Data Structure](snapshot/data-structure.md)** - Snapshot object structure

### Other Features

- **[Data Extractor](extractor.md)** - Extracting data from models
- **[Dynamic Values](dynamic-values.md)** - Dynamic value input during evaluation

## Key Features

### 1. Model Mapping

Transform your Eloquent models into a format suitable for evaluation:

```php
$mapper = new UserMapper($user);
$data = $mapper->toArray();
```

Supports 4 patterns:

- Direct Attribute Mapping
- Nested Object Mapping
- Relationship Mapping
- Computed Values

### 2. Snapshot System

Capture and preserve state at evaluation time:

```php
$snapshot = Eligify::snapshot($user, 'loan_application');
$evaluation = $snapshot->evaluate();
```

### 3. Data Extraction

Extract specific fields from complex objects:

```php
$extractor = new DataExtractor($model);
$value = $extractor->get('profile.credit_score');
```

### 4. Dynamic Values

Accept runtime values during evaluation:

```php
->addRule('requested_amount', '<=', function($context) {
    return $context->get('max_loan_amount');
});
```

## Use Cases

- **Historical Auditing**: Snapshots preserve state for compliance
- **Complex Data Sources**: Model mapping handles nested relationships
- **Runtime Configuration**: Dynamic values allow flexible evaluation
- **Performance**: Extract only needed data from large objects

## Related Sections

- [Core Features](../03-core-features/) - How data is evaluated
- [Examples](../13-examples/) - Real-world data management patterns
- [Advanced Features](../07-advanced-features/) - Caching and optimization
