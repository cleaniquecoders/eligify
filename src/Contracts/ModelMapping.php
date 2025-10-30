<?php

namespace CleaniqueCoders\Eligify\Contracts;

use CleaniqueCoders\Eligify\Data\Extractor;

/**
 * Interface for custom model mappings
 *
 * Implement this interface to define how a model's data should be extracted
 * and mapped for eligibility evaluations.
 */
interface ModelMapping
{
    /**
     * Configure the extractor with custom field mappings, relationships, and computed fields
     *
     * @param  Extractor  $extractor  The extractor instance to configure
     * @return Extractor The configured extractor
     */
    public function configure(Extractor $extractor): Extractor;
}
