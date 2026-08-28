<?php

namespace App\Domain\Patients\Models;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Doctors\Models\Doctor;
use App\Enums\DoctorPatientAccessSource;
use App\Enums\DoctorPatientAccessStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $patient_profile_id
 * @property int $doctor_id
 * @property DoctorPatientAccessStatus $status
 * @property DoctorPatientAccessSource $source
 * @property int|null $granted_via_appointment_id
 * @property-read PatientProfile $patientProfile
 * @property-read Doctor $doctor
 * @property-read Appointment|null $grantedViaAppointment
 */
#[Fillable([
    'patient_profile_id',
    'doctor_id',
    'status',
    'source',
    'granted_via_appointment_id',
])]
class DoctorPatientAccess extends Model
{
    protected function casts(): array
    {
        return [
            'status' => DoctorPatientAccessStatus::class,
            'source' => DoctorPatientAccessSource::class,
        ];
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
     * @return BelongsTo<Appointment, $this>
     */
    public function grantedViaAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'granted_via_appointment_id');
    }

    public function isApproved(): bool
    {
        return $this->status === DoctorPatientAccessStatus::Approved;
    }
}
