<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Concerns\InteractsWithMeta;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriteriaVersion extends Model
{
    use HasFactory;
    use InteractsWithEnum;
    use InteractsWithMeta;
    use InteractsWithUuid;

    protected $table = 'eligify_criteria_versions';

    protected $fillable = [
        'uuid',
        'criteria_id',
        'version',
        'description',
        'rules_snapshot',
        'meta',
    ];

    protected $casts = [
        'version' => 'integer',
        'rules_snapshot' => 'array',
        'meta' => 'array',
    ];

    /**
     * The criteria this version belongs to
     */
    public function criteria(): BelongsTo
    {
        return $this->belongsTo(Criteria::class);
    }

    /**
     * Get all rules for this version
     */
    public function getRulesSnapshot(): array
    {
        return $this->getAttribute('rules_snapshot') ?? [];
    }

    /**
     * Scope to find by version number
     */
    public function scopeByVersion($query, int $version)
    {
        return $query->where('version', $version);
    }

    /**
     * Scope to get latest version
     */
    public function scopeLatestVersion($query)
    {
        return $query->orderByDesc('version')->first();
    }
}
