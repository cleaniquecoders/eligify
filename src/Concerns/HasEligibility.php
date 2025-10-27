<?php

namespace CleaniqueCoders\Eligify\Concerns;

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Support\ModelDataExtractor;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for Laravel policies to integrate eligibility checks
 */
trait HasEligibility
{
    protected ?ModelDataExtractor $dataExtractor = null;

    /**
     * Get or create the data extractor instance
     */
    protected function getDataExtractor(): ModelDataExtractor
    {
        if ($this->dataExtractor === null) {
            $this->dataExtractor = new ModelDataExtractor(
                config('eligify.model_extraction', [])
            );
        }

        return $this->dataExtractor;
    }

    /**
     * Set a custom data extractor
     */
    protected function setDataExtractor(ModelDataExtractor $extractor): self
    {
        $this->dataExtractor = $extractor;

        return $this;
    }

    /**
     * Check if a model meets specific criteria eligibility
     */
    protected function hasEligibility(Model $model, string $criteriaName): bool
    {
        try {
            $this->configureExtractorForModel($model);
            $data = $this->extractModelData($model);
            $result = app(Eligify::class)->evaluate($criteriaName, $data);

            return $result['passed'];
        } catch (\Exception $e) {
            // Log error and fail safely
            if (config('eligify.debug', false)) {
                logger()->error("Eligibility check failed for criteria '{$criteriaName}'", [
                    'model' => get_class($model),
                    'model_id' => $model->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }

            return false;
        }
    }

    /**
     * Get detailed eligibility evaluation for a model
     */
    protected function checkEligibility(Model $model, string $criteriaName): array
    {
        try {
            $this->configureExtractorForModel($model);
            $data = $this->extractModelData($model);

            return app(Eligify::class)->evaluate($criteriaName, $data);
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'score' => 0,
                'failed_rules' => [],
                'decision' => 'Error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check eligibility with custom criteria builder
     */
    protected function checkCriteria(Model $model, \Closure $criteriaBuilder): array
    {
        try {
            $data = $this->extractModelData($model);

            // Create a temporary criteria name
            $tempName = 'temp_'.uniqid();
            $builder = Eligify::criteria($tempName);

            // Apply the criteria builder closure
            $criteriaBuilder($builder);

            // Save the pending rules and get the criteria model
            $builder->save();
            $criteriaModel = $builder->getCriteria();

            // Evaluate using the main Eligify class
            $result = app(Eligify::class)->evaluate($criteriaModel, $data);

            // Clean up temporary criteria
            Criteria::where('slug', str($tempName)->slug())->delete();

            return $result;
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'score' => 0,
                'failed_rules' => [],
                'decision' => 'Error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Evaluate model against existing criteria instance
     */
    protected function evaluateModel(Model $model, Criteria $criteria): array
    {
        try {
            $data = $this->extractModelData($model);

            return app(Eligify::class)->evaluate($criteria, $data);
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'score' => 0,
                'failed_rules' => [],
                'decision' => 'Error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check eligibility with score threshold
     */
    protected function hasMinimumScore(Model $model, string $criteriaName, int $minimumScore): bool
    {
        $result = $this->checkEligibility($model, $criteriaName);

        return $result['score'] >= $minimumScore;
    }

    /**
     * Batch eligibility check for multiple models
     */
    protected function checkBatchEligibility(array $models, string $criteriaName): array
    {
        $results = [];

        foreach ($models as $model) {
            if (! $model instanceof Model) {
                continue;
            }

            $results[$model->getKey()] = $this->checkEligibility($model, $criteriaName);
        }

        return $results;
    }

    /**
     * Extract data from model for eligibility evaluation
     * Override this method in your policy to customize data extraction
     */
    protected function extractModelData(Model $model): array
    {
        // Use the enhanced data extractor
        $extractor = $this->getDataExtractor();
        $data = $extractor->extract($model);

        // Allow for additional customization in subclasses
        return $this->customizeExtractedData($model, $data);
    }

    /**
     * Customize extracted data - override this method for additional customization
     */
    protected function customizeExtractedData(Model $model, array $data): array
    {
        // Default implementation - return data as-is
        // Override this method in your policy for custom field mappings
        return $data;
    }

    /**
     * Configure the data extractor for specific model types
     */
    protected function configureExtractorForModel(Model $model): void
    {
        $modelClass = get_class($model);

        // Auto-configure based on common model patterns
        $extractor = $this->getDataExtractor();

        if (str_contains($modelClass, 'User')) {
            $extractor->setComputedFields([
                'is_verified' => function ($model) {
                    return isset($model->email_verified_at) && ! is_null($model->email_verified_at);
                },
                'account_status' => function ($model) {
                    if (method_exists($model, 'isActive')) {
                        return $model->isActive() ? 'active' : 'inactive';
                    }

                    return isset($model->status) ? $model->status : 'unknown';
                },
            ]);
        }

        if (str_contains($modelClass, 'Order')) {
            $extractor->setComputedFields([
                'order_size_category' => function ($model) {
                    if (! isset($model->total)) {
                        return 'unknown';
                    }

                    return match (true) {
                        $model->total >= 1000 => 'large',
                        $model->total >= 500 => 'medium',
                        $model->total >= 100 => 'small',
                        default => 'micro'
                    };
                },
                'days_since_order' => function ($model) {
                    return $model->created_at ? now()->diffInDays($model->created_at) : null;
                },
            ]);
        }
    }

    /**
     * Get eligibility status with human-readable message
     */
    protected function getEligibilityStatus(Model $model, string $criteriaName): array
    {
        $result = $this->checkEligibility($model, $criteriaName);

        $status = [
            'eligible' => $result['passed'],
            'score' => $result['score'],
            'decision' => $result['decision'],
            'message' => $this->generateStatusMessage($result),
            'failed_rules' => $result['failed_rules'],
        ];

        return $status;
    }

    /**
     * Generate human-readable status message
     */
    protected function generateStatusMessage(array $result): string
    {
        if ($result['passed']) {
            return "Eligible (Score: {$result['score']})";
        }

        $failedCount = count($result['failed_rules']);
        if ($failedCount === 0) {
            return "Not eligible (Score: {$result['score']})";
        }

        return "Not eligible - {$failedCount} rule(s) failed (Score: {$result['score']})";
    }

    /**
     * Check if model passes any of the given criteria
     */
    protected function passesAnyCriteria(Model $model, array $criteriaNames): bool
    {
        foreach ($criteriaNames as $criteriaName) {
            if ($this->hasEligibility($model, $criteriaName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if model passes all of the given criteria
     */
    protected function passesAllCriteria(Model $model, array $criteriaNames): bool
    {
        foreach ($criteriaNames as $criteriaName) {
            if (! $this->hasEligibility($model, $criteriaName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get detailed results for multiple criteria
     */
    protected function checkMultipleCriteria(Model $model, array $criteriaNames): array
    {
        $results = [];

        foreach ($criteriaNames as $criteriaName) {
            $results[$criteriaName] = $this->checkEligibility($model, $criteriaName);
        }

        return $results;
    }
}
