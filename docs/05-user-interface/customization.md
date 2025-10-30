# UI Customization

Learn how to customize the Eligify dashboard to match your application's branding and requirements.

## Overview

Eligify's UI can be customized in multiple ways:
- Branding (logo, colors, name)
- Layout and widgets
- Views and components
- Styles and themes
- Functionality

## Branding

### Basic Branding

```php
// config/eligify.php
'ui' => [
    'brand_name' => 'Acme Corp Eligibility System',
    'logo_url' => '/img/acme-logo.png',
    'favicon_url' => '/img/favicon.ico',
],
```

### Colors

```php
'ui' => [
    'colors' => [
        'primary' => '#3B82F6',
        'secondary' => '#8B5CF6',
        'success' => '#10B981',
        'danger' => '#EF4444',
        'warning' => '#F59E0B',
        'info' => '#06B6D4',
    ],
],
```

### Typography

```php
'ui' => [
    'typography' => [
        'font_family' => '"Inter", sans-serif',
        'font_size_base' => '16px',
        'heading_font' => '"Poppins", sans-serif',
    ],
],
```

## Themes

### Built-in Themes

```php
'ui' => [
    'theme' => 'light',  // Options: 'light', 'dark', 'auto'
],
```

### Custom Theme

```php
'ui' => [
    'theme' => 'custom',
    'custom_theme' => [
        'background' => '#FFFFFF',
        'foreground' => '#1F2937',
        'card' => '#F9FAFB',
        'border' => '#E5E7EB',
        'muted' => '#6B7280',
    ],
],
```

## Layout Customization

### Dashboard Widgets

Add custom widgets to the dashboard:

```php
'ui' => [
    'dashboard_widgets' => [
        \App\Eligify\Widgets\EvaluationStatsWidget::class,
        \App\Eligify\Widgets\TopCriteriaWidget::class,
        \App\Eligify\Widgets\RecentActivityWidget::class,
    ],
],
```

### Widget Example

```php
namespace App\Eligify\Widgets;

use Livewire\Component;
use CleaniqueCoders\Eligify\Models\Evaluation;

class EvaluationStatsWidget extends Component
{
    public function render()
    {
        $stats = [
            'today' => Evaluation::whereDate('created_at', today())->count(),
            'week' => Evaluation::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'month' => Evaluation::whereMonth('created_at', now()->month)->count(),
            'pass_rate' => Evaluation::where('passed', true)->count() / Evaluation::count() * 100,
        ];

        return view('eligify.widgets.evaluation-stats', compact('stats'));
    }
}
```

### Sidebar Menu

Customize sidebar navigation:

```php
'ui' => [
    'sidebar_menu' => [
        ['label' => 'Dashboard', 'route' => 'eligify.dashboard', 'icon' => 'home'],
        ['label' => 'Criteria', 'route' => 'eligify.criteria.index', 'icon' => 'list'],
        ['label' => 'Playground', 'route' => 'eligify.playground', 'icon' => 'beaker'],
        ['label' => 'Audit Logs', 'route' => 'eligify.audit', 'icon' => 'document-text'],
        ['label' => 'Reports', 'route' => 'eligify.reports', 'icon' => 'chart-bar'],
        // Add custom menu items
        ['label' => 'Settings', 'route' => 'eligify.settings', 'icon' => 'cog'],
    ],
],
```

## View Customization

### Publishing Views

```bash
php artisan vendor:publish --tag="eligify-views"
```

This publishes views to `resources/views/vendor/eligify/`.

### Customizing Views

Edit published views:

```
resources/views/vendor/eligify/
├── layouts/
│   ├── app.blade.php
│   ├── sidebar.blade.php
│   └── topbar.blade.php
├── criteria/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── playground/
│   └── index.blade.php
└── audit/
    ├── index.blade.php
    └── show.blade.php
```

### Example: Custom Criteria List

```php
{{-- resources/views/vendor/eligify/criteria/index.blade.php --}}

@extends('eligify::layouts.app')

@section('content')
<div class="custom-criteria-list">
    <h1>My Custom Criteria List</h1>

    {{-- Custom content --}}
    <div class="custom-filters">
        {{-- Your custom filters --}}
    </div>

    {{-- Original table or custom table --}}
    <table class="custom-table">
        @foreach($criteria as $criterion)
            <tr>
                <td>{{ $criterion->name }}</td>
                <td>{{ $criterion->rules_count }}</td>
                {{-- Custom columns --}}
            </tr>
        @endforeach
    </table>
</div>
@endsection
```

## Component Customization

### Livewire Components

Override default Livewire components:

```php
// app/Http/Livewire/CriteriaManager.php
namespace App\Http\Livewire;

use CleaniqueCoders\Eligify\Http\Livewire\CriteriaManager as BaseCriteriaManager;

class CriteriaManager extends BaseCriteriaManager
{
    // Override methods
    public function render()
    {
        // Custom logic
        return view('livewire.criteria-manager', [
            'criteria' => $this->getCriteria(),
        ]);
    }
}
```

Register custom component:

```php
// config/eligify.php
'ui' => [
    'components' => [
        'criteria-manager' => \App\Http\Livewire\CriteriaManager::class,
    ],
],
```

## Styling

### Custom CSS

Add custom styles:

```php
// config/eligify.php
'ui' => [
    'custom_css' => [
        '/css/eligify-custom.css',
    ],
],
```

