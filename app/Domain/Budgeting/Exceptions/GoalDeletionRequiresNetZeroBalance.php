<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class GoalDeletionRequiresNetZeroBalance extends DomainException
{
    public static function forBalance(int $cumulativeGoalEventBalance): self
    {
        return new self(sprintf(
            'Goal can only be soft-deleted at net-zero balance. Current cumulative balance is %d.',
            $cumulativeGoalEventBalance,
        ));
    }
}
