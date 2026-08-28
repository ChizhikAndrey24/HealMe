<?php

namespace App\Domain\AI\Services;

use App\Domain\AI\Contracts\TriageExtractor;
use App\Domain\AI\Data\TriageSummary;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Enums\HipaaAuditAction;

readonly class TriageAnalysisService
{
    public function __construct(
        private TriageExtractor $extractor,
        private HipaaAuditLogger $auditLogger,
    ) {}

    public function analyze(string $symptoms): TriageSummary
    {
        $summary = $this->extractor->extract($symptoms);

        $this->auditLogger->record(
            action: HipaaAuditAction::TriageAnalyze,
            phiInvolved: true,
            metadata: [
                'urgency_level' => $summary->urgencyLevel->value,
                'specialty_count' => count($summary->recommendedSpecialties),
            ],
        );

        return $summary;
    }
}
