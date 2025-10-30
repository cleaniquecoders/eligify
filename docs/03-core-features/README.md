# Core Features

This section covers the fundamental features that make up the Eligify eligibility engine.

## Overview

Eligify provides a complete system for defining, evaluating, and acting upon eligibility criteria. The core features work together to provide a flexible and powerful decision-making engine.

## Documentation in this Section

- **[Criteria Builder](criteria-builder.md)** - Building criteria definitions
- **[Rule Engine](rule-engine.md)** - Rule definitions and operators
- **[Scoring Methods](scoring-methods.md)** - Weighted, Pass/Fail, Sum, Average scoring
- **[Evaluation Engine](evaluation-engine.md)** - How evaluations work
- **[Workflow Callbacks](workflow-callbacks.md)** - onPass, onFail actions

## Key Concepts

### Criteria

A named set of rules that define eligibility requirements:

```php
Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650);
```

### Rules

Individual conditions that must be met:

```php
->addRule('field_name', 'operator', 'value', $weight)
```

### Evaluation

The process of checking if an entity meets the criteria:

```php
$result = $criteria->evaluate($applicant);
```

### Results

The outcome of an evaluation:

```php
[
    'passed' => true,
    'score' => 92,
    'failed_rules' => [],
    'decision' => 'Approved',
]
```

## Workflow

1. **Define** criteria and rules
2. **Configure** scoring method and weights
3. **Evaluate** against an entity
4. **Execute** callbacks based on pass/fail
5. **Audit** the decision for traceability

## Related Sections

- [Getting Started](../01-getting-started/) - Learn the basics
- [Examples](../13-examples/) - See real-world usage
- [Configuration](../06-configuration/) - Configure operators and scoring
