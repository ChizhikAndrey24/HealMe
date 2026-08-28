<?php

namespace App\Domain\Users\Services;

use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Patients\Models\PatientProfile;
use App\Domain\Users\Data\DoctorOnboardingDefaults;
use App\Domain\Users\Data\GoogleIdentityData;
use App\Domain\Users\Data\GoogleUserSyncData;
use App\Enums\HipaaAuditAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

readonly class GoogleAuthenticationService
{
    public function __construct(
        private HipaaAuditLogger $auditLogger,
    ) {}

    public function authenticate(GoogleIdentityData $identity, UserRole $intendedRole): ?User
    {
        $user = User::query()
            ->with('roles')
            ->where('google_id', $identity->googleId)
            ->orWhere('email', $identity->email)
            ->first();

        if ($user !== null && ! $user->hasRole($intendedRole)) {
            $this->auditLogger->record(
                action: HipaaAuditAction::AuthLoginFailed,
                outcome: 'failure',
                metadata: [
                    'reason' => 'role_mismatch',
                    'intended_role' => $intendedRole->value,
                ],
            );

            return null;
        }

        $syncData = GoogleUserSyncData::fromIdentity($identity);
        $isNew = $user === null;

        $user ??= new User;
        $user->fill($syncData->toFillAttributes($user->exists ? $user : null));
        $user->save();

        if ($isNew) {
            $user->assignRole($intendedRole);
        }

        $this->ensureRoleProfile($user);

        Auth::login($user, true);
        session()->forget('auth_role');
        session()->regenerate();

        $this->auditLogger->record(
            action: HipaaAuditAction::AuthLogin,
            actor: $user,
            metadata: [
                'method' => 'google',
                'role' => $intendedRole->value,
                'new_user' => $isNew,
            ],
        );

        return $user;
    }

    private function ensureRoleProfile(User $user): void
    {
        if ($user->hasRole(UserRole::Patient)) {
            PatientProfile::query()->firstOrCreate(['user_id' => $user->id]);
        }

        if ($user->hasRole(UserRole::Doctor)) {
            Doctor::query()->firstOrCreate(
                ['user_id' => $user->id],
                (new DoctorOnboardingDefaults)->toCreateAttributes(),
            );
        }
    }
}
