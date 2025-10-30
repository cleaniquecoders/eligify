# Data Extraction

The data extraction system transforms various data sources (models, arrays, objects) into a normalized format for eligibility evaluation.

## Overview

The Extractor class provides:

- Multi-format data handling (arrays, models, objects, collections)
- Integration with Model Mappings for complex transformations
- Nested data access with dot notation
- Configurable extraction strategies

## Documentation

- **[Extraction Guide](guide.md)** - Complete guide with decision flowchart
- **[Extractor Reference](extractor.md)** - Detailed API documentation

## Quick Example

```php
use CleaniqueCoders\Eligify\Data\Extractor;

// Simple extraction from array
$extractor = new Extractor();
$data = $extractor->extract([
    'income' => 5000,
    'credit_score' => 750,
]);

// Extract from model with mapping
$data = Extractor::forModel(User::class)->extract($user);

// Custom configuration
$extractor = new Extractor([
    'include_relationships' => true,
    'max_relationship_depth' => 2,
]);

$extractor->setFieldMappings([
    'annual_income' => 'income',
    'credit_rating' => 'credit_score',
]);

$data = $extractor->extract($user);
```

## Key Features

1. **Multiple Data Sources** - Arrays, models, objects, collections
2. **Model Mapping Integration** - Automatic mapping resolution
3. **Relationship Handling** - Configurable depth and inclusion
4. **Field Transformation** - Rename and compute fields on-the-fly

## When to Use What

- **Simple arrays** - Direct extraction, no configuration needed
- **Models with mappings** - Use `Extractor::forModel()` for automatic mapping
- **Custom transformations** - Configure extractor with setter methods
- **One-off extractions** - Create new extractor instance

## Next Steps

Start with the [Extraction Guide](guide.md) to understand the decision-making process and choose the right approach for your use case.
