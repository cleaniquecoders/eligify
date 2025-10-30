# Security & API Stability

This document covers security best practices and API stability guarantees for Eligify.

## Table of Contents

- [Security Best Practices](#security-best-practices)
- [API Stability Guarantees](#api-stability-guarantees)
- [Security Audit Checklist](#security-audit-checklist)
- [Vulnerability Reporting](#vulnerability-reporting)
- [Breaking Changes Policy](#breaking-changes-policy)

## Security Best Practices

### 1. Input Validation

Always validate and sanitize user input before evaluation:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

public function evaluate(Request $request)
{
    // Validate input
    $validated = $request->validate([
        'income' => 'required|numeric|min:0|max:10000000',
        'age' => 'required|integer|min:0|max:150',
        'credit_score' => 'required|integer|min:300|max:850',
    ]);

    // Safe to use validated data
    $result = Eligify::criteria('loan_approval')
        ->evaluate($validated);
}
```

### 2. SQL Injection Prevention

Eligify uses Laravel's Eloquent ORM, which provides automatic protection against SQL injection through parameterized queries. **Never** bypass this protection:

```php
// ✅ SAFE - Uses parameterized queries
$criteria->addRule('income', '>=', $userInput);

// ❌ DANGEROUS - Never do this
DB::raw("SELECT * WHERE income >= " . $userInput);
```

### 3. Mass Assignment Protection

Protect your models from mass assignment vulnerabilities:

```php
// In your Criteria model
class Criteria extends Model
{
    protected $fillable = [
        'name',
        'description',
        'passing_threshold',
    ];

    // Never use this in production
    // protected $guarded = [];
}
```

### 4. Authorization Checks

Always verify user permissions before allowing criteria operations:

```php
use Illuminate\Support\Facades\Gate;

public function updateCriteria(Request $request, string $id)
{
    $criteria = Criteria::findOrFail($id);

    // Check authorization
    Gate::authorize('update', $criteria);

    // Or using policies
    $this->authorize('update', $criteria);

    // Safe to proceed
    $criteria->update($request->validated());
}
```

### 5. Rate Limiting

Implement rate limiting to prevent abuse:

```php
// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/api/evaluate', [EligibilityController::class, 'evaluate']);
});

// Custom rate limiting
Route::post('/api/evaluate', [EligibilityController::class, 'evaluate'])
    ->middleware('throttle:30,1,evaluate');
```

### 6. Sensitive Data Handling

Configure which fields should be masked in audit logs:

```php
// config/eligify.php
'audit' => [
    'sensitive_fields' => [
        'password',
        'credit_card_number',
        'ssn',
        'tax_id',
        'api_key',
        'secret',
        'token',
    ],
    'mask_sensitive_data' => true,
],
```

### 7. Secure Callbacks

Validate and sanitize data in callbacks:

```php
Eligify::criteria('loan_approval')
    ->onPass(function ($data) {
        // Validate before processing
        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            throw new \InvalidArgumentException('Invalid user ID');
        }

        // Safe to proceed
        User::find($data['user_id'])->approveLoan();
    })
    ->evaluate($applicant);
```

### 8. Environment Configuration

Never commit sensitive configuration to version control:

```env
# .env - Never commit this file
ELIGIFY_AUDIT_ENABLED=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database
```

```php
// .env.example - Safe to commit
ELIGIFY_AUDIT_ENABLED=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=
```

### 9. HTTPS Only

Always use HTTPS in production:

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}
```

### 10. CSRF Protection

Ensure CSRF protection is enabled for state-changing operations:

```php
// Automatic in Laravel for web routes
// For API routes, use Sanctum or Passport

// In forms
<form method="POST" action="/evaluate">
    @csrf
    <!-- form fields -->
</form>
```

## API Stability Guarantees

### Semantic Versioning

Eligify follows [Semantic Versioning 2.0.0](https://semver.org/):

- **MAJOR** version (X.0.0): Breaking changes
- **MINOR** version (0.X.0): New features, backward compatible
- **PATCH** version (0.0.X): Bug fixes, backward compatible

### Public API Surface

The following are considered **public API** and follow stability guarantees:

#### Core Classes ✅ STABLE

```php
// Facade
CleaniqueCoders\Eligify\Facades\Eligify

// Main class
CleaniqueCoders\Eligify\Eligify

// Builder
CleaniqueCoders\Eligify\Builder\CriteriaBuilder

// Engine
CleaniqueCoders\Eligify\Engine\RuleEngine
CleaniqueCoders\Eligify\Engine\AdvancedRuleEngine
```

#### Models ✅ STABLE

```php
CleaniqueCoders\Eligify\Models\Criteria
CleaniqueCoders\Eligify\Models\Rule
CleaniqueCoders\Eligify\Models\Evaluation
CleaniqueCoders\Eligify\Models\AuditLog
```

#### Enums ✅ STABLE

```php
CleaniqueCoders\Eligify\Enums\RuleOperator
CleaniqueCoders\Eligify\Enums\FieldType
CleaniqueCoders\Eligify\Enums\RulePriority
CleaniqueCoders\Eligify\Enums\ScoringMethod
```

#### Traits ✅ STABLE

```php
CleaniqueCoders\Eligify\Concerns\HasEligibility
```

#### Events ✅ STABLE

```php
CleaniqueCoders\Eligify\Events\EvaluationCompleted
CleaniqueCoders\Eligify\Events\CriteriaCreated
CleaniqueCoders\Eligify\Events\RuleExecuted
```

### Method Signatures

The following method signatures are **guaranteed stable** in v1.x:

```php
// Eligify facade/class
Eligify::criteria(string $name): CriteriaBuilder

// CriteriaBuilder
->addRule(string $field, string $operator, mixed $value, ?int $priority = null): self
->addRuleGroup(array $group): self
->onPass(callable $callback): self
->onFail(callable $callback): self
->evaluate(array|object $data): array
->evaluateBatch(array $items): array

// RuleEngine
->evaluate(Rule $rule, array|object $data): bool
->calculateScore(array $rules, array $results): float

// Models
Criteria::create(array $attributes): Criteria
Rule::create(array $attributes): Rule
Evaluation::create(array $attributes): Evaluation
AuditLog::create(array $attributes): AuditLog
```

### Configuration Keys

These configuration keys are **stable** and won't be removed in v1.x:

```php
// config/eligify.php
'cache.enabled'
'cache.ttl'
'cache.prefix'
'audit.enabled'
'audit.retention_days'
'audit.track_ip'
'audit.track_user_agent'
'optimization.query_optimization'
'optimization.eager_loading'
```

### Database Schema

The following table structures are **stable** in v1.x:

- `eligify_criteria` - Core fields won't be removed
- `eligify_rules` - Core fields won't be removed
- `eligify_evaluations` - Core fields won't be removed
- `eligify_audit_logs` - Core fields won't be removed

**Note:** New columns may be added, but existing columns won't be removed or have their types changed in v1.x.

### What May Change

The following are **NOT part of the public API** and may change without notice:

- Internal helper classes
- Private/protected methods
- Implementation details
- Internal database queries
- Cache key formats
- Internal data structures

## Security Audit Checklist

### Pre-Release Checklist ✅

- [x] **Input Validation**
  - [x] All user inputs are validated
  - [x] Type checking is enforced
  - [x] Bounds checking is implemented

- [x] **SQL Injection**
  - [x] All queries use parameterized statements
  - [x] No raw SQL with user input
  - [x] Eloquent ORM used throughout

- [x] **XSS Protection**
  - [x] Output is escaped in views (Blade automatic escaping)
  - [x] No unescaped user input in responses

- [x] **CSRF Protection**
  - [x] CSRF tokens required for state-changing operations
  - [x] API routes properly secured

- [x] **Authentication & Authorization**
  - [x] Policy integration available (`HasEligibility` trait)
  - [x] Gate checks documented
  - [x] Examples show proper authorization

- [x] **Data Protection**
  - [x] Sensitive data masking in audit logs
  - [x] Configurable sensitive field list
  - [x] No sensitive data in logs by default

- [x] **Rate Limiting**
  - [x] Documentation includes rate limiting examples
  - [x] Throttling middleware documented

- [x] **Dependencies**
  - [x] All dependencies are up to date
  - [x] No known vulnerabilities in dependencies
  - [x] Regular dependency updates via Dependabot

- [ ] **Penetration Testing** (PENDING)
  - [ ] Third-party security audit
  - [ ] Automated security scanning
  - [ ] Manual penetration testing

- [x] **Documentation**
  - [x] Security best practices documented
  - [x] Examples show secure implementations
  - [x] Common vulnerabilities addressed

### Ongoing Security Practices

- **Regular Updates**: Monitor for security advisories
- **Dependency Scanning**: Automated via Dependabot
- **Code Review**: All PRs reviewed for security issues
- **Testing**: Security-focused test cases included
- **Community**: Encourage responsible disclosure

## Vulnerability Reporting

### How to Report

If you discover a security vulnerability, please follow these steps:

1. **Do NOT** open a public GitHub issue
2. **Email** the maintainers directly at: [security contact email]
3. **Include**:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 5 business days
- **Status Updates**: Every 7 days until resolved
- **Fix Timeline**: Critical issues within 30 days
- **Public Disclosure**: After fix is released

### Security Advisories

Security advisories will be published:

- On GitHub Security Advisories
- In release notes
- In CHANGELOG.md

### Hall of Fame

Contributors who responsibly disclose security issues will be credited (with permission) in:

- SECURITY.md
- Release notes
- Project README

## Breaking Changes Policy

### What Constitutes a Breaking Change

Breaking changes include:

1. **Removing public methods or properties**
2. **Changing method signatures** (parameters, return types)
3. **Removing configuration keys**
4. **Changing database schema** (removing columns, changing types)
5. **Changing default behavior** that affects existing functionality

### Non-Breaking Changes

These are considered **safe** in minor/patch versions:

1. **Adding new methods** to classes
2. **Adding new optional parameters** (with defaults)
3. **Adding new configuration keys**
4. **Adding new database columns**
5. **Bug fixes** that restore intended behavior
6. **Performance improvements**
7. **Internal refactoring** (no public API changes)

### Deprecation Process

Before removing features:

1. **Deprecation Notice**: Feature marked as deprecated in current minor version
2. **Documentation**: Deprecation documented with migration path
3. **Warnings**: Runtime warnings added (when possible)
4. **Grace Period**: Minimum one major version cycle (e.g., deprecated in v1.5, removed in v2.0)

Example:

```php
/**
 * @deprecated since v1.5, use evaluateBatch() instead
 * Will be removed in v2.0
 */
public function bulkEvaluate(array $items): array
{
    trigger_error(
        'bulkEvaluate() is deprecated, use evaluateBatch() instead',
        E_USER_DEPRECATED
    );

    return $this->evaluateBatch($items);
}
```

### Version Lifecycle

- **v1.x**: Current stable, receives all updates
- **v0.x**: Legacy, security fixes only
- **End of Life**: Announced 6 months in advance

### Migration Guides

Major version upgrades will include:

- **UPGRADE.md** file with detailed instructions
- **Breaking changes** clearly documented
- **Code examples** showing before/after
- **Automated upgrade tools** (when possible)

## Compliance

### GDPR Considerations

When using Eligify with personal data:

1. **Data Minimization**: Only collect necessary fields
2. **Purpose Limitation**: Document why data is evaluated
3. **Audit Trails**: Enabled by default for compliance
4. **Right to Deletion**: Implement data cleanup procedures
5. **Data Portability**: Audit logs exportable to JSON/CSV

### SOC 2 / ISO 27001

For compliance-critical environments:

1. **Audit Logging**: Comprehensive audit trail available
2. **Access Controls**: Use Laravel policies and gates
3. **Data Encryption**: Encrypt sensitive fields at rest
4. **Monitoring**: Implement application monitoring
5. **Documentation**: Comprehensive docs available

## Security Resources

### Internal Documentation

- [Configuration Guide](configuration.md)
- [Production Deployment](production-deployment.md)
- [Policy Integration](policy-integration.md)

### External Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)

### Security Tools

- **PHPStan**: Static analysis (Level 5)
- **Laravel Pint**: Code style enforcement
- **Dependabot**: Automated dependency updates
- **GitHub Security Advisories**: Vulnerability tracking

---

**Questions about security or API stability?** Open a discussion on GitHub or contact the maintainers.
