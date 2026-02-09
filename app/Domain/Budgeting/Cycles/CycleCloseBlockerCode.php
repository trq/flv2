<?php

namespace App\Domain\Budgeting\Cycles;

enum CycleCloseBlockerCode: string
{
    case PENDING_EVENTS = 'pending_events';
}
