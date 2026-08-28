<?php

namespace App\Domain\Doctors\Models;

use Database\Factories\Domain\Doctors\Models\DoctorAvailabilitySlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $doctor_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property bool $is_booked
 * @property-read Doctor $doctor
 */
#[Fillable([
    'doctor_id',
    'starts_at',
    'ends_at',
    'is_booked',
])]
class DoctorAvailabilitySlot extends Model
{
    /** @use HasFactory<DoctorAvailabilitySlotFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_booked' => 'boolean',
        ];
    }

    protected static function newFactory(): DoctorAvailabilitySlotFactory
    {
        return DoctorAvailabilitySlotFactory::new();
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
