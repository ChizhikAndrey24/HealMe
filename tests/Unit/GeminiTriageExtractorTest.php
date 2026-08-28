<?php

use App\Domain\AI\Data\TriageSummary;
use App\Infrastructure\AI\GeminiTriageExtractor;
use Illuminate\Support\Facades\Http;

test('it parses a structured triage payload from gemini', function () {
    config()->set('services.gemini.api_key', 'test-key');
    config()->set('services.gemini.model', 'gemini-1.5-flash');
    config()->set('services.gemini.base_url', 'https://generativelanguage.googleapis.com');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'chief_complaint' => 'Persistent migraines',
                            'duration' => '4 days',
                            'recommended_specialties' => ['Neurology', 'General Practice'],
                            'urgency_level' => 'Medium',
                            'clinical_summary' => 'Patient reports migraine with aura and photophobia.',
                        ]),
                    ]],
                ],
            ]],
        ]),
    ]);

    $summary = app(GeminiTriageExtractor::class)->extract('Migraine with aura and light sensitivity.');

    expect($summary)
        ->toBeInstanceOf(TriageSummary::class)
        ->and($summary->chiefComplaint)->toBe('Persistent migraines')
        ->and($summary->recommendedSpecialties)->toBe(['Neurology', 'General Practice'])
        ->and($summary->urgencyLevel->value)->toBe('Medium');
});

test('it parses triage payload wrapped in markdown fences', function () {
    config()->set('services.gemini.api_key', 'test-key');
    config()->set('services.gemini.model', 'gemini-1.5-flash');
    config()->set('services.gemini.base_url', 'https://generativelanguage.googleapis.com');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => <<<'TEXT'
```json
{
  "chief_complaint": "Chest pain",
  "duration": "2 days",
  "recommended_specialties": ["Cardiology"],
  "urgency_level": "High",
  "clinical_summary": "Patient reports chest pain."
}
```
TEXT,
                    ]],
                ],
            ]],
        ]),
    ]);

    $summary = app(GeminiTriageExtractor::class)->extract('Chest pain for two days.');

    expect($summary->chiefComplaint)->toBe('Chest pain')
        ->and($summary->urgencyLevel->value)->toBe('High');
});
