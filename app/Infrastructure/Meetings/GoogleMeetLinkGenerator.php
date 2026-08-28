<?php

namespace App\Infrastructure\Meetings;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Meetings\Contracts\MeetingLinkGenerator;
use App\Domain\Meetings\Exceptions\MeetingLinkGenerationException;
use App\Domain\Users\Services\GoogleTokenService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

/**
 * Creates an open Google Meet link using the shared app Google account.
 * Doctors do not need to connect Google.
 */
readonly class GoogleMeetLinkGenerator implements MeetingLinkGenerator
{
    public function __construct(
        private HttpFactory $http,
        private GoogleTokenService $tokenService,
    ) {}

    public function createForAppointment(Appointment $appointment): string
    {
        $appointment->loadMissing(['availabilitySlot']);

        $accessToken = $this->tokenService->accessTokenForAppMeet();

        try {
            return $this->createOpenMeetSpace($accessToken);
        } catch (MeetingLinkGenerationException $spacesException) {
            return $this->createCalendarMeetFallback($appointment, $accessToken, $spacesException);
        }
    }

    private function createOpenMeetSpace(string $accessToken): string
    {
        $response = $this->http
            ->withToken($accessToken)
            ->acceptJson()
            ->post('https://meet.googleapis.com/v2/spaces', [
                'config' => [
                    'accessType' => 'OPEN',
                    'entryPointAccess' => 'ALL',
                ],
            ]);

        if (! $response->successful()) {
            throw new MeetingLinkGenerationException($this->googleErrorMessage(
                $response,
                'Google Meet space creation failed',
            ));
        }

        $meetingUri = $response->json('meetingUri');

        if (! is_string($meetingUri) || $meetingUri === '') {
            throw new MeetingLinkGenerationException('Google Meet response did not include a meeting URI.');
        }

        return $meetingUri;
    }

    private function createCalendarMeetFallback(
        Appointment $appointment,
        string $accessToken,
        MeetingLinkGenerationException $spacesException,
    ): string {
        $slot = $appointment->availabilitySlot;
        $startsAt = $slot?->starts_at ?? now()->addHour();
        $endsAt = $slot?->ends_at ?? $startsAt->clone()->addMinutes(30);
        $timezone = (string) config('app.timezone', 'UTC');
        $requestId = 'healme-'.$appointment->id.'-'.Str::lower(Str::random(8));

        $response = $this->http
            ->withToken($accessToken)
            ->acceptJson()
            ->withQueryParameters([
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'none',
            ])
            ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                'summary' => 'HealMe appointment #'.$appointment->id,
                'description' => 'Public Google Meet link for HealMe. Anyone with the link can join.',
                'start' => [
                    'dateTime' => $startsAt->clone()->timezone($timezone)->toIso8601String(),
                    'timeZone' => $timezone,
                ],
                'end' => [
                    'dateTime' => $endsAt->clone()->timezone($timezone)->toIso8601String(),
                    'timeZone' => $timezone,
                ],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => $requestId,
                        'conferenceSolutionKey' => [
                            'type' => 'hangoutsMeet',
                        ],
                    ],
                ],
                'guestsCanModify' => false,
                'guestsCanInviteOthers' => true,
                'transparency' => 'transparent',
            ]);

        if (! $response->successful()) {
            throw new MeetingLinkGenerationException(
                'Unable to create a public Meet link. '
                .$spacesException->getMessage()
                .' / '.$this->googleErrorMessage($response, 'Calendar Meet fallback failed'),
            );
        }

        $meetUrl = $this->extractMeetUrl($response->json() ?? []);

        if ($meetUrl === null) {
            throw new MeetingLinkGenerationException('Google Meet fallback did not include a Meet URL.');
        }

        return $meetUrl;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function extractMeetUrl(array $event): ?string
    {
        $hangoutLink = $event['hangoutLink'] ?? null;

        if (is_string($hangoutLink) && $hangoutLink !== '') {
            return $hangoutLink;
        }

        $entryPoints = data_get($event, 'conferenceData.entryPoints');

        if (! is_array($entryPoints)) {
            return null;
        }

        foreach ($entryPoints as $entryPoint) {
            if (! is_array($entryPoint)) {
                continue;
            }

            $type = $entryPoint['entryPointType'] ?? null;
            $uri = $entryPoint['uri'] ?? null;

            if ($type === 'video' && is_string($uri) && $uri !== '') {
                return $uri;
            }
        }

        foreach ($entryPoints as $entryPoint) {
            $uri = is_array($entryPoint) ? ($entryPoint['uri'] ?? null) : null;

            if (is_string($uri) && str_contains($uri, 'meet.google.com')) {
                return $uri;
            }
        }

        return null;
    }

    private function googleErrorMessage(Response $response, string $prefix): string
    {
        $googleMessage = $response->json('error.message')
            ?? $response->json('error.status')
            ?? $response->json('error');

        if (is_array($googleMessage)) {
            $googleMessage = $googleMessage['message'] ?? null;
        }

        $detail = is_string($googleMessage) && $googleMessage !== ''
            ? $googleMessage
            : 'HTTP '.$response->status();

        return $prefix.': '.$detail;
    }
}
