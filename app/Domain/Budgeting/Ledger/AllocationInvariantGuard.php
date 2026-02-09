<?php

namespace App\Domain\Budgeting\Ledger;

use App\Domain\Budgeting\Exceptions\ExpenseGoalCapExceeded;
use App\Domain\Budgeting\Exceptions\InsufficientPoolFunds;
use App\Domain\Budgeting\Exceptions\NonWholeDollarAmount;

class AllocationInvariantGuard
{
    public function assertSufficientPoolFunding(
        int|float|string $availablePoolAmount,
        int|float|string $allocationAmount,
        bool $consumesPool = true,
    ): void {
        $validatedAvailablePoolAmount = $this->assertWholeDollarAmount(
            amount: $availablePoolAmount,
            field: 'availablePoolAmount',
        );
        $validatedAllocationAmount = $this->assertWholeDollarAmount(
            amount: $allocationAmount,
            field: 'allocationAmount',
        );

        if (! $consumesPool || $validatedAllocationAmount <= 0) {
            return;
        }

        if ($validatedAllocationAmount > $validatedAvailablePoolAmount) {
            throw InsufficientPoolFunds::forAllocation(
                availablePoolAmount: $validatedAvailablePoolAmount,
                requestedAllocationAmount: $validatedAllocationAmount,
            );
        }
    }

    public function assertExpenseGoalCapacity(
        int|float|string $goalAmount,
        int|float|string $alreadyAllocatedAmount,
        int|float|string $allocationAmount,
    ): void {
        $validatedGoalAmount = $this->assertWholeDollarAmount(
            amount: $goalAmount,
            field: 'goalAmount',
        );
        $validatedAlreadyAllocatedAmount = $this->assertWholeDollarAmount(
            amount: $alreadyAllocatedAmount,
            field: 'alreadyAllocatedAmount',
        );
        $validatedAllocationAmount = $this->assertWholeDollarAmount(
            amount: $allocationAmount,
            field: 'allocationAmount',
        );

        if ($validatedAllocationAmount <= 0) {
            return;
        }

        $newAllocatedAmount = $validatedAlreadyAllocatedAmount + $validatedAllocationAmount;

        if ($newAllocatedAmount > $validatedGoalAmount) {
            throw ExpenseGoalCapExceeded::forAllocation(
                goalAmount: $validatedGoalAmount,
                alreadyAllocatedAmount: $validatedAlreadyAllocatedAmount,
                requestedAllocationAmount: $validatedAllocationAmount,
            );
        }
    }

    public function calculateAvailablePool(
        int|float|string $incomeTotal,
        int|float|string $reconciledPoolUsageTotal,
        int|float|string $pendingPoolUsageTotal,
    ): int {
        $validatedIncomeTotal = $this->assertWholeDollarAmount(
            amount: $incomeTotal,
            field: 'incomeTotal',
        );
        $validatedReconciledPoolUsageTotal = $this->assertWholeDollarAmount(
            amount: $reconciledPoolUsageTotal,
            field: 'reconciledPoolUsageTotal',
        );
        $validatedPendingPoolUsageTotal = $this->assertWholeDollarAmount(
            amount: $pendingPoolUsageTotal,
            field: 'pendingPoolUsageTotal',
        );

        return $validatedIncomeTotal - $validatedReconciledPoolUsageTotal - $validatedPendingPoolUsageTotal;
    }

    private function assertWholeDollarAmount(int|float|string $amount, string $field): int
    {
        if (! is_int($amount)) {
            throw NonWholeDollarAmount::forField($field, $amount);
        }

        return $amount;
    }
}
