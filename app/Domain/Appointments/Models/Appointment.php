<?php

namespace App\Domain\Appointments\Models;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\AppointmentStatus;
use App\Enums\UrgencyLevel;
use Database\Factories\Domain\Appointments\Models\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $patient_profile_id
 * @property int $doctor_id
 * @property int|null $doctor_availability_slot_id
 * @property string $symptoms
 * @property string|null $chief_complaint
 * @property string|null $clinical_summary
 * @property UrgencyLevel $urgency_level
 * @property AppointmentStatus $status
 * @property string|null $google_meet_url
 * @property-read PatientProfile $patientProfile
 * @property-read Doctor $doctor
 * @property-read DoctorAvailabilitySlot|null $availabilitySlot
 */
#[Fillable([
    'patient_profile_id',
    'doctor_id',
    'doctor_availability_slot_id',
    'symptoms',
    'chief_complaint',
    'clinical_summary',
    'urgency_level',
    'status',
    'google_meet_url',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'symptoms' => 'encrypted',
            'chief_complaint' => 'encrypted',
            'clinical_summary' => 'encrypted',
            'urgency_level' => UrgencyLevel::class,
            'status' => AppointmentStatus::class,
        ];
    }

    protected static function newFactory(): AppointmentFactory
    {
        return AppointmentFactory::new();
    }

    /**
     * @return BelongsTo<PatientProfile, $this>
     */
    public function patientProfile(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class);
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return BelongsTo<DoctorAvailabilitySlot, $this>
     */
    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(DoctorAvailabilitySlot::class, 'doctor_availability_slot_id');
    }
}
