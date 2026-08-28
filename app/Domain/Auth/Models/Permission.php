<?php

namespace App\Domain\Auth\Models;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 */
#[Fillable(['name', 'display_name'])]
class Permission extends Model
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public static function findByName(PermissionEnum|string $permission): ?self
    {
        $name = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return static::query()->where('name', $name)->first();
    }
}
