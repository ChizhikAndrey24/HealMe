<?php

use App\Enums\AppointmentStatus;
use App\Enums\UrgencyLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_availability_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->text('symptoms');
            $table->text('chief_complaint')->nullable();
            $table->text('clinical_summary')->nullable();
            $table->string('urgency_level')->default(UrgencyLevel::Medium->value);
            $table->string('status')->default(AppointmentStatus::Pending->value);
            $table->string('google_meet_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
