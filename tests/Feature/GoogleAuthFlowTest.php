<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_callback_creates_and_logs_in_a_patient_user(): void
    {
        $providerUser = new SocialiteUser;
        $providerUser->map([
            'id' => 'google-123',
            'name' => 'Pat Doe',
            'email' => 'pat@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($providerUser);

        $response = $this
            ->withSession(['auth_role' => UserRole::Patient->value])
            ->get('/auth/google/callback');

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::query()->firstOrFail();

        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('https://example.com/avatar.png', $user->avatar);
        $this->assertTrue($user->hasRole(UserRole::Patient));
        $this->assertNotNull($user->patientProfile);
    }

    public function test_current_session_endpoint_returns_authenticated_user(): void
    {
        $user = User::factory()->patient()->create([
            'avatar' => 'https://lh3.googleusercontent.com/a/test-avatar=s96-c',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/session');

        $response
            ->assertOk()
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.role', UserRole::Patient->value)
            ->assertJsonPath('data.user.avatar', '/api/v1/auth/avatar?u='.$user->id);
    }

    public function test_avatar_proxy_streams_allowed_google_image(): void
    {
        $user = User::factory()->patient()->create([
            'avatar' => 'https://lh3.googleusercontent.com/a/test-avatar=s96-c',
        ]);

        Http::fake([
            'lh3.googleusercontent.com/*' => Http::response('fake-image', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $response = $this->actingAs($user)->get('/api/v1/auth/avatar');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertSee('fake-image', false);
    }
}
