<?php

namespace App\Domain\Compliance\Models;

use App\Enums\HipaaAuditAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable HIPAA audit trail entry. Never store raw PHI in metadata.
 *
 * @property int $id
 * @property int|null $actor_user_id
 * @property HipaaAuditAction $action
 * @property string|null $resource_type
 * @property int|null $resource_id
 * @property string $outcome
 * @property bool $phi_involved
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $metadata
 * @property-read User|null $actor
 */
#[Fillable([
    'actor_user_id',
    'action',
    'resource_type',
    'resource_id',
    'outcome',
    'phi_involved',
    'ip_address',
    'user_agent',
    'metadata',
    'created_at',
])]
class HipaaAuditLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'action' => HipaaAuditAction::class,
            'phi_involved' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \RuntimeException('HIPAA audit logs are immutable and cannot be updated.');
        });

        static::deleting(function (): never {
            throw new \RuntimeException('HIPAA audit logs are immutable and cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
