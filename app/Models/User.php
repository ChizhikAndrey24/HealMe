<?php

namespace App\Models;

use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne as HasOneRelation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_id
 * @property string|null $avatar
 * @property string|null $google_access_token
 * @property string|null $google_refresh_token
 * @property Carbon|null $google_token_expires_at
 * @property-read Doctor|null $doctorProfile
 * @property-read PatientProfile|null $patientProfile
 */
#[Fillable(['name', 'email', 'google_id', 'avatar', 'password'])]
#[Hidden(['password', 'remember_token', 'google_access_token', 'google_refresh_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_access_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'google_token_expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * @return HasOneRelation<Doctor, $this>
     */
    public function doctorProfile(): HasOneRelation
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * @return HasOneRelation<PatientProfile, $this>
     */
    public function patientProfile(): HasOneRelation
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function assignRole(UserRole|string|Role $role): void
    {
        $roleModel = $role instanceof Role
            ? $role
            : Role::findByName($role);

        if ($roleModel === null) {
            throw new \InvalidArgumentException('Unknown role.');
        }

        $this->roles()->syncWithoutDetaching([$roleModel->id]);
        $this->unsetRelation('roles');
    }

    public function hasRole(UserRole|string $role): bool
    {
        $name = $role instanceof UserRole ? $role->value : $role;

        return $this->roles->contains(fn (Role $assigned): bool => $assigned->name === $name);
    }

    public function hasPermission(PermissionEnum|string $permission): bool
    {
        $name = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains(fn (Permission $assigned): bool => $assigned->name === $name);
    }

    /**
     * Primary role for session/UI compatibility (users currently have one role).
     */
    public function primaryRole(): ?UserRole
    {
        $name = $this->roles->first()?->name;

        return $name !== null ? UserRole::tryFrom($name) : null;
    }
}
