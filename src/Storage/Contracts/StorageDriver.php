<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Storage\Contracts;

use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Support\Collection;

interface StorageDriver
{
    /**
     * Find criteria by slug, return hydrated Criteria model or null
     */
    public function findCriteriaBySlug(string $slug): ?Criteria;

    /**
     * Find criteria by name or slug
     */
    public function findCriteria(string $identifier): ?Criteria;

    /**
     * Get all active criteria
     *
     * @return Collection<int, Criteria>
     */
    public function getAllActiveCriteria(): Collection;

    /**
     * Store criteria (upsert by slug)
     */
    public function storeCriteria(array $data): Criteria;

    /**
     * Store a rule for a criteria
     */
    public function storeRule(Criteria $criteria, array $ruleData): void;

    /**
     * Delete criteria and its rules
     */
    public function deleteCriteria(string $identifier): bool;

    /**
     * Store a rule group with its rules
     */
    public function storeGroup(Criteria $criteria, array $groupData, array $rules = []): mixed;

    /**
     * Export criteria with all rules and groups as a portable array
     */
    public function exportCriteria(string $slug): ?array;

    /**
     * Import criteria from a portable array
     */
    public function importCriteria(array $data): Criteria;
}
