<?php

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Doctors\Services\DoctorAvailabilitySlotGenerator;
use Carbon\CarbonImmutable;

test('it generates open slots spanning the availability horizon year', function () {
    CarbonImmutable::setTestNow('2026-08-24 09:00:00');

    $doctor = Doctor::factory()->create();
    $created = app(DoctorAvailabilitySlotGenerator::class)->generateForDoctor($doctor);

    expect($created)->toBeGreaterThan(100);

    $first = DoctorAvailabilitySlot::query()
        ->where('doctor_id', $doctor->id)
        ->orderBy('starts_at')
        ->first();
    $last = DoctorAvailabilitySlot::query()
        ->where('doctor_id', $doctor->id)
        ->orderByDesc('starts_at')
        ->first();

    expect($first)->not->toBeNull()
        ->and($last)->not->toBeNull()
        ->and($first->starts_at->greaterThanOrEqualTo(now()))->toBeTrue()
        ->and($last->starts_at->greaterThan(now()->addMonths(10)))->toBeTrue();
});
