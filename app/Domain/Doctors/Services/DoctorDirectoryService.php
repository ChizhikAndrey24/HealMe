<?php

namespace App\Domain\Doctors\Services;

use App\Domain\Doctors\Data\DoctorDirectoryPage;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Patients\Models\DoctorPatientAccess;
use App\Domain\Patients\Models\PatientProfile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

readonly class DoctorDirectoryService
{
    public function listForPatient(
        PatientProfile $patientProfile,
        ?string $name = null,
        ?string $specialization = null,
        int $page = 1,
        int $perPage = 12,
    ): DoctorDirectoryPage {
        $accessByDoctor = DoctorPatientAccess::query()
            ->where('patient_profile_id', $patientProfile->id)
            ->get()
            ->keyBy('doctor_id');

        $windowStart = CarbonImmutable::now();
        $horizonDays = max(1, (int) config('healme.availability_horizon_days', 365));
        $windowEnd = $windowStart->addDays($horizonDays);
        $slotsLimit = max(1, (int) config('healme.availability_slots_limit', 48));
        $perPage = min(max($perPage, 1), 50);
        $page = max($page, 1);

        $name = $name !== null ? trim($name) : null;
        $specialization = $specialization !== null ? trim($specialization) : null;

        $query = Doctor::query()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->with([
                'user',
                'availabilitySlots' => function ($slotsQuery) use ($windowStart, $windowEnd, $slotsLimit): void {
                    $slotsQuery->where('is_booked', false)
                        ->whereBetween('starts_at', [$windowStart, $windowEnd])
                        ->orderBy('starts_at')
                        ->limit($slotsLimit);
                },
            ])
            ->when(
                $name !== null && $name !== '',
                fn (Builder $builder) => $builder->whereHas(
                    'user',
                    fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$name.'%'),
                ),
            )
            ->when(
                $specialization !== null && $specialization !== '',
                fn (Builder $builder) => $builder->where('specialization', $specialization),
            )
            ->orderBy('specialization')
            ->orderByDesc('rating');

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $specializations = Doctor::query()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->distinct()
            ->orderBy('specialization')
            ->pluck('specialization')
            ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();

        return new DoctorDirectoryPage(
            paginator: $paginator,
            accessByDoctor: $accessByDoctor,
            specializations: $specializations,
        );
    }
}
