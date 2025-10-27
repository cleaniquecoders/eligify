# Contributing to Eligify

First off, thank you for considering contributing to Eligify! It's people like you that make Eligify such a great tool for the Laravel community.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How Can I Contribute?](#how-can-i-contribute)
- [Style Guidelines](#style-guidelines)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Community](#community)

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

**Examples of behavior that contributes to creating a positive environment include:**

- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Examples of unacceptable behavior include:**

- The use of sexualized language or imagery and unwelcome sexual attention or advances
- Trolling, insulting/derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without explicit permission
- Other conduct which could reasonably be considered inappropriate in a professional setting

## Getting Started

### Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.4+** (cutting-edge requirement)
- **Composer** (latest version recommended)
- **Git** for version control
- **Laravel 11.x or 12.x** for testing integration

### Fork and Clone

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:

   ```bash
   git clone https://github.com/YOUR-USERNAME/eligify.git
   cd eligify
   ```

3. **Add the upstream repository**:

   ```bash
   git remote add upstream https://github.com/cleaniquecoders/eligify.git
   ```

## Development Setup

### Install Dependencies

```bash
composer install
```

### Run Tests

Before making any changes, ensure all tests pass:

```bash
composer test
```

### Code Quality Tools

Run all quality checks:

```bash
# Static analysis
composer analyse

# Code formatting
composer format

# Run all tests with coverage
composer test-coverage
```

### Database Setup for Testing

The package uses Orchestra Testbench for Laravel package testing. Tests automatically set up an in-memory SQLite database. No manual database setup is required.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

**Bug Report Template:**

```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Create criteria with '...'
2. Add rule '...'
3. Evaluate with '...'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Code Sample**
```php
// Your code here
```

**Environment:**

- PHP Version: [e.g., 8.4.0]
- Laravel Version: [e.g., 11.x]
- Eligify Version: [e.g., 1.0.0]

**Additional context**
Add any other context about the problem here.

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, include:

**Enhancement Template:**

```markdown
**Is your feature request related to a problem?**
A clear and concise description of what the problem is.

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear and concise description of any alternative solutions or features you've considered.

**Code Example**
```php
// How you envision using the feature
Eligify::criteria('example')
    ->yourNewFeature()
    ->evaluate($data);
```

**Additional context**
Add any other context or screenshots about the feature request here.

### Your First Code Contribution

Unsure where to begin? Look for issues tagged with:

- `good first issue` - Simple issues perfect for newcomers
- `help wanted` - Issues where we need community assistance
- `documentation` - Documentation improvements

### Pull Requests

1. **Create a new branch** for your feature/fix:

   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/your-bug-fix
   ```

2. **Make your changes** following our style guidelines

3. **Write or update tests** for your changes

4. **Run all quality checks**:

   ```bash
   composer test
   composer analyse
   composer format
   ```

5. **Commit your changes** with clear, descriptive messages:

   ```bash
   git commit -m "Add feature: description of feature"
   # or
   git commit -m "Fix: description of bug fix"
   ```

6. **Push to your fork**:

   ```bash
   git push origin feature/your-feature-name
   ```

7. **Open a Pull Request** on GitHub

## Style Guidelines

### PHP Code Style

We follow **Laravel Pint** for code formatting. The configuration is already set up in the project.

**Key conventions:**

- Use **PSR-12** coding standard
- Use **type hints** for all parameters and return types
- Use **strict types** declaration: `declare(strict_types=1);`
- Prefer **explicit over implicit** - make code intentions clear
- Use **descriptive variable names** - avoid abbreviations

**Example:**

```php
<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Engine;

class RuleEngine
{
    public function evaluate(array $rules, array $data): array
    {
        // Implementation
    }
}
```

### Documentation Style

- Use **clear, concise language**
- Include **code examples** where appropriate
- Add **PHPDoc blocks** for all public methods
- Document **complex logic** with inline comments
- Keep **README updated** with new features

**PHPDoc Example:**

```php
/**
 * Evaluate data against defined criteria rules.
 *
 * @param  array  $data  The data to evaluate
 * @return array{passed: bool, score: int, failed_rules: array, decision: string}
 *
 * @throws \InvalidArgumentException When data structure is invalid
 */
public function evaluate(array $data): array
{
    // Implementation
}
```

## Testing Guidelines

### Test Requirements

- **All new features** must include tests
- **All bug fixes** must include regression tests
- Maintain **minimum 80% code coverage**
- Tests must be **independent** and **idempotent**

### Test Structure

We use **Pest PHP** for testing. Follow this structure:

```php
<?php

use CleaniqueCoders\Eligify\Facades\Eligify;

it('can evaluate criteria with multiple rules', function () {
    // Arrange
    $criteria = Eligify::criteria('test')
        ->addRule('age', '>=', 18)
        ->addRule('income', '>=', 3000);

    $applicant = [
        'age' => 25,
        'income' => 5000,
    ];

    // Act
    $result = $criteria->evaluate($applicant);

    // Assert
    expect($result['passed'])->toBeTrue()
        ->and($result['score'])->toBeGreaterThan(0)
        ->and($result['failed_rules'])->toBeEmpty();
});
```

### Test Categories

- **Unit Tests** (`tests/Unit/`) - Test individual classes and methods
- **Feature Tests** (`tests/Feature/`) - Test complete workflows
- **Integration Tests** - Test database persistence and relationships

### Running Specific Tests

```bash
# Run specific test file
composer test -- tests/Feature/WorkflowTest.php

# Run tests with filter
composer test -- --filter=workflow

# Run with coverage
composer test-coverage
```

## Pull Request Process

### Before Submitting

1. ✅ All tests pass (`composer test`)
2. ✅ Static analysis passes (`composer analyse`)
3. ✅ Code is formatted (`composer format`)
4. ✅ Documentation is updated
5. ✅ CHANGELOG.md is updated (if applicable)

### PR Title Format

Use conventional commit format:

- `feat: Add support for custom scoring algorithms`
- `fix: Resolve UUID constraint issue in evaluations`
- `docs: Update configuration guide with new options`
- `test: Add workflow callback tests`
- `refactor: Simplify rule engine evaluation logic`
- `perf: Optimize batch evaluation performance`

### PR Description Template

```markdown
## Description
Brief description of the changes

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Checklist
- [ ] My code follows the style guidelines of this project
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] Any dependent changes have been merged and published

