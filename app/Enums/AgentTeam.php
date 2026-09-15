<?php

namespace App\Enums;

enum AgentTeam: string
{
    case Support = 'support';
    case Billing = 'billing';
    case Technical = 'technical';
}
