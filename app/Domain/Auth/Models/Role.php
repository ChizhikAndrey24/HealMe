<?php

namespace App\Domain\Auth\Models;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 */
#[Fillable(['name', 'display_name'])]
class Role extends Model
{
    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function findByName(UserRole|string $role): ?self
    {
        $name = $role instanceof UserRole ? $role->value : $role;

        return static::query()->where('name', $name)->first();
    }
}
