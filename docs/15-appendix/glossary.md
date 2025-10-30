# Glossary

Terms and definitions used in Eligify.

## A

### Audit Log

A record of every eligibility evaluation, including who evaluated, when, what criteria was used, and the result.

### Audit Trail

The complete history of evaluations for an entity, providing transparency and traceability.

## C

### Criteria

A named set of rules that define eligibility requirements for a specific purpose (e.g., "loan_approval", "scholarship").

### Criteria Builder

A fluent interface for constructing eligibility criteria programmatically.

## E

### Entity

The object being evaluated for eligibility (e.g., User, Applicant, Application).

### Evaluation

The process of checking if an entity meets the defined criteria requirements.

### Evaluation Result

The outcome of an eligibility check, including pass/fail status, score, and detailed rule results.

### Extractor

A component that retrieves values from entities for rule evaluation, supporting dot notation and custom methods.

## F

### Failed Rules

Rules that did not pass during evaluation.

## O

### Operator

A comparison function used in rules (e.g., `>=`, `in`, `between`).

## P

### Passed Rules

Rules that successfully passed during evaluation.

### Pass/Fail Scoring

Binary scoring method where the result is either 0 (fail) or 100 (pass).

## R

### Rule

An individual condition that must be met, consisting of a field, operator, value, and optional weight.

### Rule Weight

A numeric value (0-1) indicating the importance of a rule in weighted scoring.

## S

### Score

A numeric value (0-100) representing how well an entity met the criteria.

### Scoring Method

The algorithm used to calculate the final score from individual rule results.

### Snapshot

An immutable copy of entity data at the time of evaluation, stored for audit purposes.

## T

### Threshold

The minimum score required for an evaluation to pass.

## W

### Weighted Scoring

A scoring method where each rule contributes a percentage to the final score based on its weight.

### Workflow

Automated actions triggered when evaluation passes or fails (via `onPass`/`onFail` callbacks).

## Related

- [FAQ](faq.md)
- [Core Concepts](../01-getting-started/core-concepts.md)
