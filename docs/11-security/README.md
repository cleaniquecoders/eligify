# Security

This section covers security best practices for Eligify applications.

## Overview

Ensure your eligibility system is secure and protected against common vulnerabilities.

## Documentation in this Section

- **[Best Practices](best-practices.md)** - Security guidelines and patterns
- **[Authorization](authorization.md)** - Access control implementation
- **[Input Validation](input-validation.md)** - Validating rule inputs
- **[Vulnerability Reporting](vulnerability-reporting.md)** - How to report issues

## Security Principles

### 1. Authentication & Authorization

Always protect the UI and API:

```php
// config/eligify.php
'ui' => [
    'middleware' => ['web', 'auth', 'can:manage-eligibility'],
],
```

### 2. Input Validation

Validate all rule inputs:

```php
$criteria->addRule('income', '>=', $request->validated('min_income'));
```

### 3. Audit Logging

Enable comprehensive audit trails:

```php
'audit' => [
    'enabled' => true,
    'log_level' => 'info',
    'include_user' => true,
],
```

### 4. Rate Limiting

Protect against abuse:

```php
Route::middleware('throttle:100,1')->group(function () {
    // Eligify routes
});
```

## Common Security Concerns

### SQL Injection

✅ **Safe:** Eligify uses parameter binding
❌ **Unsafe:** Custom operators with raw SQL

### Code Injection

✅ **Safe:** Predefined operators
❌ **Unsafe:** `eval()` or dynamic code execution in custom operators

### Data Exposure

✅ **Safe:** Audit logs with appropriate access control
❌ **Unsafe:** Publicly accessible evaluation results

### Authorization Bypass

✅ **Safe:** Middleware on all routes
❌ **Unsafe:** Direct access to evaluation logic

## Secure Configuration

```php
// config/eligify.php
return [
    // Require authentication
    'ui' => [
        'middleware' => ['web', 'auth', 'verified'],
    ],

    // Enable audit logging
    'audit' => [
        'enabled' => true,
        'include_user' => true,
        'include_ip' => true,
    ],

    // Validate operators
    'operators' => [
        'allow_custom' => false, // Disable in production
    ],

    // Rate limiting
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 100,
        'decay_minutes' => 1,
    ],
];
```

## Security Checklist

- [ ] Authentication enabled on UI
- [ ] Authorization checks in place
- [ ] Input validation implemented
- [ ] Audit logging enabled
- [ ] Rate limiting configured
- [ ] Custom operators reviewed
- [ ] HTTPS enforced in production
- [ ] Environment variables secured
- [ ] Regular security updates
- [ ] Penetration testing performed

## Reporting Vulnerabilities

If you discover a security vulnerability, please email <security@example.com>.

**Do not** create a public GitHub issue for security vulnerabilities.

## Related Sections

- [Configuration](../06-configuration/) - Security configuration
- [Deployment](../10-deployment/) - Production security
- [Advanced Features](../07-advanced-features/) - Policy integration
