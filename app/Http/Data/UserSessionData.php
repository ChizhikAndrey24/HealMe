<?php

namespace App\Http\Data;

use App\Domain\Users\Services\GoogleTokenService;
use App\Enums\UserRole;
use App\Models\User;

readonly class UserSessionData
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public UserRole $role,
        public ?string $avatar,
        public bool $patientReady,
        public bool $googleCalendarConnected,
        public array $permissions,
    ) {}

    public static function fromUser(User $user): self
    {
        $user->loadMissing('roles.permissions');

        $role = $user->primaryRole() ?? UserRole::Patient;
        $tokenService = app(GoogleTokenService::class);

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            role: $role,
            avatar: self::sessionAvatarUrl($user),
            patientReady: $user->hasRole(UserRole::Patient) && $user->patientProfile !== null,
            googleCalendarConnected: $role === UserRole::Doctor && $tokenService->hasCalendarAccess($user),
            permissions: $user->roles
                ->flatMap(fn ($assignedRole) => $assignedRole->permissions->pluck('name'))
                ->unique()
                ->values()
                ->all(),
        );
    }

    private static function sessionAvatarUrl(User $user): ?string
    {
        if (! is_string($user->avatar) || $user->avatar === '') {
            return null;
        }

        // Same-origin proxy avoids browsers/extensions blocking Google-hosted images.
        return '/api/v1/auth/avatar?u='.$user->id;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     role: string,
     *     avatar: string|null,
     *     patient_ready: bool,
     *     google_calendar_connected: bool,
     *     permissions: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'avatar' => $this->avatar,
            'patient_ready' => $this->patientReady,
            'google_calendar_connected' => $this->googleCalendarConnected,
            'permissions' => $this->permissions,
        ];
    }
}
