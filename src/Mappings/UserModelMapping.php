<?php

namespace CleaniqueCoders\Eligify\Mappings;

/**
 * Default User model mapping
 *
 * Configures data extraction for User models with common fields and relationships
 */
class UserModelMapping extends AbstractModelMapping
{
    /**
     * Get the model class this mapping is for
     */
    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    /**
     * Field mappings for User model
     */
    protected array $fieldMappings = [
        'email_verified_at' => 'email_verified_timestamp',
        'created_at' => 'registration_date',
    ];

    /**
     * Computed fields for User model
     */
    protected array $computedFields = [
        'is_verified' => null,
    ];

    /**
     * Initialize computed fields with closures
     */
    public function __construct()
    {
        $this->computedFields = [
            // Verification status
            'is_verified' => fn ($model) => ! is_null($model->email_verified_at ?? null),
        ];
    }
}
