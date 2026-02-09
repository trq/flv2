<?php

namespace App\Domain\Budgeting\Cycles;

enum CycleState: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
}
