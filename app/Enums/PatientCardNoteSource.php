<?php

namespace App\Enums;

enum PatientCardNoteSource: string
{
    case Manual = 'manual';
    case Gemini = 'gemini';
}
