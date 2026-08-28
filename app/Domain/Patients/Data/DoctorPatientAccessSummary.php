<?php

namespace App\Domain\Patients\Data;

use App\Domain\Patients\Models\DoctorPatientAccess;
use App\Enums\DoctorPatientAccessSource;
use App\Enums\DoctorPatientAccessStatus;

readonly class DoctorPatientAccessSummary
{
    public function __construct(
        public int $id,
        public int $doctorId,
        public ?string $doctorName,
        public string $specialization,
        public DoctorPatientAccessStatus $status,
        public DoctorPatientAccessSource $source,
        public ?int $grantedViaAppointmentId,
        public string $updatedAt,
    ) {}

    public static function fromModel(DoctorPatientAccess $access): self
    {
        $access->loadMissing('doctor.user');

        return new self(
            id: $access->id,
            doctorId: $access->doctor_id,
            doctorName: $access->doctor->user?->name,
            specialization: $access->doctor->specialization,
            status: $access->status,
            source: $access->source,
            grantedViaAppointmentId: $access->granted_via_appointment_id,
            updatedAt: $access->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        );
    }

    /**
     * @return array{
     *     id: int,
     *     doctor_id: int,
     *     doctor_name: string|null,
     *     specialization: string,
     *     status: string,
     *     source: string,
     *     granted_via_appointment_id: int|null,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctorId,
            'doctor_name' => $this->doctorName,
            'specialization' => $this->specialization,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'granted_via_appointment_id' => $this->grantedViaAppointmentId,
            'updated_at' => $this->updatedAt,
        ];
    }
}
