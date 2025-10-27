---
name: Bug Report
about: Create a report to help us improve Eligify
title: '[BUG] '
labels: bug
assignees: ''
---

## Bug Description

A clear and concise description of what the bug is.

## To Reproduce

Steps to reproduce the behavior:

1. Create criteria with '...'
2. Add rule '...'
3. Evaluate with '...'
4. See error

## Code Sample

```php
// Provide a minimal code sample that reproduces the issue
use CleaniqueCoders\Eligify\Facades\Eligify;

$criteria = Eligify::criteria('example')
    ->addRule('field', 'operator', 'value');

$result = $criteria->evaluate($data);
```

## Expected Behavior

A clear and concise description of what you expected to happen.

## Actual Behavior

A clear and concise description of what actually happened.

## Error Messages

```plaintext
Paste any error messages here
```

## Environment

- **PHP Version:** [e.g., 8.4.0]
- **Laravel Version:** [e.g., 11.9.0]
- **Eligify Version:** [e.g., 1.0.0]
- **Database:** [e.g., MySQL 8.0, PostgreSQL 15]
- **Operating System:** [e.g., Ubuntu 22.04, macOS 14]

## Additional Context

Add any other context about the problem here, such as:

- Does this happen consistently or intermittently?
- Did this work in a previous version?
- Any relevant configuration settings?

## Possible Solution

If you have any ideas on how to fix the issue, please share them here.
