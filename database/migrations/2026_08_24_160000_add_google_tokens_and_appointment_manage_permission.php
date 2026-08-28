<?php

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('google_access_token')->nullable()->after('avatar');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token');
        });

        $now = now();
        $roleId = DB::table('roles')->where('name', UserRole::Doctor->value)->value('id');

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => PermissionEnum::AppointmentsManage->value,
            'display_name' => 'Manage and approve own appointments',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($roleId !== null) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', PermissionEnum::AppointmentsManage->value)
            ->value('id');

        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
            ]);
        });
    }
};
