# Eligify

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/eligify.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/eligify)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/eligify/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/eligify/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/eligify/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/eligify/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/eligify.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/eligify)

Eligify is a flexible rule and criteria engine that determines whether an entity — such as a person, application, or transaction — meets the defined acceptance conditions. It powers decision-making systems by making eligibility data-driven, traceable, and automatable.

## Features

- 🧱 **Criteria Builder** - Define eligibility requirements with weighted rules
- ⚖️ **Rule Engine** - 16+ operators for comprehensive validation
- 🎯 **Evaluator** - Real-time eligibility checks with detailed scoring
- 🔄 **Workflow Manager** - Trigger actions on pass/fail/excellent scores
- 🧾 **Audit Log** - Complete traceability of all decisions
- 🎨 **Web Dashboard** - Optional Telescope-style UI for management (disabled by default)
- 🧩 **Model Integration** - Seamless Laravel Eloquent integration
- 📊 **Flexible Scoring** - Weighted, pass/fail, percentage, and custom methods

## Installation

You can install the package via composer:

```bash
composer require cleaniquecoders/eligify
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="eligify-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="eligify-config"
```

## Usage

### Quick Example

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Define criteria
$criteria = Eligify::criteria('loan_approval')
    ->addRule('credit_score', '>=', 650, 30)
    ->addRule('annual_income', '>=', 30000, 25)
    ->addRule('debt_ratio', '<=', 43, 20)
    ->passThreshold(70)
    ->save();

// Evaluate
$result = Eligify::evaluate('loan_approval', [
    'credit_score' => 720,
    'annual_income' => 55000,
    'debt_ratio' => 35,
]);

// Result: ['passed' => true, 'score' => 85, 'decision' => 'Approved', ...]
```

### Optional Web Dashboard

Enable the dashboard for visual management:

```bash
# .env
ELIGIFY_UI_ENABLED=true
```

**Access:** `http://your-app.test/eligify`

![Dashboard](screenshots/01-dashboard-overview.png)

**Authorization (Production):**

```php
// AppServiceProvider.php
Gate::define('viewEligify', function ($user) {
    return $user->hasRole('admin');
});
```

### Complete Documentation

📖 **[Full Documentation](docs/README.md)**

**Key Guides:**
- [Quick Start Guide](docs/README.md#quick-start)
- [UI Setup Guide](docs/ui-setup-guide.md) - Dashboard configuration & authorization
- [Environment Variables](docs/environment-variables.md) - Complete `.env` reference
- [Configuration Guide](docs/configuration.md) - All config options
- [Model Data Extraction](docs/model-data-extraction.md) - Extract & evaluate Eloquent models
- [CLI Commands](docs/cli-commands.md) - Artisan command reference
- [Examples](examples/README.md) - 12+ real-world examples

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
