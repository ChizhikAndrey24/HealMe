<?php

namespace App\Infrastructure\AI\Data;

use App\Domain\AI\Data\TriageSummary;
use App\Domain\AI\Data\TriageSummaryPayload;
use App\Domain\AI\Exceptions\TriageExtractionException;

readonly class GeminiTriagePayload
{
    public static function fromJsonString(string $text): TriageSummaryPayload
    {
        return TriageSummaryPayload::fromArray(self::decodeJsonObject($text));
    }

    public static function toTriageSummary(string $text): TriageSummary
    {
        return self::fromJsonString($text)->toSummary();
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeJsonObject(string $text): array
    {
        $decoded = json_decode(trim($text), true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches) === 1) {
            $decoded = json_decode($matches[1], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new TriageExtractionException('Gemini returned invalid JSON for triage payload.');
    }
}
