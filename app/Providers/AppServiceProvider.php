<?php

namespace App\Providers;

use App\Domain\AI\Contracts\TriageExtractor;
use App\Domain\Meetings\Contracts\MeetingLinkGenerator;
use App\Infrastructure\AI\GeminiTriageExtractor;
use App\Infrastructure\Meetings\FakeMeetingLinkGenerator;
use App\Infrastructure\Meetings\GoogleMeetLinkGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TriageExtractor::class, GeminiTriageExtractor::class);

        $this->app->bind(MeetingLinkGenerator::class, function ($app) {
            $driver = (string) config('services.google.meet_driver', 'fake');

            return $driver === 'google'
                ? $app->make(GoogleMeetLinkGenerator::class)
                : $app->make(FakeMeetingLinkGenerator::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
