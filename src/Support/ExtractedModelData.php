<?php

namespace CleaniqueCoders\Eligify\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use JsonSerializable;

/**
 * Container for extracted model data with enhanced functionality
 *
 * This class wraps the flat array of extracted data and provides convenient
 * methods for accessing, transforming, and working with the data in eligibility
 * evaluation contexts.
 *
 * @example
 * ```php
 * $extracted = ModelDataExtractor::forModel(User::class)->extract($user);
 *
 * // Direct property access
 * $income = $extracted->income;
 *
 * // Safe access with defaults
 * $score = $extracted->get('credit_score', 650);
 *
 * // Check if field exists
 * if ($extracted->has('employment_verified')) {
 *     // ...
 * }
 *
 * // Convert to array for rule engine
 * $data = $extracted->toArray();
 *
 * // Get only specific fields
 * $subset = $extracted->only(['income', 'credit_score', 'age']);
 *
 * // Exclude sensitive fields
 * $safe = $extracted->except(['ssn', 'account_number']);
 *
 * // Transform data
 * $normalized = $extracted->transform(fn($value, $key) =>
 *     is_string($value) ? strtolower($value) : $value
 * );
 *
 * // Export as JSON
 * $json = $extracted->toJson();
 * ```
 */
class ExtractedModelData implements \ArrayAccess, \Countable, Arrayable, Jsonable, JsonSerializable
{
    /**
     * The extracted data
     */
    protected array $data;

    /**
     * Metadata about the extraction
     */
    protected array $metadata;

    /**
     * Create a new extracted data instance
     */
    public function __construct(array $data, array $metadata = [])
    {
        $this->data = $data;
        $this->metadata = array_merge([
            'extracted_at' => now()->toIso8601String(),
            'field_count' => count($data),
        ], $metadata);
    }

    /**
     * Get a data value using dot notation
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Check if a key exists in the data
     */
    public function has(string|array $key): bool
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Get all the data
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get only the specified keys
     */
    public function only(array $keys): self
    {
        return new self(Arr::only($this->data, $keys), $this->metadata);
    }

    /**
     * Get all data except the specified keys
     */
    public function except(array $keys): self
    {
        return new self(Arr::except($this->data, $keys), $this->metadata);
    }

    /**
     * Filter data using a callback
     */
    public function filter(callable $callback): self
    {
        $filtered = array_filter($this->data, $callback, ARRAY_FILTER_USE_BOTH);

        return new self($filtered, $this->metadata);
    }

    /**
     * Transform data using a callback
     */
    public function transform(callable $callback): self
    {
        $transformed = [];

        foreach ($this->data as $key => $value) {
            $transformed[$key] = $callback($value, $key);
        }

        return new self($transformed, $this->metadata);
    }

    /**
     * Merge additional data
     */
    public function merge(array $data): self
    {
        return new self(array_merge($this->data, $data), $this->metadata);
    }

    /**
     * Get metadata about the extraction
     */
    public function metadata(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? null;
    }

    /**
     * Get fields matching a pattern
     */
    public function whereKeyMatches(string $pattern): self
    {
        $filtered = array_filter(
            $this->data,
            fn ($key) => preg_match($pattern, $key),
            ARRAY_FILTER_USE_KEY
        );

        return new self($filtered, $this->metadata);
    }

    /**
     * Get numeric fields only
     */
    public function numericFields(): self
    {
        return $this->filter(fn ($value) => is_numeric($value));
    }

    /**
     * Get string fields only
     */
    public function stringFields(): self
    {
        return $this->filter(fn ($value) => is_string($value));
    }

    /**
     * Get boolean fields only
     */
    public function booleanFields(): self
    {
        return $this->filter(fn ($value) => is_bool($value));
    }

    /**
     * Convert to array (for rule engine compatibility)
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Convert to JSON
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Specify data which should be serialized to JSON
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get the count of data fields
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Dynamic property access
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Check if property exists
     */
    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * Set a value (immutable pattern - returns new instance)
     */
    public function __set(string $key, mixed $value): void
    {
        throw new \BadMethodCallException(
            'ExtractedModelData is immutable. Use merge() or transform() to create a new instance with changes.'
        );
    }

    /**
     * ArrayAccess: Check if offset exists
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * ArrayAccess: Get offset
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * ArrayAccess: Set offset (immutable)
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException(
            'ExtractedModelData is immutable. Use merge() or transform() to create a new instance with changes.'
        );
    }

    /**
     * ArrayAccess: Unset offset (immutable)
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException(
            'ExtractedModelData is immutable. Use except() to create a new instance without specific keys.'
        );
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->toJson(JSON_PRETTY_PRINT);
    }

    /**
     * Debug info
     */
    public function __debugInfo(): array
    {
        return [
            'data' => $this->data,
            'metadata' => $this->metadata,
            'field_count' => $this->count(),
        ];
    }
}
