<?php

namespace Tests\Feature;

use App\Domain\Compliance\Models\HipaaAuditLog;
use App\Domain\Doctors\Models\Doctor;
use App\Enums\HipaaAuditAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HipaaAuditLogAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_audit_logs(): void
    {
        $admin = User::factory()->superAdmin()->create();

        HipaaAuditLog::query()->create([
            'actor_user_id' => $admin->id,
            'action' => HipaaAuditAction::AuthLogin,
            'outcome' => 'success',
            'phi_involved' => false,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => ['method' => 'password'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/audit-logs');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.action', HipaaAuditAction::AuthLogin->value)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 25)
            ->assertJsonPath('meta.total', 1);

        $this->assertDatabaseHas('hipaa_audit_logs', [
            'action' => HipaaAuditAction::AuditLogsView->value,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_super_admin_can_paginate_audit_logs(): void
    {
        $admin = User::factory()->superAdmin()->create();

        foreach (range(1, 3) as $index) {
            HipaaAuditLog::query()->create([
                'actor_user_id' => $admin->id,
                'action' => HipaaAuditAction::AuthLogin,
                'outcome' => 'success',
                'phi_involved' => false,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'phpunit',
                'metadata' => ['index' => $index],
                'created_at' => now()->subMinutes($index),
            ]);
        }

        $response = $this->actingAs($admin)->getJson('/api/v1/audit-logs?page=2&per_page=2');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_patients_cannot_view_audit_logs(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->getJson('/api/v1/audit-logs');

        $response->assertForbidden();
    }

    public function test_password_login_works_for_doctor(): void
    {
        $doctorUser = User::factory()->doctor()->create([
            'email' => 'doctor@healme.test',
            'password' => 'password',
        ]);

        Doctor::factory()->create([
            'user_id' => $doctorUser->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'doctor@healme.test',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.user.role', UserRole::Doctor->value);

        $this->assertAuthenticatedAs($doctorUser);
    }

    public function test_password_login_works_for_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'email' => 'admin@healme.test',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@healme.test',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.user.role', UserRole::SuperAdmin->value);

        $this->assertAuthenticatedAs($admin);
    }
}
