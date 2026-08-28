<?php

namespace App\Domain\Doctors\Services;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

readonly class DoctorAvailabilitySlotGenerator
{
    /**
     * Create weekday open slots for a doctor across the availability horizon.
     *
     * @return int Number of slots created
     */
    public function generateForDoctor(Doctor $doctor, ?CarbonImmutable $from = null): int
    {
        $from ??= CarbonImmutable::now()->startOfHour();
        $horizonDays = max(1, (int) config('healme.availability_horizon_days', 365));
        $until = $from->addDays($horizonDays);

        $rows = [];
        $now = now();
        $clinicWeekdays = [
            CarbonImmutable::TUESDAY,
            CarbonImmutable::THURSDAY,
        ];

        for ($day = $from->startOfDay(); $day->lt($until); $day = $day->addDay()) {
            if (! in_array($day->dayOfWeek, $clinicWeekdays, true)) {
                continue;
            }

            foreach ([10, 14] as $hour) {
                $start = $day->setTime($hour, 0);

                if ($start->lt($from) || $start->gte($until)) {
                    continue;
                }

                $rows[] = [
                    'doctor_id' => $doctor->id,
                    'starts_at' => $start->toDateTimeString(),
                    'ends_at' => $start->addMinutes(45)->toDateTimeString(),
                    'is_booked' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows === []) {
            return 0;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('doctor_availability_slots')->insert($chunk);
        }

        return count($rows);
    }

    /**
     * Replace future unbooked slots and regenerate the horizon for every active doctor.
     */
    public function regenerateAllFutureOpenSlots(): int
    {
        $from = CarbonImmutable::now();

        DoctorAvailabilitySlot::query()
            ->where('is_booked', false)
            ->where('starts_at', '>=', $from)
            ->delete();

        $created = 0;

        Doctor::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(50, function ($doctors) use ($from, &$created): void {
                foreach ($doctors as $doctor) {
                    $created += $this->generateForDoctor($doctor, $from);
                }
            });

        return $created;
    }
}
