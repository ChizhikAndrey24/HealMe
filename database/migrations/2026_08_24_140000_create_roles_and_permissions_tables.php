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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
        });

        $now = now();

        $roleIds = [];
        foreach ([
            UserRole::SuperAdmin->value => 'Super Admin',
            UserRole::Doctor->value => 'Doctor',
            UserRole::Patient->value => 'Patient',
        ] as $name => $displayName) {
            $roleIds[$name] = DB::table('roles')->insertGetId([
                'name' => $name,
                'display_name' => $displayName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = [];
        foreach ([
            PermissionEnum::AuditLogsView->value => 'View HIPAA audit logs',
            PermissionEnum::AppointmentsBook->value => 'Book appointments',
            PermissionEnum::AppointmentsViewOwn->value => 'View own appointments',
            PermissionEnum::TriageAnalyze->value => 'Analyze symptoms with triage AI',
            PermissionEnum::DoctorsMatch->value => 'Match doctors from triage',
        ] as $name => $displayName) {
            $permissionIds[$name] = DB::table('permissions')->insertGetId([
                'name' => $name,
                'display_name' => $displayName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $rolePermissions = [
            UserRole::SuperAdmin->value => [
                PermissionEnum::AuditLogsView->value,
            ],
            UserRole::Patient->value => [
                PermissionEnum::AppointmentsBook->value,
                PermissionEnum::AppointmentsViewOwn->value,
                PermissionEnum::TriageAnalyze->value,
                PermissionEnum::DoctorsMatch->value,
            ],
            UserRole::Doctor->value => [],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            foreach ($permissions as $permissionName) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissionIds[$permissionName],
                    'role_id' => $roleIds[$roleName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasColumn('users', 'role')) {
            $users = DB::table('users')->select(['id', 'role'])->get();

            foreach ($users as $user) {
                $roleId = $roleIds[$user->role] ?? $roleIds[UserRole::Patient->value];

                DB::table('role_user')->insert([
                    'role_id' => $roleId,
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default(UserRole::Patient->value)->after('password');
            });

            $roleNames = DB::table('roles')->pluck('name', 'id');
            $assignments = DB::table('role_user')->get();

            foreach ($assignments as $assignment) {
                DB::table('users')
                    ->where('id', $assignment->user_id)
                    ->update(['role' => $roleNames[$assignment->role_id] ?? UserRole::Patient->value]);
            }
        }

        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
