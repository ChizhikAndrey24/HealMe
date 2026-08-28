<?php

namespace App\Domain\Doctors\Services;

use App\Domain\AI\Contracts\TriageExtractor;
use App\Domain\AI\Data\TriageSummary;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Domain\Doctors\Data\DoctorAvailabilitySlotSummary;
use App\Domain\Doctors\Data\DoctorMatchResult;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Enums\HipaaAuditAction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

readonly class DoctorMatchingService
{
    public function __construct(
        private TriageExtractor $triageExtractor,
        private HipaaAuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, DoctorMatchResult>
     */
    public function match(string $symptoms, int $limit = 5): Collection
    {
        $triage = $this->triageExtractor->extract($symptoms);

        $windowStart = CarbonImmutable::now();
        $horizonDays = max(1, (int) config('healme.availability_horizon_days', 365));
        $windowEnd = $windowStart->addDays($horizonDays);
        $slotsLimit = max(1, (int) config('healme.availability_slots_limit', 48));
        $specialties = $triage->recommendedSpecialties;

        $doctors = Doctor::query()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->when($specialties !== [], function (Builder $query) use ($specialties): void {
                $query->whereIn('specialization', $specialties);
            })
            ->with([
                'user',
                'availabilitySlots' => function ($query) use ($windowStart, $windowEnd, $slotsLimit): void {
                    $query->where('is_booked', false)
                        ->whereBetween('starts_at', [$windowStart, $windowEnd])
                        ->orderBy('starts_at')
                        ->limit($slotsLimit);
                },
            ])
            ->get();

        $matches = $doctors
            ->filter(static fn (Doctor $doctor): bool => $doctor->availabilitySlots->isNotEmpty())
            ->map(function (Doctor $doctor) use ($triage, $windowStart, $horizonDays): DoctorMatchResult {
                $nextSlot = $doctor->availabilitySlots->first();

                return new DoctorMatchResult(
                    doctorId: $doctor->id,
                    doctorName: $doctor->user?->name,
                    specialization: $doctor->specialization,
                    rating: (float) $doctor->rating,
                    yearsOfExperience: $doctor->years_of_experience,
                    matchScore: round($this->scoreDoctor($doctor, $triage, $windowStart, $horizonDays), 4),
                    nextAvailableSlot: $nextSlot?->starts_at?->toIso8601String(),
                    availableSlots: array_values($doctor->availabilitySlots
                        ->take(8)
                        ->map(static fn (DoctorAvailabilitySlot $slot): DoctorAvailabilitySlotSummary => DoctorAvailabilitySlotSummary::fromModel($slot))
                        ->all()),
                );
            })
            ->sortByDesc(fn (DoctorMatchResult $result): float => $result->matchScore)
            ->take($limit)
            ->values();

        $this->auditLogger->record(
            action: HipaaAuditAction::DoctorsMatch,
            phiInvolved: true,
            metadata: [
                'match_count' => $matches->count(),
                'specialty_count' => count($specialties),
            ],
        );

        return $matches;
    }

    private function scoreDoctor(
        Doctor $doctor,
        TriageSummary $triage,
        CarbonImmutable $windowStart,
        int $horizonDays,
    ): float {
        $specialtyMatch = in_array($doctor->specialization, $triage->recommendedSpecialties, true) ? 1.0 : 0.0;
        $ratingScore = min(((float) $doctor->rating) / 5, 1.0);
        $availabilityScore = $this->availabilityScore(
            $doctor->availabilitySlots->first(),
            $windowStart,
            $horizonDays,
        );

        return ($specialtyMatch * 0.5) + ($ratingScore * 0.3) + ($availabilityScore * 0.2);
    }

    private function availabilityScore(
        ?DoctorAvailabilitySlot $slot,
        CarbonImmutable $windowStart,
        int $horizonDays,
    ): float {
        if ($slot === null) {
            return 0.0;
        }

        $minutesUntilSlot = max($windowStart->diffInMinutes($slot->starts_at, false), 0);
        $windowMinutes = max($horizonDays, 1) * 24 * 60;

        return max(0.0, 1 - ($minutesUntilSlot / $windowMinutes));
    }
}
