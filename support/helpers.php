<?php

use CleaniqueCoders\Eligify\Data\Extractor;
use CleaniqueCoders\Eligify\Data\Snapshot;
use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('eligify_snapshot')) {
    function eligify_snapshot(string $model, Model $data): Snapshot
    {
        if (! class_exists($model)) {
            throw new Exception('Error Processing Request', 1);
        }

        return (new Extractor)
            ->forModel($model)
            ->extract($data);
    }
}

if (! function_exists('eligify_evaluate')) {
    function eligify_evaluate(string|Criteria $criteria, Snapshot $snapshot)
    {
        if (is_string($criteria)) {
            $criteria = eligify_find_criteria($criteria);
        }

        return (new Eligify)->evaluate(
            criteria: $criteria,
            data: $snapshot,
            saveEvaluation: false,
            useCache: false
        );
    }
}

if (! function_exists('eligify_find_criteria')) {
    function eligify_find_criteria(string $keyword, string $field = 'name'): Criteria
    {
        return Criteria::where($field, $keyword)->firstOrNew();
    }
}
