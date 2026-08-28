<?php

namespace App\Domain\Doctors\Models;

use App\Models\User;
use Database\Factories\Domain\Doctors\Models\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany as HasManyRelation;

/**
 * @property int $id
 * @property int $user_id
 * @property string $specialization
 * @property string|null $bio
 * @property int $years_of_experience
 * @property string $rating
 * @property bool $is_active
 * @property bool $is_verified
 * @property string $meeting_provider
 * @property-read User|null $user
 * @property-read Collection<int, DoctorAvailabilitySlot> $availabilitySlots
 */
#[Fillable([
    'user_id',
    'specialization',
    'bio',
    'years_of_experience',
    'rating',
    'is_active',
    'is_verified',
    'meeting_provider',
])]
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    protected static function newFactory(): DoctorFactory
    {
        return DoctorFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasManyRelation<DoctorAvailabilitySlot, $this>
     */
    public function availabilitySlots(): HasManyRelation
    {
        return $this->hasMany(DoctorAvailabilitySlot::class);
    }
}
