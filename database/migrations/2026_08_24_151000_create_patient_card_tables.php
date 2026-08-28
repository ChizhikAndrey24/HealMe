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
        Schema::create('doctor_patient_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('source');
            $table->foreignId('granted_via_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamps();

            $table->unique(['patient_profile_id', 'doctor_id']);
            $table->index(['doctor_id', 'status']);
        });

        Schema::create('patient_card_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_profile_id')->constrained()->cascadeOnDelete();
            $table->string('author_type');
            $table->foreignId('author_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('source');
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['patient_profile_id', 'created_at']);
        });

        $now = now();
        $roleIds = DB::table('roles')->pluck('id', 'name');

        $permissions = [
            PermissionEnum::PatientCardViewOwn->value => 'View own patient card notes',
            PermissionEnum::PatientCardManageAccess->value => 'Approve or revoke doctor card access',
            PermissionEnum::PatientCardView->value => 'View approved patient cards',
            PermissionEnum::PatientCardWrite->value => 'Write notes on approved patient cards',
        ];

        $permissionIds = [];
        foreach ($permissions as $name => $displayName) {
            $permissionIds[$name] = DB::table('permissions')->insertGetId([
                'name' => $name,
                'display_name' => $displayName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $rolePermissions = [
            UserRole::Patient->value => [
                PermissionEnum::PatientCardViewOwn->value,
                PermissionEnum::PatientCardManageAccess->value,
            ],
            UserRole::Doctor->value => [
                PermissionEnum::PatientCardView->value,
                PermissionEnum::PatientCardWrite->value,
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $roleId = $roleIds[$roleName] ?? null;

            if ($roleId === null) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionIds[$permissionName],
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_card_notes');
        Schema::dropIfExists('doctor_patient_accesses');

        $names = [
            PermissionEnum::PatientCardViewOwn->value,
            PermissionEnum::PatientCardManageAccess->value,
            PermissionEnum::PatientCardView->value,
            PermissionEnum::PatientCardWrite->value,
        ];

        $permissionIds = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
