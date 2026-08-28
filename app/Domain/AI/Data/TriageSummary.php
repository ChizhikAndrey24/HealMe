<?php

namespace App\Domain\AI\Data;

use App\Enums\UrgencyLevel;

readonly class TriageSummary
{
    /**
     * @param  list<string>  $recommendedSpecialties
     */
    public function __construct(
        public string $chiefComplaint,
        public string $duration,
        public array $recommendedSpecialties,
        public UrgencyLevel $urgencyLevel,
        public string $clinicalSummary,
    ) {}

    /**
     * @return array{
     *     chief_complaint: string,
     *     duration: string,
     *     recommended_specialties: list<string>,
     *     urgency_level: string,
     *     clinical_summary: string
     * }
     */
    public function toArray(): array
    {
        return [
            'chief_complaint' => $this->chiefComplaint,
            'duration' => $this->duration,
            'recommended_specialties' => $this->recommendedSpecialties,
            'urgency_level' => $this->urgencyLevel->value,
            'clinical_summary' => $this->clinicalSummary,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return TriageSummaryPayload::fromArray($payload)->toSummary();
    }
}
