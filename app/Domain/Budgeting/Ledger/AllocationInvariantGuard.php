<?php

namespace App\Domain\Budgeting\Ledger;

use App\Domain\Budgeting\Exceptions\ExpenseGoalCapExceeded;
use App\Domain\Budgeting\Exceptions\InsufficientPoolFunds;

class AllocationInvariantGuard
{
    public function assertSufficientPoolFunding(
        int $availablePoolAmount,
        int $allocationAmount,
        bool $consumesPool = true,
    ): void {
        if (! $consumesPool || $allocationAmount <= 0) {
            return;
        }

        if ($allocationAmount > $availablePoolAmount) {
            throw InsufficientPoolFunds::forAllocation(
                availablePoolAmount: $availablePoolAmount,
                requestedAllocationAmount: $allocationAmount,
            );
        }
    }

    public function assertExpenseGoalCapacity(
        int $goalAmount,
        int $alreadyAllocatedAmount,
        int $allocationAmount,
    ): void {
        if ($allocationAmount <= 0) {
            return;
        }

        $newAllocatedAmount = $alreadyAllocatedAmount + $allocationAmount;

        if ($newAllocatedAmount > $goalAmount) {
            throw ExpenseGoalCapExceeded::forAllocation(
                goalAmount: $goalAmount,
                alreadyAllocatedAmount: $alreadyAllocatedAmount,
                requestedAllocationAmount: $allocationAmount,
            );
        }
    }

    public function calculateAvailablePool(
        int $incomeTotal,
        int $reconciledPoolUsageTotal,
        int $pendingPoolUsageTotal,
    ): int {
        return $incomeTotal - $reconciledPoolUsageTotal - $pendingPoolUsageTotal;
    }
}
