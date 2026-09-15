<?php

namespace App\Enums;

enum EscalationStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case PartiallyFailed = 'partially_failed';
    case Failed = 'failed';
}
