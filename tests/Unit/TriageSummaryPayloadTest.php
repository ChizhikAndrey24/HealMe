<?php

use App\Domain\AI\Data\TriageSummaryPayload;
use App\Domain\AI\Exceptions\TriageExtractionException;

test('it validates a complete triage payload', function () {
    $payload = TriageSummaryPayload::fromArray([
        'chief_complaint' => 'Chest pain',
        'duration' => '2 days',
        'recommended_specialties' => ['Cardiology'],
        'urgency_level' => 'High',
        'clinical_summary' => 'Patient reports chest pain.',
    ]);

    expect($payload->toSummary()->urgencyLevel->value)->toBe('High');
});

test('it rejects incomplete triage payloads', function () {
    TriageSummaryPayload::fromArray([
        'chief_complaint' => '',
        'duration' => '2 days',
        'recommended_specialties' => ['Cardiology'],
        'urgency_level' => 'High',
        'clinical_summary' => 'Patient reports chest pain.',
    ]);
})->throws(TriageExtractionException::class);
