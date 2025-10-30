<?php

use CleaniqueCoders\Eligify\Data\Mappings\UserModelMapping;

it('can get prefix from user mapping', function () {
    $mapping = new UserModelMapping;

    expect($mapping->getPrefix())->toBe('user');
});

it('can get prefix from model name automatically', function () {
    // Create a mock mapping without explicit prefix
    $mockMapping = new class extends \CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping
    {
        public function getModelClass(): string
        {
            return 'App\Models\Applicant';
        }

        public function getName(): string
        {
            return 'Applicant';
        }

        public function getDescription(): string
        {
            return 'Test mapping';
        }
    };

    expect($mockMapping->getPrefix())->toBe('applicant');
});

it('handles multi-word model names in prefix generation', function () {
    $mockMapping = new class extends \CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping
    {
        public function getModelClass(): string
        {
            return 'App\Models\LoanApplication';
        }

        public function getName(): string
        {
            return 'Loan Application';
        }

        public function getDescription(): string
        {
            return 'Test mapping';
        }
    };

    expect($mockMapping->getPrefix())->toBe('loan.application');
});
