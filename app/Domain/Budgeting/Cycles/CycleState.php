<?php

namespace App\Domain\Budgeting\Cycles;

enum CycleState: string
{
    case Open = 'open';
    case Closed = 'closed';
}
