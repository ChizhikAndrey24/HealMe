<?php

namespace App\Domain\AI\Data;

use App\Domain\AI\Exceptions\TriageExtractionException;
use App\Enums\UrgencyLevel;

readonly class TriageSummaryPayload
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
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $chiefComplaint = trim((string) ($payload['chief_complaint'] ?? ''));
        $duration = trim((string) ($payload['duration'] ?? ''));
        $clinicalSummary = trim((string) ($payload['clinical_summary'] ?? ''));
        $urgency = UrgencyLevel::tryFrom((string) ($payload['urgency_level'] ?? ''));

        if ($chiefComplaint === '' || $duration === '' || $clinicalSummary === '' || $urgency === null) {
            throw new TriageExtractionException('Gemini returned an incomplete triage payload.');
        }

        $specialties = $payload['recommended_specialties'] ?? null;

        if (! is_array($specialties)) {
            throw new TriageExtractionException('Gemini returned invalid recommended specialties.');
        }

        $recommendedSpecialties = array_values(array_filter(array_map(
            static fn (mixed $specialty): string => trim((string) $specialty),
            $specialties,
        )));

        if ($recommendedSpecialties === []) {
            throw new TriageExtractionException('Gemini returned no recommended specialties.');
        }

        return new self(
            chiefComplaint: $chiefComplaint,
            duration: $duration,
            recommendedSpecialties: $recommendedSpecialties,
            urgencyLevel: $urgency,
            clinicalSummary: $clinicalSummary,
        );
    }

    public function toSummary(): TriageSummary
    {
        return new TriageSummary(
            chiefComplaint: $this->chiefComplaint,
            duration: $this->duration,
            recommendedSpecialties: $this->recommendedSpecialties,
            urgencyLevel: $this->urgencyLevel,
            clinicalSummary: $this->clinicalSummary,
        );
    }
}
