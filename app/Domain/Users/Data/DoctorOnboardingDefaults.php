<?php

namespace App\Domain\Users\Data;

readonly class DoctorOnboardingDefaults
{
    /**
     * @return array{
     *     specialization: string,
     *     bio: null,
     *     years_of_experience: int,
     *     rating: int,
     *     is_active: bool,
     *     is_verified: bool,
     *     meeting_provider: string
     * }
     */
    public function toCreateAttributes(): array
    {
        return [
            'specialization' => 'Pending Verification',
            'bio' => null,
            'years_of_experience' => 0,
            'rating' => 0,
            'is_active' => false,
            'is_verified' => false,
            'meeting_provider' => 'google_meet',
        ];
    }
}
