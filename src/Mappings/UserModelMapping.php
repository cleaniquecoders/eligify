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
     * Prefix for this mapping
     */
    protected ?string $prefix = 'user';

    /**
     * Initialize computed fields with closures
     */
    public function __construct()
    {
        $this->computedFields = [
            // Verification status
            'is_verified' => fn ($model) => ! is_null($model->email_verified_at ?? null),
        ];

        // Field descriptions for UI display
        $this->fieldDescriptions = [
            'name' => 'User full name',
            'email' => 'User email address',
            'email_verified_timestamp' => 'When the email was verified (nullable)',
            'registration_date' => 'Account creation date',
            'is_verified' => 'Whether the user has verified their email',
        ];

        // Field types for validation and UI hints
        $this->fieldTypes = [
            'name' => 'string',
            'email' => 'string',
            'email_verified_timestamp' => 'date',
            'registration_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Get human-readable name for this mapping
     */
    public function getName(): string
    {
        return 'User';
    }

    /**
     * Get description of what this mapping does
     */
    public function getDescription(): string
    {
        return 'Standard mapping for User models including profile data, verification status, and registration information';
    }
}
