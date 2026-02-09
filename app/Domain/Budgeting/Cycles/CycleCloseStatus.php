<?php

namespace App\Domain\Budgeting\Cycles;

enum CycleCloseStatus: string
{
    case BLOCKED = 'blocked';
    case READY_FOR_CONFIRMATION = 'ready_for_confirmation';
}
