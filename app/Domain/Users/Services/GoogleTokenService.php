<?php

namespace App\Domain\Users\Services;

use App\Domain\Meetings\Exceptions\MeetingLinkGenerationException;
use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

readonly class GoogleTokenService
{
    private const string AppMeetAccessTokenCacheKey = 'healme.google.meet.access_token';

    public function __construct(
        private HttpFactory $http,
    ) {}

    public function storeFromSocialite(User $user, object $socialiteUser): void
    {
        $token = $socialiteUser->token ?? null;
        $refreshToken = $socialiteUser->refreshToken ?? null;
        $expiresIn = $socialiteUser->expiresIn ?? null;

        if (! is_string($token) || $token === '') {
            return;
        }

        $user->forceFill([
            'google_access_token' => $token,
            'google_refresh_token' => is_string($refreshToken) && $refreshToken !== ''
                ? $refreshToken
                : $user->google_refresh_token,
            'google_token_expires_at' => is_numeric($expiresIn)
                ? now()->addSeconds((int) $expiresIn)
                : $user->google_token_expires_at,
        ])->save();
    }

    public function hasCalendarAccess(User $user): bool
    {
        return (is_string($user->google_access_token) && $user->google_access_token !== '')
            || (is_string($user->google_refresh_token) && $user->google_refresh_token !== '');
    }

    /**
     * App-level token used to create Meet links for any appointment (no doctor OAuth required).
     */
    public function accessTokenForAppMeet(): string
    {
        $cached = Cache::get(self::AppMeetAccessTokenCacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $refreshToken = config('services.google.meet_refresh_token');

        if (! is_string($refreshToken) || $refreshToken === '') {
            throw new MeetingLinkGenerationException(
                'Google Meet is not configured. Set GOOGLE_MEET_REFRESH_TOKEN in the environment.',
            );
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            throw new MeetingLinkGenerationException(
                'Google Meet is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.',
            );
        }

        $response = $this->http
            ->asForm()
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            $googleMessage = $response->json('error_description') ?? $response->json('error');
            $detail = is_string($googleMessage) && $googleMessage !== ''
                ? $googleMessage
                : 'HTTP '.$response->status();

            throw new MeetingLinkGenerationException(
                'Unable to refresh the app Google Meet token: '.$detail,
            );
        }

        $accessToken = (string) $response->json('access_token');
        $expiresIn = max(60, (int) $response->json('expires_in', 3600) - 60);

        if ($accessToken === '') {
            throw new MeetingLinkGenerationException('Google token refresh returned an empty access token.');
        }

        Cache::put(self::AppMeetAccessTokenCacheKey, $accessToken, now()->addSeconds($expiresIn));

        return $accessToken;
    }

    public function accessTokenFor(User $user): string
    {
        if (! $this->hasCalendarAccess($user)) {
            throw new MeetingLinkGenerationException(
                'Doctor Google Meet access is not connected. Sign in again with Google as a doctor.',
            );
        }

        if (
            is_string($user->google_access_token)
            && $user->google_access_token !== ''
            && $this->accessTokenLooksFresh($user)
        ) {
            return $user->google_access_token;
        }

        if (! is_string($user->google_refresh_token) || $user->google_refresh_token === '') {
            throw new MeetingLinkGenerationException(
                'Doctor Google Meet access expired. Sign in again with Google as a doctor.',
            );
        }

        $response = $this->http
            ->asForm()
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $user->google_refresh_token,
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            $googleMessage = $response->json('error_description') ?? $response->json('error');
            $detail = is_string($googleMessage) && $googleMessage !== ''
                ? $googleMessage
                : 'HTTP '.$response->status();

            throw new MeetingLinkGenerationException(
                'Unable to refresh Google Meet access token: '.$detail,
            );
        }

        $accessToken = (string) $response->json('access_token');
        $expiresIn = (int) $response->json('expires_in', 3600);

        if ($accessToken === '') {
            throw new MeetingLinkGenerationException('Google token refresh returned an empty access token.');
        }

        $user->forceFill([
            'google_access_token' => $accessToken,
            'google_token_expires_at' => now()->addSeconds($expiresIn),
        ])->save();

        return $accessToken;
    }

    private function accessTokenLooksFresh(User $user): bool
    {
        if (! $user->google_token_expires_at instanceof Carbon) {
            return true;
        }

        return $user->google_token_expires_at->greaterThan(now()->addMinute());
    }
}
