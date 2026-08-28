<?php

namespace App\Domain\Patients\Models;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Doctors\Models\Doctor;
use App\Enums\PatientCardNoteAuthor;
use App\Enums\PatientCardNoteSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only illness history note on a patient's card.
 *
 * @property int $id
 * @property int $patient_profile_id
 * @property PatientCardNoteAuthor $author_type
 * @property int|null $author_doctor_id
 * @property PatientCardNoteSource $source
 * @property int|null $appointment_id
 * @property string $body
 * @property-read PatientProfile $patientProfile
 * @property-read Doctor|null $authorDoctor
 * @property-read Appointment|null $appointment
 */
#[Fillable([
    'patient_profile_id',
    'author_type',
    'author_doctor_id',
    'source',
    'appointment_id',
    'body',
])]
class PatientCardNote extends Model
{
    protected function casts(): array
    {
        return [
            'author_type' => PatientCardNoteAuthor::class,
            'source' => PatientCardNoteSource::class,
            'body' => 'encrypted',
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
    public function authorDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'author_doctor_id');
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
