# Description

<!-- Provide a brief description of the changes in this PR -->

## Type of Change

<!-- Mark the relevant option with an 'x' -->

- [ ] 🐛 Bug fix (non-breaking change which fixes an issue)
- [ ] ✨ New feature (non-breaking change which adds functionality)
- [ ] 💥 Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] 📝 Documentation update
- [ ] 🎨 Code style update (formatting, renaming)
- [ ] ♻️ Refactoring (no functional changes)
- [ ] ⚡ Performance improvement
- [ ] ✅ Test update
- [ ] 🔧 Configuration change

## Related Issue

<!-- Link to the issue this PR addresses -->

Fixes #(issue number)
Closes #(issue number)
Related to #(issue number)

## Changes Made

<!-- Provide a detailed list of changes -->

- Change 1
- Change 2
- Change 3

## Code Example

<!-- If applicable, show how the new feature works -->

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Example usage of your changes
$criteria = Eligify::criteria('example')
    ->addRule('field', '>=', 100)
    ->evaluate($data);
```

## Testing

<!-- Describe the tests you ran to verify your changes -->

### Test Coverage

- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] Integration tests added/updated
- [ ] All existing tests pass

### Manual Testing

<!-- Describe any manual testing performed -->

1. Test scenario 1
2. Test scenario 2

### Test Commands Run

```bash
composer test
composer analyse
composer format
```

## Checklist

<!-- Mark completed items with an 'x' -->

- [ ] My code follows the style guidelines of this project
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] Any dependent changes have been merged and published
- [ ] I have updated the CHANGELOG.md (if applicable)

## Screenshots / Recordings

<!-- If applicable, add screenshots or recordings to help explain your changes -->

## Breaking Changes

<!-- If this introduces breaking changes, describe them here -->

- [ ] This PR introduces breaking changes

**If yes, describe the breaking changes and migration path:**

<!-- Describe what breaks and how users should migrate -->

## Performance Impact

<!-- Describe any performance implications -->

- [ ] Performance tested
- [ ] No performance impact
- [ ] Performance improved
- [ ] Performance degraded (with justification)

## Additional Notes

<!-- Add any additional notes, concerns, or context for reviewers -->

## Reviewer Notes

<!-- For reviewers: areas to pay special attention to -->

- Please review the changes in: `file/path`
- Special attention needed for: `specific functionality`

---

**By submitting this pull request, I confirm that my contribution is made under the terms of the project's license.**
