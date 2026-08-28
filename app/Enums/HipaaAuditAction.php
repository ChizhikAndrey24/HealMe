<?php

namespace App\Enums;

enum HipaaAuditAction: string
{
    case AuthLogin = 'auth.login';
    case AuthLogout = 'auth.logout';
    case AuthLoginFailed = 'auth.login_failed';
    case TriageAnalyze = 'triage.analyze';
    case DoctorsMatch = 'doctors.match';
    case GeminiRequest = 'gemini.request';
    case AppointmentCreate = 'appointments.create';
    case AppointmentList = 'appointments.list';
    case AuditLogsView = 'audit_logs.view';
    case PatientCardView = 'patient_card.view';
    case PatientCardNoteCreate = 'patient_card.note_create';
    case PatientCardAccessApprove = 'patient_card.access_approve';
    case PatientCardAccessRevoke = 'patient_card.access_revoke';
    case AppointmentConfirm = 'appointments.confirm';
    case AppointmentCancel = 'appointments.cancel';
}
