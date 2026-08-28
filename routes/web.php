<?php

use App\Enums\Permission;
use App\Http\Controllers\Api\DoctorMatchController;
use App\Http\Controllers\Api\TriageAnalysisController;
use App\Http\Controllers\Web\AppointmentBookingController;
use App\Http\Controllers\Web\Auth\GoogleAuthController;
use App\Http\Controllers\Web\Auth\SessionController;
use App\Http\Controllers\Web\DoctorAppointmentController;
use App\Http\Controllers\Web\DoctorDirectoryController;
use App\Http\Controllers\Web\DoctorPatientCardController;
use App\Http\Controllers\Web\HipaaAuditLogController;
use App\Http\Controllers\Web\PatientAppointmentsController;
use App\Http\Controllers\Web\PatientCardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::prefix('auth')->group(function (): void {
    Route::get('/google/redirect/{role}', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);
});

Route::prefix('api/v1')->group(function (): void {
    Route::get('/auth/session', [SessionController::class, 'show']);
    Route::post('/auth/login', [SessionController::class, 'login']);
    Route::post('/auth/logout', [SessionController::class, 'destroy'])->middleware('auth');
    Route::get('/auth/avatar', [SessionController::class, 'avatar'])->middleware('auth');

    Route::middleware('auth')->group(function (): void {
        Route::get('/appointments', PatientAppointmentsController::class)
            ->middleware('permission:'.Permission::AppointmentsViewOwn->value);
        Route::post('/appointments', AppointmentBookingController::class)
            ->middleware('permission:'.Permission::AppointmentsBook->value);

        Route::post('/triage/analyze', TriageAnalysisController::class)
            ->middleware('permission:'.Permission::TriageAnalyze->value);
        Route::post('/doctors/match', DoctorMatchController::class)
            ->middleware('permission:'.Permission::DoctorsMatch->value);

        Route::get('/doctors', DoctorDirectoryController::class)
            ->middleware('permission:'.Permission::PatientCardManageAccess->value);

        Route::get('/patient-card/notes', [PatientCardController::class, 'notes'])
            ->middleware('permission:'.Permission::PatientCardViewOwn->value);
        Route::get('/patient-card/access', [PatientCardController::class, 'access'])
            ->middleware('permission:'.Permission::PatientCardManageAccess->value);
        Route::post('/patient-card/access/approve', [PatientCardController::class, 'approveAccess'])
            ->middleware('permission:'.Permission::PatientCardManageAccess->value);
        Route::post('/patient-card/access/revoke', [PatientCardController::class, 'revokeAccess'])
            ->middleware('permission:'.Permission::PatientCardManageAccess->value);

        Route::get('/doctor/patients', [DoctorPatientCardController::class, 'patients'])
            ->middleware('permission:'.Permission::PatientCardView->value);
        Route::get('/doctor/patients/{patientProfileId}/notes', [DoctorPatientCardController::class, 'notes'])
            ->middleware('permission:'.Permission::PatientCardView->value);
        Route::post('/doctor/patients/{patientProfileId}/notes', [DoctorPatientCardController::class, 'storeNote'])
            ->middleware('permission:'.Permission::PatientCardWrite->value);

        Route::get('/doctor/appointments', [DoctorAppointmentController::class, 'index'])
            ->middleware('permission:'.Permission::AppointmentsManage->value);
        Route::post('/doctor/appointments/{appointmentId}/approve', [DoctorAppointmentController::class, 'approve'])
            ->middleware('permission:'.Permission::AppointmentsManage->value);
        Route::post('/doctor/appointments/{appointmentId}/reject', [DoctorAppointmentController::class, 'reject'])
            ->middleware('permission:'.Permission::AppointmentsManage->value);

        Route::get('/audit-logs', HipaaAuditLogController::class)
            ->middleware('permission:'.Permission::AuditLogsView->value);
    });
});
