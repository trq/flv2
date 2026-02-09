<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class ExpenseGoalCapExceeded extends DomainException
{
    public static function forAllocation(
        int $goalAmount,
        int $alreadyAllocatedAmount,
        int $requestedAllocationAmount,
    ): self {
        $remainingAmount = $goalAmount - $alreadyAllocatedAmount;

        return new self(sprintf(
            'Cannot allocate %s because this expense goal has a hard cap of %s with only %s remaining.',
            self::formatCurrency($requestedAllocationAmount),
            self::formatCurrency($goalAmount),
            self::formatCurrency(max(0, $remainingAmount)),
        ));
    }

    private static function formatCurrency(int $amount): string
    {
        return '$'.number_format($amount, 0, '.', ',');
    }
}
