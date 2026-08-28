<?php

namespace App\Domain\Doctors\Data;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Patients\Models\DoctorPatientAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

readonly class DoctorDirectoryPage
{
    /**
     * @param  LengthAwarePaginator<int, Doctor>  $paginator
     * @param  Collection<int|string, DoctorPatientAccess>  $accessByDoctor
     * @param  list<string>  $specializations
     */
    public function __construct(
        public LengthAwarePaginator $paginator,
        public Collection $accessByDoctor,
        public array $specializations,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        return $this->paginator
            ->getCollection()
            ->map(function (Doctor $doctor): array {
                /** @var DoctorPatientAccess|null $access */
                $access = $this->accessByDoctor->get($doctor->id);

                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user?->name,
                    'specialization' => $doctor->specialization,
                    'bio' => $doctor->bio,
                    'rating' => (float) $doctor->rating,
                    'years_of_experience' => $doctor->years_of_experience,
                    'card_access_status' => $access?->status->value,
                    'available_slots' => $doctor->availabilitySlots
                        ->map(static fn (DoctorAvailabilitySlot $slot): array => [
                            'id' => $slot->id,
                            'starts_at' => $slot->starts_at?->toIso8601String(),
                            'ends_at' => $slot->ends_at?->toIso8601String(),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int
     * }
     */
    public function meta(): array
    {
        return [
            'current_page' => $this->paginator->currentPage(),
            'last_page' => $this->paginator->lastPage(),
            'per_page' => $this->paginator->perPage(),
            'total' => $this->paginator->total(),
        ];
    }
}
