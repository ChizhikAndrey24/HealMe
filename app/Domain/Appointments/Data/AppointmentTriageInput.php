<?php

namespace App\Domain\Appointments\Data;

use App\Domain\AI\Data\TriageSummary;
use App\Enums\UrgencyLevel;
use App\Http\Requests\BookAppointmentRequest;

readonly class AppointmentTriageInput
{
    public function __construct(
        public string $symptoms,
        public string $chiefComplaint,
        public string $clinicalSummary,
        public UrgencyLevel $urgencyLevel,
    ) {}

    public static function fromRequest(BookAppointmentRequest $request): self
    {
        return new self(
            symptoms: $request->string('symptoms')->toString(),
            chiefComplaint: $request->string('chief_complaint')->toString(),
            clinicalSummary: $request->string('clinical_summary')->toString(),
            urgencyLevel: UrgencyLevel::from($request->string('urgency_level')->toString()),
        );
    }

    public static function fromTriageSummary(TriageSummary $summary, string $symptoms): self
    {
        return new self(
            symptoms: $symptoms,
            chiefComplaint: $summary->chiefComplaint,
            clinicalSummary: $summary->clinicalSummary,
            urgencyLevel: $summary->urgencyLevel,
        );
    }
}
