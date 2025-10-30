# Roadmap

Future features and development plans for Eligify.

## Version 1.3 (Current)

### Completed Features ✅

- Core eligibility evaluation engine
- Rule-based criteria system
- Multiple scoring methods (weighted, pass/fail, sum, average)
- Audit trail and logging
- Snapshot system
- Caching support
- Workflow callbacks (onPass/onFail)
- Custom operators
- Laravel model integration
- Data extraction with dot notation
- Model mappers with relationship support
- Comprehensive testing suite
- Visual Criteria Builder UI
  - Visual rule testing
  - Playground for testing
  - Audit log viewer
- Complete documentation

## Version 1.x (In Progress)

### Planned Features

#### 1. Advanced Scoring Algorithms

```php
// Machine learning-based scoring
$result = Eligify::criteria('Loan Approval')
    ->scoringMethod('ml_model', 'loan_risk_model')
    ->evaluate($applicant);

// Fuzzy logic scoring
$result = Eligify::criteria('Candidate Fit')
    ->scoringMethod('fuzzy')
    ->evaluate($candidate);
```

#### 2. Rule Versioning

```php
// Track criteria changes over time
$v1 = Criteria::find(1)->version(1);
$v2 = Criteria::find(1)->version(2);

// Evaluate against historical version
$result = Eligify::criteria('Loan Approval')
    ->version(1)
    ->evaluate($applicant);
```

#### 3. A/B Testing Support

```php
// Test different criteria configurations
Eligify::abTest('loan_approval', [
    'variant_a' => fn($builder) => $builder->addRule('income', '>=', 3000),
    'variant_b' => fn($builder) => $builder->addRule('income', '>=', 3500),
]);
```

#### 4. Performance Enhancements

**Parallel Rule Evaluation:**

```php
// Evaluate rules in parallel
$result = Eligify::criteria('Complex')
    ->parallel()
    ->addRule('slow_check_1', 'custom', ...)
    ->addRule('slow_check_2', 'custom', ...)
    ->evaluate($entity);
```

**Compiled Criteria:**

```php
// Pre-compile criteria for faster execution
php artisan eligify:compile Loan Approval

// Use compiled version
$result = Eligify::compiled('loan_approval')->evaluate($applicant);
```

**Distributed Evaluation:**

```php
// Distribute evaluations across multiple workers
Eligify::criteria('Bulk Evaluation')
    ->distributed()
    ->evaluateBatch($applicants);
```

#### 5. Advanced Rule Features

**Conditional Rules:**

```php
// Rules that depend on other rules
$result = Eligify::criteria('Complex')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->addRuleIf('income', '>=', 5000, function ($builder) {
        $builder->addRule('debt_ratio', '<=', 0.3);
    })
    ->evaluate($applicant);
```

**Rule Groups:**

```php
// Logical grouping of rules
$result = Eligify::criteria('Loan')
    ->group('basic', function ($group) {
        $group->addRule('age', '>=', 18);
        $group->addRule('citizenship', '==', 'US');
    })
    ->group('financial', function ($group) {
        $group->addRule('income', '>=', 3000);
        $group->addRule('credit_score', '>=', 650);
    })
    ->requireAll(['basic', 'financial'])
    ->evaluate($applicant);
```

**Time-Based Criteria:**

```php
// Criteria that change based on time
$result = Eligify::criteria('Seasonal Discount')
    ->addRule('membership_months', '>=', 6)
    ->addRule('current_month', 'in', [11, 12]) // November, December
    ->evaluate($customer);

// Schedule criteria activation
Criteria::find(1)->scheduleActivation(now()->addDays(7));
```

## Version 2.0 (Future Vision)

### AI-Powered Features

#### 1. Intelligent Rule Suggestions

```php
// AI suggests rules based on historical data
$suggestions = Eligify::suggestRules('loan_approval', $historicalData);

// Apply suggestions
foreach ($suggestions as $rule) {
    $criteria->addRule($rule['field'], $rule['operator'], $rule['value']);
}
```

#### 2. Anomaly Detection

```php
// Detect unusual evaluation patterns
Eligify::criteria('Loan Approval')
    ->withAnomalyDetection()
    ->evaluate($applicant);

// Alert if evaluation is anomalous
if ($result->isAnomalous()) {
    // Flag for manual review
}
```

#### 3. Predictive Analytics

```php
// Predict likelihood of passing before full evaluation
$prediction = Eligify::predict('loan_approval', $partialData);

if ($prediction->likelihood < 0.2) {
    return 'Pre-rejected';
}
```

### Enterprise Features

#### 1. Approval Workflows

```php
// Multi-stage approval process
$result = Eligify::criteria('Loan Approval')
    ->requireApproval(['manager', 'senior_manager'])
    ->evaluate($applicant);
```

#### 2. Compliance Reporting

```php
// Generate compliance reports
php artisan eligify:report compliance --period=month

// Export audit trail
Eligify::exportAuditTrail('loan_approval', 'pdf');
```

#### 3. Multi-Language Support

```php
// Internationalized criteria
$criteria->translate('es', [
    'name' => 'aprobación_de_préstamo',
    'description' => 'Criterios de aprobación de préstamos',
]);
```

## Community Requests

### Highly Requested Features

1. **API** - API endpoints for criteria management
2. **Criteria Marketplace** - Share and download criteria templates
3. **Excel Import/Export** - Bulk criteria management via Excel

### Under Consideration

- Real-time evaluation streaming
- Blockchain-based immutable audit trails
- Integration with external decision engines
- Natural language criteria definition
- Voice-activated criteria management

## Contribution Opportunities

Want to contribute? Here are areas we'd love help with:

- **Documentation**: Improve guides and examples
- **Testing**: Add more test coverage
- **Performance**: Optimize evaluation engine
- **Integrations**: Build integrations with popular packages
- **UI**: Enhance the visual criteria builder

See [Contributing Guide](../../CONTRIBUTING.md).

## Stay Updated

- **GitHub**: Watch the repository for updates
- **Changelog**: Check [CHANGELOG.md](../../CHANGELOG.md)
- **Discussions**: Join [GitHub Discussions](https://github.com/cleaniquecoders/eligify/discussions)
- **Twitter**: Follow @CleaniqueCoder

## Feedback

We'd love to hear your ideas! Share feedback:

- [GitHub Discussions](https://github.com/cleaniquecoders/eligify/discussions)
- [Feature Requests](https://github.com/cleaniquecoders/eligify/issues/new?template=feature_request.md)
- Email: <hello@cleaniquecoders.com>

## Related

- [Comparison](comparison.md)
- [FAQ](faq.md)
- [Contributing](../../CONTRIBUTING.md)
