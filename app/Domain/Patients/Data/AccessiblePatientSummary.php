<?php

namespace App\Domain\Patients\Data;

use App\Domain\Patients\Models\DoctorPatientAccess;

readonly class AccessiblePatientSummary
{
    public function __construct(
        public int $patientProfileId,
        public ?string $patientName,
        public int $accessId,
        public string $accessStatus,
        public string $accessSource,
        public string $updatedAt,
    ) {}

    public static function fromAccess(DoctorPatientAccess $access): self
    {
        $access->loadMissing('patientProfile.user');

        return new self(
            patientProfileId: $access->patient_profile_id,
            patientName: $access->patientProfile->user?->name,
            accessId: $access->id,
            accessStatus: $access->status->value,
            accessSource: $access->source->value,
            updatedAt: $access->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        );
    }

    /**
     * @return array{
     *     patient_profile_id: int,
     *     patient_name: string|null,
     *     access_id: int,
     *     access_status: string,
     *     access_source: string,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'patient_profile_id' => $this->patientProfileId,
            'patient_name' => $this->patientName,
            'access_id' => $this->accessId,
            'access_status' => $this->accessStatus,
            'access_source' => $this->accessSource,
            'updated_at' => $this->updatedAt,
        ];
    }
}
