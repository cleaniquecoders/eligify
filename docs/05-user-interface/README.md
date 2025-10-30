# User Interface

This section covers the web-based UI components for managing eligibility criteria.

## Overview

Eligify provides optional UI components for:
- Creating and managing criteria
- Testing eligibility evaluations
- Viewing audit logs
- Interactive playground for testing

## Documentation in this Section

- **[Setup Guide](setup.md)** - Installing and configuring the UI
- **[Features](features.md)** - Dashboard, criteria editor, and more
- **[Dynamic Fields](dynamic-fields.md)** - Dynamic field selection
- **[Playground](playground.md)** - Interactive testing environment
- **[Customization](customization.md)** - Branding and customization

## Quick Start

```bash
# Publish UI assets
php artisan vendor:publish --tag="eligify-views"
php artisan vendor:publish --tag="eligify-assets"

# Access the UI
http://your-app.test/eligify
```

## Features

### Criteria Manager
- Visual criteria builder
- Rule configuration
- Scoring method selection
- Import/Export capabilities

### Playground
- Test evaluations in real-time
- Sample data generation
- Result visualization
- Debug mode

### Audit Viewer
- Browse evaluation history
- Filter by criteria, date, result
- Export audit reports
- Compliance tools

### Dashboard
- Criteria overview
- Recent evaluations
- Pass/fail statistics
- Performance metrics

## Technology Stack

- **Livewire** - Dynamic components
- **Tailwind CSS** - Styling
- **Alpine.js** - Interactive elements
- **Laravel** - Backend framework

## Authentication

The UI integrates with Laravel's authentication:

```php
// config/eligify.php
'ui' => [
    'middleware' => ['web', 'auth'],
    'route_prefix' => 'eligify',
],
```

## Related Sections

- [Configuration](../06-configuration/) - UI configuration options
- [Advanced Features](../07-advanced-features/) - Extend UI components
- [Examples](../13-examples/) - UI usage patterns
