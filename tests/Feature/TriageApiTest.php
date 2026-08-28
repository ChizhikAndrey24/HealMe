<?php

namespace Tests\Feature;

use App\Domain\AI\Contracts\TriageExtractor;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeTriageExtractor;
use Tests\TestCase;

class TriageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_triage_api_returns_structured_summary_data(): void
    {
        app()->bind(TriageExtractor::class, fn () => new FakeTriageExtractor(['Neurology', 'General Practice']));

        $user = User::factory()->patient()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/triage/analyze', [
            'symptoms' => 'I have had migraines with aura and severe light sensitivity for four days.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.chief_complaint', 'Chest pain')
            ->assertJsonPath('data.recommended_specialties.0', 'Neurology')
            ->assertJsonPath('data.urgency_level', 'High');
    }

    public function test_guests_cannot_analyze_symptoms(): void
    {
        $response = $this->postJson('/api/v1/triage/analyze', [
            'symptoms' => 'I have had migraines with aura and severe light sensitivity for four days.',
        ]);

        $response->assertUnauthorized();
    }

    public function test_doctors_cannot_analyze_symptoms(): void
    {
        $user = User::factory()->doctor()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/triage/analyze', [
            'symptoms' => 'I have had migraines with aura and severe light sensitivity for four days.',
        ]);

        $response->assertForbidden();
    }

    public function test_session_exposes_permissions_from_role_tables(): void
    {
        $user = User::factory()->superAdmin()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/auth/session');

        $response
            ->assertOk()
            ->assertJsonPath('data.user.role', UserRole::SuperAdmin->value)
            ->assertJsonPath('data.user.permissions.0', 'audit_logs.view');
    }
}
