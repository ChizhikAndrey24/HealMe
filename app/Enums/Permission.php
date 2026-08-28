<?php

namespace App\Enums;

enum Permission: string
{
    case AuditLogsView = 'audit_logs.view';
    case AppointmentsBook = 'appointments.book';
    case AppointmentsViewOwn = 'appointments.view_own';
    case TriageAnalyze = 'triage.analyze';
    case DoctorsMatch = 'doctors.match';
    case PatientCardViewOwn = 'patient_card.view_own';
    case PatientCardManageAccess = 'patient_card.manage_access';
    case PatientCardView = 'patient_card.view';
    case PatientCardWrite = 'patient_card.write';
    case AppointmentsManage = 'appointments.manage';
}
