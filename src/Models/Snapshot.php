<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Models;

use CleaniqueCoders\Eligify\Data\Snapshot as SnapshotData;
use CleaniqueCoders\Traitify\Concerns\InteractsWithMeta;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Snapshot extends Model
{
    use HasFactory;
    use InteractsWithMeta;
    use InteractsWithUuid;

    protected $table = 'eligify_snapshots';

    protected $fillable = [
        'uuid',
        'snapshotable_type',
        'snapshotable_id',
        'data',
        'checksum',
        'meta',
        'captured_at',
    ];

    protected $casts = [
        'data' => 'array',
        'meta' => 'array',
        'captured_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $snapshot) {
            if (empty($snapshot->checksum)) {
                $snapshot->checksum = $snapshot->calculateChecksum();
            }

            if (empty($snapshot->captured_at)) {
                $snapshot->captured_at = now();
            }
        });
    }

    /**
     * The snapshotable entity (polymorphic relationship)
     */
    public function snapshotable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Evaluations that used this snapshot
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Calculate checksum for the data
     */
    public function calculateChecksum(): string
    {
        $data = is_array($this->data) ? $this->data : json_decode($this->data, true);

        return hash('sha256', json_encode($data, JSON_SORT_KEYS));
    }

    /**
     * Verify data integrity
     */
    public function verifyIntegrity(): bool
    {
        return $this->checksum === $this->calculateChecksum();
    }

    /**
     * Convert to SnapshotData object for use in evaluations
     */
    public function toSnapshotData(): SnapshotData
    {
        return new SnapshotData(
            $this->data,
            [
                'snapshot_id' => $this->id,
                'snapshot_uuid' => $this->uuid,
                'captured_at' => $this->captured_at->toIso8601String(),
                'checksum' => $this->checksum,
            ]
        );
    }

    /**
     * Create a snapshot from a SnapshotData object
     */
    public static function fromSnapshotData(
        SnapshotData $snapshotData,
        string $snapshotableType,
        int $snapshotableId,
        array $meta = []
    ): self {
        return static::create([
            'uuid' => (string) str()->uuid(),
            'snapshotable_type' => $snapshotableType,
            'snapshotable_id' => $snapshotableId,
            'data' => $snapshotData->toArray(),
            'meta' => array_merge($snapshotData->metadata() ?? [], $meta),
            'captured_at' => now(),
        ]);
    }

    /**
     * Find or create a snapshot with the same data
     * Uses checksum for deduplication
     */
    public static function findOrCreateFromData(
        array $data,
        string $snapshotableType,
        int $snapshotableId,
        array $meta = []
    ): self {
        $checksum = hash('sha256', json_encode($data, JSON_SORT_KEYS));

        return static::firstOrCreate(
            [
                'checksum' => $checksum,
                'snapshotable_type' => $snapshotableType,
                'snapshotable_id' => $snapshotableId,
            ],
            [
                'uuid' => (string) str()->uuid(),
                'data' => $data,
                'meta' => $meta,
                'captured_at' => now(),
            ]
        );
    }

    /**
     * Scope for a specific snapshotable entity
     */
    public function scopeForSnapshotable($query, string $type, int $id)
    {
        return $query->where('snapshotable_type', $type)
            ->where('snapshotable_id', $id);
    }

    /**
     * Scope for snapshots captured within a date range
     */
    public function scopeCapturedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('captured_at', [$startDate, $endDate]);
    }

    /**
     * Scope for snapshots with a specific checksum
     */
    public function scopeByChecksum($query, string $checksum)
    {
        return $query->where('checksum', $checksum);
    }

    /**
     * Get a specific field from the snapshot data
     */
    public function getDataField(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Check if snapshot has a specific field
     */
    public function hasDataField(string $key): bool
    {
        return data_get($this->data, $key) !== null;
    }

    /**
     * Get summary of the snapshot
     */
    public function getSummary(): array
    {
        return [
            'uuid' => $this->uuid,
            'snapshotable' => "{$this->snapshotable_type}:{$this->snapshotable_id}",
            'field_count' => is_array($this->data) ? count($this->data) : 0,
            'checksum' => $this->checksum,
            'captured_at' => $this->captured_at,
            'evaluations_count' => $this->evaluations()->count(),
        ];
    }
}