```css
/* public/css/eligify-custom.css */
.eligify-dashboard {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.eligify-card {
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.eligify-btn-primary {
    background-color: #your-brand-color;
}
```

### Custom JavaScript

```php
'ui' => [
    'custom_js' => [
        '/js/eligify-custom.js',
    ],
],
```

```javascript
// public/js/eligify-custom.js
document.addEventListener('DOMContentLoaded', function() {
    // Custom behavior
    console.log('Eligify custom JS loaded');

    // Add custom event listeners
    document.querySelectorAll('.custom-action').forEach(el => {
        el.addEventListener('click', customHandler);
    });
});
```

### Tailwind Configuration

If using Tailwind, extend the configuration:

```javascript
// tailwind.config.js
module.exports = {
    content: [
        './resources/views/vendor/eligify/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                'eligify-primary': '#3B82F6',
                'eligify-secondary': '#8B5CF6',
            },
        },
    },
};
```

## Localization

### Translation Files

Publish translations:

```bash
php artisan vendor:publish --tag="eligify-translations"
```

### Custom Translations

```php
// resources/lang/en/eligify.php
return [
    'criteria' => [
        'title' => 'Eligibility Criteria',
        'create' => 'Create New Criteria',
        'edit' => 'Edit Criteria',
        'delete_confirm' => 'Are you sure you want to delete this criteria?',
    ],
    'playground' => [
        'title' => 'Test Playground',
        'evaluate' => 'Run Evaluation',
    ],
    // ... more translations
];
```

### Using Translations

```php
{{-- In Blade views --}}
{{ __('eligify.criteria.title') }}
{{ trans('eligify.playground.evaluate') }}
```

## Middleware

### Custom UI Middleware

```php
// app/Http/Middleware/CustomEligifyAccess.php
namespace App\Http\Middleware;

use Closure;

class CustomEligifyAccess
{
    public function handle($request, Closure $next)
    {
        if (!$request->user()->canAccessEligify()) {
            abort(403, 'Unauthorized access to Eligify dashboard');
        }

        // Add custom headers
        return $next($request)->header('X-Eligify-Access', 'granted');
    }
}
```

Register middleware:

```php
// config/eligify.php
'ui' => [
    'middleware' => [
        'web',
        'auth',
        \App\Http\Middleware\CustomEligifyAccess::class,
    ],
],
```

## Routes

### Custom Routes

```php
// routes/eligify-custom.php
use Illuminate\Support\Facades\Route;

Route::prefix('eligify')->middleware(config('eligify.ui.middleware'))->group(function () {
    Route::get('/custom-report', [CustomReportController::class, 'index'])->name('eligify.custom-report');
    Route::get('/bulk-operations', [BulkOperationsController::class, 'index'])->name('eligify.bulk-operations');
});
```

Register custom routes:

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    Route::group([], base_path('routes/eligify-custom.php'));
}
```

## Advanced Customization

### Custom Dashboard

Replace the entire dashboard:

```php
// config/eligify.php
'ui' => [
    'dashboard_view' => 'custom.eligify.dashboard',
],
```

```php
{{-- resources/views/custom/eligify/dashboard.blade.php --}}
@extends('eligify::layouts.app')

@section('content')
<div class="custom-dashboard">
    {{-- Your completely custom dashboard --}}
    <div class="custom-stats-grid">
        @foreach($stats as $stat)
            <x-custom-stat-card :stat="$stat" />
        @endforeach
    </div>

    <div class="custom-charts">
        <x-custom-chart type="line" :data="$lineData" />
        <x-custom-chart type="pie" :data="$pieData" />
    </div>
</div>
@endsection
```

### Custom Controllers

Replace default controllers:

```php
// config/eligify.php
'ui' => [
    'controllers' => [
        'criteria' => \App\Http\Controllers\CustomCriteriaController::class,
        'playground' => \App\Http\Controllers\CustomPlaygroundController::class,
    ],
],
```

## Examples

### Example 1: Corporate Theme

```php
// config/eligify.php
'ui' => [
    'brand_name' => 'Corporate Eligibility System',
    'logo_url' => '/img/corporate-logo.svg',
    'theme' => 'custom',
    'custom_theme' => [
        'background' => '#FFFFFF',
        'foreground' => '#1E293B',
        'primary' => '#0F172A',
        'accent' => '#DC2626',
    ],
    'custom_css' => ['/css/corporate-theme.css'],
],
```

### Example 2: Minimal Dashboard

```php
'ui' => [
    'dashboard_widgets' => [
        \App\Eligify\Widgets\SimpleStatsWidget::class,
    ],
    'sidebar_menu' => [
        ['label' => 'Criteria', 'route' => 'eligify.criteria.index'],
        ['label' => 'Test', 'route' => 'eligify.playground'],
    ],
],
```

### Example 3: Multi-Tenant

```php
'ui' => [
    'brand_name' => fn() => auth()->user()->tenant->name . ' Eligibility',
    'logo_url' => fn() => auth()->user()->tenant->logo_url,
    'colors' => fn() => auth()->user()->tenant->brand_colors,
],
```

## Related Documentation

- [UI Features](features.md) - Available features
- [Setup Guide](setup.md) - Installation
- [Dynamic Fields](dynamic-fields.md) - Field customization
- [Playground](playground.md) - Testing interface
