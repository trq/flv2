<?php

namespace App\Domain\Budgeting\Cycles;

enum CycleCloseStepStatus: string
{
    case BLOCKED = 'blocked';
    case PASSED = 'passed';
    case COMPLETED = 'completed';
    case AWAITING_CONFIRMATION = 'awaiting_confirmation';
}