## Testing
How has this been tested? Please describe the tests you ran.

## Screenshots (if applicable)
Add screenshots to help explain your changes.

## Related Issues
Fixes #(issue number)

### Review Process

1. **Automated checks** must pass (GitHub Actions)
2. **Code review** by at least one maintainer
3. **Discussion** and potential revisions
4. **Approval** and merge by maintainer

### After Your PR is Merged

1. **Delete your branch** (both local and remote)
2. **Pull the latest changes** from upstream:

   ```bash
   git checkout main
   git pull upstream main
   ```

3. **Update your fork**:

   ```bash
   git push origin main
   ```

## Community

### Where to Get Help

- **Documentation**: Check the `/docs` directory
- **Examples**: See `/examples` for real-world use cases
- **Issues**: Search existing GitHub issues
- **Discussions**: Use GitHub Discussions for questions

### Communication Guidelines

- Be respectful and professional
- Provide context and examples
- Search before asking questions
- Help others when you can
- Stay on topic

## Recognition

Contributors will be recognized in:

- **CHANGELOG.md** for significant contributions
- **README.md** contributors section
- GitHub's contributor graph

## License

By contributing to Eligify, you agree that your contributions will be licensed under the same license as the project (MIT License).

---

Thank you for contributing to Eligify! 🎉

**Questions?** Feel free to open a discussion or contact the maintainers.
