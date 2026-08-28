<?php

namespace App\Enums;

enum UrgencyLevel: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';
    case Emergency = 'Emergency';
}
