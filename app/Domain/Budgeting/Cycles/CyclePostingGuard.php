<?php

namespace App\Domain\Budgeting\Cycles;

use App\Domain\Budgeting\Exceptions\ClosedCycleReadOnly;
use App\Domain\Budgeting\Exceptions\NonCurrentCyclePostingNotAllowed;

class CyclePostingGuard
{
    public function assertCanPostAllocation(CycleState $cycleState, bool $isCurrentCycle): void
    {
        if (! $isCurrentCycle) {
            throw NonCurrentCyclePostingNotAllowed::create();
        }

        if ($cycleState === CycleState::Closed) {
            throw ClosedCycleReadOnly::create();
        }
    }
}
