<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Compliance\Models\HipaaAuditLog;
use App\Enums\HipaaAuditAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Writes HIPAA-oriented audit events to the dedicated audit table.
 * Never pass raw PHI into $metadata — use hashes, lengths, and IDs only.
 */
readonly class HipaaAuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        HipaaAuditAction $action,
        string $outcome = 'success',
        bool $phiInvolved = false,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $metadata = [],
        ?User $actor = null,
        ?Request $request = null,
    ): HipaaAuditLog {
        $request ??= request();
        $actor ??= Auth::user();

        return HipaaAuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'outcome' => $outcome,
            'phi_involved' => $phiInvolved,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $this->sanitizeMetadata($metadata),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $blocked = [
            'symptoms',
            'chief_complaint',
            'clinical_summary',
            'medical_history',
            'body',
            'note',
            'notes',
            'password',
            'token',
            'email',
            'name',
            'date_of_birth',
            'dob',
            'ssn',
            'phone',
        ];

        $clean = [];

        foreach ($metadata as $key => $value) {
            $normalized = strtolower((string) $key);

            if (in_array($normalized, $blocked, true)) {
                continue;
            }

            if (is_string($value) && strlen($value) > 500) {
                $clean[$key] = substr($value, 0, 500).'…';

                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
