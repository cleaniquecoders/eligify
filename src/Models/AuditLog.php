<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Concerns\InteractsWithMeta;
use CleaniqueCoders\Traitify\Concerns\InteractsWithSlug;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;
    use InteractsWithEnum;
    use InteractsWithMeta;
    use InteractsWithSlug;
    use InteractsWithUuid;

    protected $table = 'eligify_audit_logs';

    protected $fillable = [
        'uuid',
        'event',
        'auditable_type',
        'auditable_id',
        'slug',
        'old_values',
        'new_values',
        'context',
        'user_type',
        'user_id',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'context' => 'array',
        'meta' => 'array',
    ];

    /**
     * Configure the slug source field
     */
    public function getSlugSourceAttribute(): string
    {
        return $this->getAttribute('event').'_'.$this->getAttribute('auditable_type').'_'.$this->getAttribute('auditable_id');
    }

    /**
     * The auditable entity (polymorphic relationship)
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by event type
     */
    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, string $userType, int $userId)
    {
        return $query->where('user_type', $userType)->where('user_id', $userId);
    }

    /**
     * Scope to filter by IP address
     */
    public function scopeByIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Get changes made in this audit log
     */
    public function getChanges(): array
    {
        if (empty($this->old_values) || empty($this->new_values)) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Get a human-readable description of the audit log
     */
    public function getDescription(): string
    {
        $description = ucfirst(str_replace('_', ' ', $this->getAttribute('event')));
        $description .= " on {$this->getAttribute('auditable_type')}";

        if ($this->getAttribute('user_type') && $this->getAttribute('user_id')) {
            $description .= " by {$this->getAttribute('user_type')}#{$this->getAttribute('user_id')}";
        }

        return $description;
    }

    /**
     * Create a new audit log entry
     */
    public static function logEvent(
        string $event,
        Model $auditable,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $context = [],
        array $userInfo = []
    ): self {
        return static::create([
            'event' => $event,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->getAttribute('id'),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'context' => $context,
            'user_type' => $userInfo['user_type'] ?? null,
            'user_id' => $userInfo['user_id'] ?? null,
            'ip_address' => $userInfo['ip_address'] ?? (app()->bound('request') ? app('request')->ip() : null),
            'user_agent' => $userInfo['user_agent'] ?? (app()->bound('request') ? app('request')->userAgent() : null),
        ]);
    }
}
