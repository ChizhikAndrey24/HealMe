<?php

namespace App\Enums;

enum PatientCardNoteAuthor: string
{
    case Doctor = 'doctor';
    case System = 'system';
}
