<?php

namespace CleaniqueCoders\Eligify\Contracts;

use CleaniqueCoders\Eligify\Support\ModelDataExtractor;

/**
 * Contract for model mapping classes that configure data extraction
 */
interface ModelMapping
{
    /**
     * Configure the extractor with field mappings, computed fields, etc.
     *
     * @param  ModelDataExtractor  $extractor  The extractor instance to configure
     * @return ModelDataExtractor The configured extractor
     */
    public function configure(ModelDataExtractor $extractor): ModelDataExtractor;

    /**
     * Get the fully qualified class name of the model this mapping is for
     */
    public function getModelClass(): string;
}
