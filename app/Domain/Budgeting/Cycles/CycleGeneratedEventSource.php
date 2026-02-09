<?php

namespace App\Domain\Budgeting\Cycles;

enum CycleGeneratedEventSource: string
{
    case ADJUSTMENT_SWEEP = 'adjustment_sweep';
    case ROLLOVER_INCOME_ADJUSTMENT = 'rollover_income_adjustment';
}
