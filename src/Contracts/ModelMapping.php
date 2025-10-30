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

    /**
     * Get the model class this mapping is for
     */
    public function getModelClass(): string;

    /**
     * Get human-readable name for this mapping
     */
    public function getName(): string;

    /**
     * Get description of what this mapping does
     */
    public function getDescription(): string;

    /**
     * Get all available fields with their metadata
     *
     * @return array Format: ['field_name' => ['type' => 'string', 'description' => '...', 'category' => 'attribute|relationship|computed']]
     */
    public function getAvailableFields(): array;

    /**
     * Get field type for a specific field
     */
    public function getFieldType(string $field): ?string;

    /**
     * Get field description for a specific field
     */
    public function getFieldDescription(string $field): ?string;
}
