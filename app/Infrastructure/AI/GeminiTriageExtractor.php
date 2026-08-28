<?php

namespace App\Infrastructure\AI;

use App\Domain\AI\Contracts\TriageExtractor;
use App\Domain\AI\Data\TriageSummary;
use App\Domain\AI\Exceptions\TriageExtractionException;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Domain\Compliance\Services\PhiMinimizer;
use App\Enums\HipaaAuditAction;
use App\Infrastructure\AI\Data\GeminiTriagePayload;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

readonly class GeminiTriageExtractor implements TriageExtractor
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a certified clinical triage assistant AI.
Analyze the patient's self-reported complaints and symptoms.
The notes may have direct identifiers redacted. Do not attempt to re-identify the patient.
Produce a structured JSON output with the following schema:
1. chief_complaint (string)
2. duration (string)
3. recommended_specialties (array of strings)
4. urgency_level (Low | Medium | High | Emergency)
5. clinical_summary (string)

Do NOT offer medical diagnosis to the patient. Maintain a neutral clinical tone.
Do NOT invent patient identifiers (name, DOB, MRN, contact details).
Return JSON only. Do not include markdown fences or explanatory text.
PROMPT;

    public function __construct(
        private HttpFactory $http,
        private PhiMinimizer $phiMinimizer,
        private HipaaAuditLogger $auditLogger,
    ) {}

    public function extract(string $symptoms): TriageSummary
    {
        $baseUrl = rtrim((string) config('services.gemini.base_url'), '/');
        $model = (string) config('services.gemini.model');
        $apiKey = (string) config('services.gemini.api_key');

        if ($apiKey === '') {
            throw new TriageExtractionException('Gemini API key is not configured.');
        }

        $minimizedNotes = $this->phiMinimizer->forExternalAi($symptoms);
        $fingerprint = $this->phiMinimizer->fingerprint($minimizedNotes);
        $startedAt = microtime(true);

        try {
            $response = $this->http
                ->baseUrl($baseUrl)
                ->acceptJson()
                ->withQueryParameters(['key' => $apiKey])
                ->post("/v1beta/models/{$model}:generateContent", [
                    'contents' => [[
                        'parts' => [[
                            'text' => self::SYSTEM_PROMPT."\n\nPatient notes (identifiers redacted):\n".$minimizedNotes,
                        ]],
                    ]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'chief_complaint' => ['type' => 'string'],
                                'duration' => ['type' => 'string'],
                                'recommended_specialties' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                                'urgency_level' => [
                                    'type' => 'string',
                                    'enum' => ['Low', 'Medium', 'High', 'Emergency'],
                                ],
                                'clinical_summary' => ['type' => 'string'],
                            ],
                            'required' => [
                                'chief_complaint',
                                'duration',
                                'recommended_specialties',
                                'urgency_level',
                                'clinical_summary',
                            ],
                        ],
                    ],
                ])
                ->throw();
        } catch (RequestException $exception) {
            $this->auditGemini(false, $model, $fingerprint, $startedAt, 'request_failed');
            // Never log request/response bodies — they may contain PHI.
            Log::warning('Gemini triage request failed.', [
                'model' => $model,
                'notes_hash' => $fingerprint['hash'],
                'notes_length' => $fingerprint['length'],
                'status' => $exception->response?->status(),
            ]);

            throw new TriageExtractionException('Gemini request failed.', previous: $exception);
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            $this->auditGemini(false, $model, $fingerprint, $startedAt, 'empty_payload');

            throw new TriageExtractionException('Gemini returned an empty triage payload.');
        }

        $this->auditGemini(true, $model, $fingerprint, $startedAt);

        return GeminiTriagePayload::toTriageSummary($text);
    }

    /**
     * @param  array{hash: string, length: int}  $fingerprint
     */
    private function auditGemini(
        bool $success,
        string $model,
        array $fingerprint,
        float $startedAt,
        ?string $failureReason = null,
    ): void {
        $this->auditLogger->record(
            action: HipaaAuditAction::GeminiRequest,
            outcome: $success ? 'success' : 'failure',
            phiInvolved: true,
            metadata: array_filter([
                'vendor' => 'google_gemini',
                'model' => $model,
                'notes_hash' => $fingerprint['hash'],
                'notes_length' => $fingerprint['length'],
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'failure_reason' => $failureReason,
            ], static fn (mixed $value): bool => $value !== null),
        );
    }
}
