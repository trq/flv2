<?php

namespace App\Domain\Budgeting\Cycles;

use App\Domain\Budgeting\Exceptions\InvalidCycleStateTransition;
use App\Domain\Budgeting\Exceptions\MultipleOpenCyclesNotAllowed;

class CycleStateMachine
{
    public function openCycle(int $existingOpenCycleCount): CycleState
    {
        if ($existingOpenCycleCount > 0) {
            throw MultipleOpenCyclesNotAllowed::forOpenCycleCount($existingOpenCycleCount);
        }

        return CycleState::Open;
    }

    public function closeCycle(CycleState $currentState): CycleState
    {
        if ($currentState !== CycleState::Open) {
            throw InvalidCycleStateTransition::forTransition(
                from: $currentState,
                to: CycleState::Closed,
            );
        }

        return CycleState::Closed;
    }
}
