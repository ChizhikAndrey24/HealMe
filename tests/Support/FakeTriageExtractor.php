<?php

namespace Tests\Support;

use App\Domain\AI\Contracts\TriageExtractor;
use App\Domain\AI\Data\TriageSummary;
use App\Enums\UrgencyLevel;

readonly class FakeTriageExtractor implements TriageExtractor
{
    /**
     * @param  list<string>  $recommendedSpecialties
     */
    public function __construct(
        private array $recommendedSpecialties = ['General Practice'],
    ) {}

    public function extract(string $symptoms): TriageSummary
    {
        return new TriageSummary(
            chiefComplaint: 'Chest pain',
            duration: '2 days',
            recommendedSpecialties: $this->recommendedSpecialties,
            urgencyLevel: UrgencyLevel::High,
            clinicalSummary: 'Patient reports chest pain and dizziness.',
        );
    }
}
