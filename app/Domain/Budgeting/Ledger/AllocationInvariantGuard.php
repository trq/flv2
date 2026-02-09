<?php

namespace App\Domain\Budgeting\Ledger;

use App\Domain\Budgeting\Exceptions\ExpenseGoalCapExceeded;
use App\Domain\Budgeting\Exceptions\InsufficientPoolFunds;

class AllocationInvariantGuard
{
    public function assertSufficientPoolFunding(
        float $availablePoolAmount,
        float $allocationAmount,
        bool $consumesPool = true,
    ): void {
        if (! $consumesPool || $allocationAmount <= 0.0) {
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
        float $goalAmount,
        float $alreadyAllocatedAmount,
        float $allocationAmount,
    ): void {
        if ($allocationAmount <= 0.0) {
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
        float $incomeTotal,
        float $reconciledPoolUsageTotal,
        float $pendingPoolUsageTotal,
    ): float {
        return round(
            $incomeTotal - $reconciledPoolUsageTotal - $pendingPoolUsageTotal,
            2,
        );
    }
}
