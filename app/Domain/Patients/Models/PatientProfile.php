<?php

namespace App\Domain\Patients\Models;

use App\Models\User;
use Database\Factories\Domain\Patients\Models\PatientProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $medical_history
 * @property string|null $telegram_chat_id
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'date_of_birth',
    'medical_history',
    'telegram_chat_id',
])]
class PatientProfile extends Model
{
    /** @use HasFactory<PatientProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'medical_history' => 'encrypted',
        ];
    }

    protected static function newFactory(): PatientProfileFactory
    {
        return PatientProfileFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PatientCardNote, $this>
     */
    public function cardNotes(): HasMany
    {
        return $this->hasMany(PatientCardNote::class);
    }

    /**
     * @return HasMany<DoctorPatientAccess, $this>
     */
    public function doctorAccesses(): HasMany
    {
        return $this->hasMany(DoctorPatientAccess::class);
    }
}
