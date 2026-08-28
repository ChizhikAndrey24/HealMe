<?php

namespace App\Domain\Compliance\Services;

/**
 * Prepares free-text clinical notes for external AI processing under minimum-necessary PHI.
 * Strips common direct identifiers before data leaves the application boundary.
 */
readonly class PhiMinimizer
{
    public function forExternalAi(string $notes): string
    {
        $minimized = trim($notes);

        // Email addresses
        $minimized = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[REDACTED_EMAIL]', $minimized) ?? $minimized;

        // Phone numbers (US-ish and international-ish)
        $minimized = preg_replace('/(?:\+?\d{1,3}[\s.-]?)?(?:\(?\d{2,4}\)?[\s.-]?)\d{3,4}[\s.-]?\d{3,4}/', '[REDACTED_PHONE]', $minimized) ?? $minimized;

        // SSN-like patterns
        $minimized = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[REDACTED_ID]', $minimized) ?? $minimized;

        // Dates that look like DOB (keep relative duration phrases like "4 days")
        $minimized = preg_replace(
            '/\b(?:0?[1-9]|1[0-2])[\/\-.](?:0?[1-9]|[12]\d|3[01])[\/\-.](?:19|20)\d{2}\b/',
            '[REDACTED_DATE]',
            $minimized,
        ) ?? $minimized;

        // MRN / member ID style tokens
        $minimized = preg_replace('/\b(?:MRN|Member\s*ID|Patient\s*ID)\s*[#:.]?\s*[A-Z0-9-]{4,}\b/i', '[REDACTED_ID]', $minimized) ?? $minimized;

        return trim($minimized);
    }

    /**
     * @return array{hash: string, length: int}
     */
    public function fingerprint(string $notes): array
    {
        return [
            'hash' => hash('sha256', $notes),
            'length' => mb_strlen($notes),
        ];
    }
}
