<?php

namespace App\Domain\Doctors\Data;

use App\Domain\Doctors\Models\DoctorAvailabilitySlot;

readonly class DoctorAvailabilitySlotSummary
{
    public function __construct(
        public int $id,
        public string $startsAt,
        public string $endsAt,
    ) {}

    public static function fromModel(DoctorAvailabilitySlot $slot): self
    {
        return new self(
            id: $slot->id,
            startsAt: $slot->starts_at->toIso8601String(),
            endsAt: $slot->ends_at->toIso8601String(),
        );
    }

    /**
     * @return array{id: int, starts_at: string, ends_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
        ];
    }
}
