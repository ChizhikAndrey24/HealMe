# Testing Strategy & Guidelines — HealMe

## 1. Testing Philosophy (TDD Strategy)
HealMe strictly enforces **Test-Driven Development (TDD)** using **Pest PHP**. Developers must follow the **Red-Green-Refactor** framework:
1. **Red:** Write a failing test defining the expected input/output and behavior.
2. **Green:** Write the minimal code needed to make the test pass.
3. **Refactor:** Clean up code, enforce strict typing via Laravel Pint, and optimize without breaking tests.

## 2. Mocking Google Gemini & External Services
All tests interacting with Google Gemini AI or external APIs MUST use Laravel AI SDK mocks or standard Laravel HTTP fakes.

## 3. Sample Test Case (Doctor Matching Engine with Gemini Mock)

```php
<?php

use App\Domain\Doctors\Services\DoctorMatchingService;
use App\Domain\Doctors\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('it matches top doctors based on Gemini extracted specializations', function () {
    // Arrange
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => json_encode([
                                'recommended_specialties' => ['Cardiology'],
                                'urgency_level' => 'High',
                                'clinical_summary' => 'Chest pain reported.',
                            ])],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $cardiologist = Doctor::factory()->create(['specialization' => 'Cardiology']);
    $dermatologist = Doctor::factory()->create(['specialization' => 'Dermatology']);

    $matchingService = app(DoctorMatchingService::class);

    // Act
    $recommendedDoctors = $matchingService->match('Severe chest pain and dizziness', limit: 3);

    // Assert
    expect($recommendedDoctors)->toHaveCount(1)
        ->and($recommendedDoctors->first()->id)->toBe($cardiologist->id);
});
```

## 4. Running Tests in Docker
```bash
./vendor/bin/sail artisan test
./vendor/bin/sail pest --parallel
./vendor/bin/sail bin pint --test
```

Frontend checks (from the host or Sail, when JS/TS changed):
```bash
npm run lint
npm run typecheck
```
