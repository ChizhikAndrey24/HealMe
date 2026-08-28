<?php

use App\Domain\AI\Contracts\TriageExtractor;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Doctors\Services\DoctorMatchingService;
use Illuminate\Support\Carbon;
use Tests\Support\FakeTriageExtractor;

test('it matches top doctors based on extracted specializations and upcoming availability', function () {
    Carbon::setTestNow('2026-08-19 10:00:00');

    app()->bind(TriageExtractor::class, fn () => new FakeTriageExtractor(['Cardiology']));

    $cardiologist = Doctor::factory()->create([
        'specialization' => 'Cardiology',
        'rating' => 4.9,
    ]);

    DoctorAvailabilitySlot::factory()->create([
        'doctor_id' => $cardiologist->id,
        'starts_at' => now()->addHours(4),
        'ends_at' => now()->addHours(4)->addMinutes(30),
    ]);

    $dermatologist = Doctor::factory()->create([
        'specialization' => 'Dermatology',
        'rating' => 5.0,
    ]);

    DoctorAvailabilitySlot::factory()->create([
        'doctor_id' => $dermatologist->id,
        'starts_at' => now()->addHours(2),
        'ends_at' => now()->addHours(2)->addMinutes(30),
    ]);

    $recommendedDoctors = app(DoctorMatchingService::class)->match('Severe chest pain and dizziness', limit: 3);

    expect($recommendedDoctors)->toHaveCount(1)
        ->and($recommendedDoctors->sole()->doctorId)->toBe($cardiologist->id);
});
