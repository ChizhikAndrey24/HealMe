<?php

namespace App\Domain\Doctors\Data;

readonly class DoctorMatchResult
{
    /**
     * @param  list<DoctorAvailabilitySlotSummary>  $availableSlots
     */
    public function __construct(
        public int $doctorId,
        public ?string $doctorName,
        public string $specialization,
        public float $rating,
        public int $yearsOfExperience,
        public float $matchScore,
        public ?string $nextAvailableSlot,
        public array $availableSlots,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string|null,
     *     specialization: string,
     *     rating: float,
     *     years_of_experience: int,
     *     match_score: float,
     *     next_available_slot: string|null,
     *     available_slots: array<int, array{id: int, starts_at: string, ends_at: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->doctorId,
            'name' => $this->doctorName,
            'specialization' => $this->specialization,
            'rating' => $this->rating,
            'years_of_experience' => $this->yearsOfExperience,
            'match_score' => $this->matchScore,
            'next_available_slot' => $this->nextAvailableSlot,
            'available_slots' => array_map(
                static fn (DoctorAvailabilitySlotSummary $slot): array => $slot->toArray(),
                $this->availableSlots,
            ),
        ];
    }
}
