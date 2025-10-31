<?php

use CleaniqueCoders\Eligify\Enums\FieldType;
use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Enums\ScoringMethod;

// config for CleaniqueCoders/Eligify
return [

    /*
    |--------------------------------------------------------------------------
    | UI / Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Telescope-style, opt-in dashboard for managing Eligify. Developers can
    | enable it and control access via a Gate or a custom authorization closure.
    |
    */
    'ui' => [
        // NEW: Theme selection
        'theme' => env('ELIGIFY_UI_THEME', 'tailwindcss'), // 'tailwindcss' | 'bootstrap'
        // Turn the Eligify Dashboard on/off
        'enabled' => env('ELIGIFY_UI_ENABLED', false),

        // Route prefix for the dashboard (e.g., /eligify)
        'route_prefix' => env('ELIGIFY_UI_ROUTE_PREFIX', 'eligify'),

        // Middleware applied to all dashboard routes
        'middleware' => ['web'],

        // Gate name used to authorize dashboard access (if no auth closure is set)
        'gate' => env('ELIGIFY_UI_GATE', 'viewEligify'),

        // Optional authorization closure (like Telescope::auth)
        // Example: fn ($request) => app()->environment('local') || Gate::check('viewEligify')
        'auth' => null,

        // Basic branding options for the dashboard layout
        'brand' => [
            'name' => env('ELIGIFY_UI_BRAND_NAME', 'Eligify'),
            'logo' => env('ELIGIFY_UI_BRAND_LOGO', null), // path/URL or null to use text
        ],

        // Asset CDNs
        'assets' => [
            'use_cdn' => env('ELIGIFY_UI_ASSETS_USE_CDN', true),
            'tailwind_cdn' => env('ELIGIFY_UI_TAILWIND_CDN', 'https://cdn.tailwindcss.com'),
            'alpine_cdn' => env('ELIGIFY_UI_ALPINE_CDN', 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js'),
            'bootstrap_css_cdn' => env('ELIGIFY_UI_BOOTSTRAP_CSS', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'),
            'bootstrap_js_cdn' => env('ELIGIFY_UI_BOOTSTRAP_JS', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Scoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how eligibility scores are calculated by default.
    | These settings can be overridden per criteria.
    |
    */
    'scoring' => [
        // Default passing score threshold (0-100)
        'pass_threshold' => env('ELIGIFY_SCORING_PASS_THRESHOLD', 65),

        // Maximum possible score
        'max_score' => env('ELIGIFY_SCORING_MAX_SCORE', 100),

        // Minimum possible score
        'min_score' => env('ELIGIFY_SCORING_MIN_SCORE', 0),

        // Default scoring method: use ScoringMethod enum
        'method' => env('ELIGIFY_SCORING_METHOD', ScoringMethod::WEIGHTED->value),

        // Penalty for failed rules (subtracted from total)
        'failure_penalty' => env('ELIGIFY_SCORING_FAILURE_PENALTY', 5),

        // Bonus for exceeding expectations
        'excellence_bonus' => env('ELIGIFY_SCORING_EXCELLENCE_BONUS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Rule Operators
    |--------------------------------------------------------------------------
    |
    | Define all comparison operators that can be used in rules.
    | Each operator maps to its implementation method.
    |
    */
    'operators' => [
        // Numeric Comparisons
        RuleOperator::EQUAL->value => [
            'name' => RuleOperator::EQUAL->label(),
            'description' => RuleOperator::EQUAL->description(),
            'types' => [FieldType::NUMERIC->value, FieldType::STRING->value, FieldType::BOOLEAN->value],
            'example' => "addRule('age', '==', 25)",
        ],
        RuleOperator::NOT_EQUAL->value => [
            'name' => RuleOperator::NOT_EQUAL->label(),
            'description' => RuleOperator::NOT_EQUAL->description(),
            'types' => [FieldType::NUMERIC->value, FieldType::STRING->value, FieldType::BOOLEAN->value],
            'example' => "addRule('status', '!=', 'banned')",
        ],
        RuleOperator::GREATER_THAN->value => [
            'name' => RuleOperator::GREATER_THAN->label(),
            'description' => RuleOperator::GREATER_THAN->description(),
            'types' => [FieldType::NUMERIC->value, FieldType::DATE->value],
            'example' => "addRule('income', '>', 50000)",
        ],
        RuleOperator::GREATER_THAN_OR_EQUAL->value => [
            'name' => RuleOperator::GREATER_THAN_OR_EQUAL->label(),
            'description' => RuleOperator::GREATER_THAN_OR_EQUAL->description(),
            'types' => [FieldType::NUMERIC->value, FieldType::DATE->value],
            'example' => "addRule('credit_score', '>=', 650)",
        ],
        RuleOperator::LESS_THAN->value => [
            'name' => RuleOperator::LESS_THAN->label(),
            'description' => RuleOperator::LESS_THAN->description(),
            'types' => [FieldType::NUMERIC->value, FieldType::DATE->value],
            'example' => "addRule('debt_ratio', '<', 0.4)",
        ],
        RuleOperator::LESS_THAN_OR_EQUAL->value => [
            'name' => RuleOperator::LESS_THAN_OR_EQUAL->label(),
            'description' => RuleOperator::LESS_THAN_OR_EQUAL->description(),
            'types' => [FieldType::NUMERIC->value, FieldType::DATE->value],
            'example' => "addRule('active_loans', '<=', 2)",
        ],

        // Array/Set Operations
        RuleOperator::IN->value => [
            'name' => RuleOperator::IN->label(),
            'description' => RuleOperator::IN->description(),
            'types' => [FieldType::ARRAY->value, FieldType::STRING->value, FieldType::NUMERIC->value],
            'example' => "addRule('country', 'in', ['US', 'CA', 'UK'])",
        ],
        RuleOperator::NOT_IN->value => [
            'name' => RuleOperator::NOT_IN->label(),
            'description' => RuleOperator::NOT_IN->description(),
            'types' => [FieldType::ARRAY->value, FieldType::STRING->value, FieldType::NUMERIC->value],
            'example' => "addRule('status', 'not_in', ['banned', 'suspended'])",
        ],

        // Range Operations
        RuleOperator::BETWEEN->value => [
            'name' => RuleOperator::BETWEEN->label(),
            'description' => RuleOperator::BETWEEN->description(),
            'types' => [FieldType::NUMERIC->value, FieldType::DATE->value],
            'example' => "addRule('age', 'between', [18, 65])",
        ],
        RuleOperator::NOT_BETWEEN->value => [
            'name' => RuleOperator::NOT_BETWEEN->label(),
            'description' => RuleOperator::NOT_BETWEEN->description(),
            'types' => [FieldType::NUMERIC->value, FieldType::DATE->value],
            'example' => "addRule('risk_score', 'not_between', [80, 100])",
        ],

        // String Operations
        RuleOperator::CONTAINS->value => [
            'name' => RuleOperator::CONTAINS->label(),
            'description' => RuleOperator::CONTAINS->description(),
            'types' => [FieldType::STRING->value],
            'example' => "addRule('email', 'contains', '@company.com')",
        ],
        RuleOperator::STARTS_WITH->value => [
            'name' => RuleOperator::STARTS_WITH->label(),
            'description' => RuleOperator::STARTS_WITH->description(),
            'types' => [FieldType::STRING->value],
            'example' => "addRule('account_number', 'starts_with', 'ACC')",
        ],
        RuleOperator::ENDS_WITH->value => [
            'name' => RuleOperator::ENDS_WITH->label(),
            'description' => RuleOperator::ENDS_WITH->description(),
            'types' => [FieldType::STRING->value],
            'example' => "addRule('email', 'ends_with', '.edu')",
        ],

        // Existence Operations
        RuleOperator::EXISTS->value => [
            'name' => RuleOperator::EXISTS->label(),
            'description' => RuleOperator::EXISTS->description(),
            'types' => ['any'],
            'example' => "addRule('social_security', 'exists', true)",
        ],
        RuleOperator::NOT_EXISTS->value => [
            'name' => RuleOperator::NOT_EXISTS->label(),
            'description' => RuleOperator::NOT_EXISTS->description(),
            'types' => ['any'],
            'example' => "addRule('bankruptcy_record', 'not_exists', true)",
        ],

        // Pattern Matching
        RuleOperator::REGEX->value => [
            'name' => RuleOperator::REGEX->label(),
            'description' => RuleOperator::REGEX->description(),
            'types' => [FieldType::STRING->value],
            'example' => "addRule('phone', 'regex', '/^\\+1[0-9]{10}$/')",
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Data Types
    |--------------------------------------------------------------------------
    |
    | Define supported data types for rule fields and their validation.
    |
    */
    'field_types' => [
        FieldType::NUMERIC->value => [
            'label' => FieldType::NUMERIC->label(),
            'description' => FieldType::NUMERIC->description(),
            'validation' => 'numeric',
            'cast' => 'float',
            'operators' => [
                RuleOperator::EQUAL->value,
                RuleOperator::NOT_EQUAL->value,
                RuleOperator::GREATER_THAN->value,
                RuleOperator::GREATER_THAN_OR_EQUAL->value,
                RuleOperator::LESS_THAN->value,
                RuleOperator::LESS_THAN_OR_EQUAL->value,
                RuleOperator::BETWEEN->value,
                RuleOperator::NOT_BETWEEN->value,
                RuleOperator::IN->value,
                RuleOperator::NOT_IN->value,
            ],
        ],
        FieldType::INTEGER->value => [
            'label' => FieldType::INTEGER->label(),
            'description' => FieldType::INTEGER->description(),
            'validation' => 'integer',
            'cast' => 'integer',
            'operators' => [
                RuleOperator::EQUAL->value,
                RuleOperator::NOT_EQUAL->value,
                RuleOperator::GREATER_THAN->value,
                RuleOperator::GREATER_THAN_OR_EQUAL->value,
                RuleOperator::LESS_THAN->value,
                RuleOperator::LESS_THAN_OR_EQUAL->value,
                RuleOperator::BETWEEN->value,
                RuleOperator::NOT_BETWEEN->value,
                RuleOperator::IN->value,
                RuleOperator::NOT_IN->value,
            ],
        ],
        FieldType::STRING->value => [
            'label' => FieldType::STRING->label(),
            'description' => FieldType::STRING->description(),
            'validation' => 'string',
            'cast' => 'string',
            'operators' => [
                RuleOperator::EQUAL->value,
                RuleOperator::NOT_EQUAL->value,
                RuleOperator::IN->value,
                RuleOperator::NOT_IN->value,
                RuleOperator::CONTAINS->value,
                RuleOperator::STARTS_WITH->value,
                RuleOperator::ENDS_WITH->value,
                RuleOperator::REGEX->value,
            ],
        ],
        FieldType::BOOLEAN->value => [
            'label' => FieldType::BOOLEAN->label(),
            'description' => FieldType::BOOLEAN->description(),
            'validation' => 'boolean',
            'cast' => 'boolean',
            'operators' => [
                RuleOperator::EQUAL->value,
                RuleOperator::NOT_EQUAL->value,
            ],
        ],
        FieldType::DATE->value => [
            'label' => FieldType::DATE->label(),
            'description' => FieldType::DATE->description(),
            'validation' => 'date',
            'cast' => 'datetime',
            'operators' => [
                RuleOperator::EQUAL->value,
                RuleOperator::NOT_EQUAL->value,
                RuleOperator::GREATER_THAN->value,
                RuleOperator::GREATER_THAN_OR_EQUAL->value,
                RuleOperator::LESS_THAN->value,
                RuleOperator::LESS_THAN_OR_EQUAL->value,
                RuleOperator::BETWEEN->value,
                RuleOperator::NOT_BETWEEN->value,
            ],
        ],
        FieldType::ARRAY->value => [
            'label' => FieldType::ARRAY->label(),
            'description' => FieldType::ARRAY->description(),
            'validation' => 'array',
            'cast' => 'array',
            'operators' => [
                RuleOperator::IN->value,
                RuleOperator::NOT_IN->value,
                RuleOperator::CONTAINS->value,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Rule Weights
    |--------------------------------------------------------------------------
    |
    | Default weights for different types of rules.
    | Higher weights have more impact on the final score.
    |
    */
    'rule_weights' => [
        RulePriority::CRITICAL->value => 100,   // Must pass for eligibility
        RulePriority::HIGH->value => 75,        // Very important
        RulePriority::MEDIUM->value => 50,      // Standard importance
        RulePriority::LOW->value => 25,         // Nice to have
        RulePriority::INFO->value => 0,         // Informational only
    ],

    /*
    |--------------------------------------------------------------------------
    | Evaluation Settings
    |--------------------------------------------------------------------------
    |
    | Configure how evaluations are performed and cached.
    |
    */
    'evaluation' => [
        // Enable/disable evaluation caching
        'cache_enabled' => env('ELIGIFY_EVALUATION_CACHE_ENABLED', true),

        // Cache TTL in minutes
        'cache_ttl' => env('ELIGIFY_EVALUATION_CACHE_TTL', 60),

        // Cache key prefix
        'cache_prefix' => env('ELIGIFY_EVALUATION_CACHE_PREFIX', 'eligify_eval'),

        // Stop on first failure (fail-fast)
        'fail_fast' => env('ELIGIFY_EVALUATION_FAIL_FAST', false),

        // Maximum evaluation time in seconds
        'max_execution_time' => env('ELIGIFY_EVALUATION_MAX_EXECUTION_TIME', 30),

        // Enable detailed logging
        'detailed_logging' => env('ELIGIFY_EVALUATION_DETAILED_LOGGING', true),

        // Default decision labels
        'decisions' => [
            'pass' => ['Approved', 'Accepted', 'Qualified', 'Eligible'],
            'fail' => ['Rejected', 'Declined', 'Not Qualified', 'Ineligible'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Configure audit logging behavior.
    |
    */
    'audit' => [
        // Enable/disable audit logging
        'enabled' => env('ELIGIFY_AUDIT_ENABLED', true),

        // Events to audit
        'events' => [
            'evaluation_completed',
            'rule_created',
            'rule_updated',
            'rule_deleted',
            'rule_executed',
            'criteria_created',
            'criteria_updated',
            'criteria_activated',
            'criteria_deactivated',
        ],

        // Automatically clean old audit logs
        'auto_cleanup' => env('ELIGIFY_AUDIT_AUTO_CLEANUP', true),

        // Keep audit logs for this many days
        'retention_days' => env('ELIGIFY_AUDIT_RETENTION_DAYS', 365),

        // Include sensitive data in audit logs
        'include_sensitive_data' => env('ELIGIFY_AUDIT_INCLUDE_SENSITIVE', false),

        // Schedule automatic cleanup (cron expression or null to disable)
        'cleanup_schedule' => env('ELIGIFY_AUDIT_CLEANUP_SCHEDULE', 'daily'), // daily, weekly, monthly, or cron
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Use Case Presets
    |--------------------------------------------------------------------------
    |
    | Pre-configured rule sets for common eligibility scenarios.
    |
    */
    'presets' => [
        'loan_approval' => [
            'name' => 'Loan Approval',
            'description' => 'Standard loan approval criteria',
            'pass_threshold' => 70,
            'rules' => [
                ['field' => 'credit_score', 'operator' => RuleOperator::GREATER_THAN_OR_EQUAL->value, 'value' => 650, 'weight' => 8],
                ['field' => 'income', 'operator' => RuleOperator::GREATER_THAN_OR_EQUAL->value, 'value' => 30000, 'weight' => 7],
                ['field' => 'debt_to_income_ratio', 'operator' => RuleOperator::LESS_THAN_OR_EQUAL->value, 'value' => 0.4, 'weight' => 6],
                ['field' => 'employment_years', 'operator' => RuleOperator::GREATER_THAN_OR_EQUAL->value, 'value' => 2, 'weight' => 5],
                ['field' => 'active_bankruptcies', 'operator' => RuleOperator::EQUAL->value, 'value' => 0, 'weight' => 10],
            ],
        ],

        'scholarship_eligibility' => [
            'name' => 'Scholarship Eligibility',
            'description' => 'Academic scholarship criteria',
            'pass_threshold' => 75,
            'rules' => [
                ['field' => 'gpa', 'operator' => RuleOperator::GREATER_THAN_OR_EQUAL->value, 'value' => 3.5, 'weight' => 9],
                ['field' => 'family_income', 'operator' => RuleOperator::LESS_THAN_OR_EQUAL->value, 'value' => 60000, 'weight' => 6],
                ['field' => 'community_service_hours', 'operator' => RuleOperator::GREATER_THAN_OR_EQUAL->value, 'value' => 50, 'weight' => 4],
                ['field' => 'enrollment_status', 'operator' => RuleOperator::EQUAL->value, 'value' => 'full_time', 'weight' => 7],
            ],
        ],

        'job_application' => [
            'name' => 'Job Application',
            'description' => 'Standard job application screening',
            'pass_threshold' => 65,
            'rules' => [
                ['field' => 'years_experience', 'operator' => RuleOperator::GREATER_THAN_OR_EQUAL->value, 'value' => 3, 'weight' => 8],
                ['field' => 'education_level', 'operator' => RuleOperator::IN->value, 'value' => ['bachelor', 'master', 'phd'], 'weight' => 6],
                ['field' => 'skills_match_percentage', 'operator' => RuleOperator::GREATER_THAN_OR_EQUAL->value, 'value' => 70, 'weight' => 7],
                ['field' => 'background_check', 'operator' => RuleOperator::EQUAL->value, 'value' => 'passed', 'weight' => 9],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings.
    |
    */
    'performance' => [
        // Enable query optimization
        'optimize_queries' => env('ELIGIFY_PERF_OPTIMIZE_QUERIES', true),

        // Batch evaluation size
        'batch_size' => env('ELIGIFY_PERF_BATCH_SIZE', 100),

        // Enable rule compilation for better performance
        'compile_rules' => env('ELIGIFY_PERF_COMPILE_RULES', true),

        // Rule compilation cache TTL in minutes
        'compilation_cache_ttl' => env('ELIGIFY_PERF_COMPILATION_CACHE_TTL', 1440), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow Configuration
    |--------------------------------------------------------------------------
    |
    | Configure workflow and callback behavior.
    |
    */
    'workflow' => [
        // Enable workflow system
        'enabled' => env('ELIGIFY_WORKFLOW_ENABLED', true),

        // Callback execution timeout in seconds
        'callback_timeout' => env('ELIGIFY_WORKFLOW_CALLBACK_TIMEOUT', 30),

        // Log callback execution errors
        'log_callback_errors' => env('ELIGIFY_WORKFLOW_LOG_CALLBACK_ERRORS', true),

        // Fail evaluation if callback throws error
        'fail_on_callback_error' => env('ELIGIFY_WORKFLOW_FAIL_ON_CALLBACK_ERROR', false),

        // Dispatch Laravel events
        'dispatch_events' => env('ELIGIFY_WORKFLOW_DISPATCH_EVENTS', true),

        // Enable async callbacks (requires queue)
        'enable_async_callbacks' => env('ELIGIFY_WORKFLOW_ASYNC', false),

        // Queue connection for async callbacks
        'async_queue_connection' => env('ELIGIFY_WORKFLOW_QUEUE_CONNECTION', 'default'),

        // Queue name for async callbacks
        'async_queue_name' => env('ELIGIFY_WORKFLOW_QUEUE_NAME', 'eligify'),

        // Retry failed async callbacks
        'async_retry_attempts' => env('ELIGIFY_WORKFLOW_ASYNC_RETRY_ATTEMPTS', 3),

        // Score thresholds for automatic callbacks
        'score_thresholds' => [
            'excellent' => 90,
            'good' => 80,
            'average' => 60,
            'poor' => 40,
        ],

        // Default callback conditions
        'default_conditions' => [
            'max_execution_time' => 10, // seconds
            'memory_limit' => 128, // MB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Data Extraction Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how data is extracted from Eloquent models for eligibility
    | evaluation. These settings control what data is included and how
    | it's processed before evaluation.
    |
    */
    'model_extraction' => [
        // Include timestamp-based computed fields
        'include_timestamps' => true,

        // Include relationship data and counts
        'include_relationships' => true,

        // Include computed fields like age, duration, etc.
        'include_computed_fields' => true,

        // Maximum depth for relationship extraction
        'max_relationship_depth' => 2,

        // Exclude sensitive fields from extraction
        'exclude_sensitive_fields' => true,

        // List of sensitive fields to exclude
        'sensitive_fields' => [
            'password',
            'remember_token',
            'api_token',
            'secret',
            'private_key',
            'access_token',
            'refresh_token',
        ],

        // Date format for extracted date fields
        'date_format' => 'Y-m-d H:i:s',

        // Enable automatic model-specific field extraction
        'auto_configure' => true,

        // Model mapping classes
        // Map model class names to their mapping class implementations
        'model_mappings' => [
            'App\Models\User' => \CleaniqueCoders\Eligify\Data\Mappings\UserModelMapping::class,
            // Add more mappings here:
            // 'App\Models\Order' => \App\Eligify\Mappings\OrderModelMapping::class,
            // 'App\Models\Customer' => \App\Eligify\Mappings\CustomerModelMapping::class,
        ],

        // Default mapping class to use if no specific mapping is found
        'default_mapping' => null,
    ],

];
