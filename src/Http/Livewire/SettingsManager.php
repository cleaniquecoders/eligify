<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\PhpEnvKeyManager\EnvKeyManager;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsManager extends Component
{
    // UI
    public bool $ui_enabled;

    public string $ui_route_prefix;

    public string $ui_brand_name;

    public bool $ui_assets_use_cdn;

    // Scoring
    #[Validate('integer|min:0|max:100')]
    public int $scoring_pass_threshold;

    public string $scoring_method;

    // Evaluation
    public bool $evaluation_cache_enabled;

    #[Validate('integer|min:1|max:10080')]
    public int $evaluation_cache_ttl;

    public bool $evaluation_fail_fast;

    // Audit
    public bool $audit_enabled;

    #[Validate('integer|min:1|max:3650')]
    public int $audit_retention_days;

    public bool $audit_auto_cleanup;

    // Workflow
    public bool $workflow_enabled;

    public bool $workflow_async;

    public string $workflow_queue_name;

    public string $status = '';

    public string $envPath;

    public function mount(): void
    {
        $this->ui_enabled = (bool) config('eligify.ui.enabled');
        $this->ui_route_prefix = (string) config('eligify.ui.route_prefix');
        $this->ui_brand_name = (string) config('eligify.ui.brand.name');
        $this->ui_assets_use_cdn = (bool) data_get(config('eligify.ui.assets'), 'use_cdn', true);

        $this->scoring_pass_threshold = (int) config('eligify.scoring.pass_threshold');
        $this->scoring_method = (string) config('eligify.scoring.method');

        $this->evaluation_cache_enabled = (bool) config('eligify.evaluation.cache_enabled');
        $this->evaluation_cache_ttl = (int) config('eligify.evaluation.cache_ttl');
        $this->evaluation_fail_fast = (bool) config('eligify.evaluation.fail_fast');

        $this->audit_enabled = (bool) config('eligify.audit.enabled');
        $this->audit_retention_days = (int) config('eligify.audit.retention_days');
        $this->audit_auto_cleanup = (bool) config('eligify.audit.auto_cleanup');

        $this->workflow_enabled = (bool) config('eligify.workflow.enabled');
        $this->workflow_async = (bool) config('eligify.workflow.enable_async_callbacks');
        $this->workflow_queue_name = (string) config('eligify.workflow.async_queue_name');

        // Default env path in workbench as requested
        $this->envPath = base_path('workbench/.env');
    }

    public function save(): void
    {
        $this->validate();

        // Ensure workbench .env exists
        if (! File::exists($this->envPath)) {
            File::ensureDirectoryExists(dirname($this->envPath));
            File::put($this->envPath, "# Workbench .env for Eligify\n");
        }

        $env = new EnvKeyManager($this->envPath);

        $bool = fn ($v) => $v ? 'true' : 'false';

        $pairs = [
            // UI
            'ELIGIFY_UI_ENABLED' => $bool($this->ui_enabled),
            'ELIGIFY_UI_ROUTE_PREFIX' => $this->ui_route_prefix,
            'ELIGIFY_UI_BRAND_NAME' => $this->ui_brand_name,
            'ELIGIFY_UI_ASSETS_USE_CDN' => $bool($this->ui_assets_use_cdn),
            // Scoring
            'ELIGIFY_SCORING_PASS_THRESHOLD' => (string) $this->scoring_pass_threshold,
            'ELIGIFY_SCORING_METHOD' => $this->scoring_method,
            // Evaluation
            'ELIGIFY_EVALUATION_CACHE_ENABLED' => $bool($this->evaluation_cache_enabled),
            'ELIGIFY_EVALUATION_CACHE_TTL' => (string) $this->evaluation_cache_ttl,
            'ELIGIFY_EVALUATION_FAIL_FAST' => $bool($this->evaluation_fail_fast),
            // Audit
            'ELIGIFY_AUDIT_ENABLED' => $bool($this->audit_enabled),
            'ELIGIFY_AUDIT_RETENTION_DAYS' => (string) $this->audit_retention_days,
            'ELIGIFY_AUDIT_AUTO_CLEANUP' => $bool($this->audit_auto_cleanup),
            // Workflow
            'ELIGIFY_WORKFLOW_ENABLED' => $bool($this->workflow_enabled),
            'ELIGIFY_WORKFLOW_ASYNC' => $bool($this->workflow_async),
            'ELIGIFY_WORKFLOW_QUEUE_NAME' => $this->workflow_queue_name,
        ];

        foreach ($pairs as $k => $v) {
            $env->setKey($k, $v);
        }

        // Update runtime config for immediate effect
        config([
            'eligify.ui.enabled' => $this->ui_enabled,
            'eligify.ui.route_prefix' => $this->ui_route_prefix,
            'eligify.ui.brand.name' => $this->ui_brand_name,
            'eligify.ui.assets.use_cdn' => $this->ui_assets_use_cdn,
            'eligify.scoring.pass_threshold' => $this->scoring_pass_threshold,
            'eligify.scoring.method' => $this->scoring_method,
            'eligify.evaluation.cache_enabled' => $this->evaluation_cache_enabled,
            'eligify.evaluation.cache_ttl' => $this->evaluation_cache_ttl,
            'eligify.evaluation.fail_fast' => $this->evaluation_fail_fast,
            'eligify.audit.enabled' => $this->audit_enabled,
            'eligify.audit.retention_days' => $this->audit_retention_days,
            'eligify.audit.auto_cleanup' => $this->audit_auto_cleanup,
            'eligify.workflow.enabled' => $this->workflow_enabled,
            'eligify.workflow.enable_async_callbacks' => $this->workflow_async,
            'eligify.workflow.async_queue_name' => $this->workflow_queue_name,
        ]);

        $this->status = 'Settings saved.';
        session()->flash('status', 'Settings saved and applied.');
    }

    public function render()
    {
        return view('eligify::livewire.settings-manager');
    }
}
