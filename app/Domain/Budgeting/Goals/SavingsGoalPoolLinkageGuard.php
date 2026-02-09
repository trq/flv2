<?php

namespace App\Domain\Budgeting\Goals;

use App\Domain\Budgeting\Exceptions\SavingsGoalRequiresSavingsPool;

class SavingsGoalPoolLinkageGuard
{
    public function assertSavingsGoalHasPool(GoalType $goalType, ?string $savingsPoolId): void
    {
        if (! $goalType->requiresSavingsPool()) {
            return;
        }

        if ($savingsPoolId === null || $savingsPoolId === '') {
            throw SavingsGoalRequiresSavingsPool::forGoalType($goalType->value);
        }
    }
}
