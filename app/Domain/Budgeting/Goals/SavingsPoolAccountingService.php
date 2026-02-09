<?php

namespace App\Domain\Budgeting\Goals;

use App\Domain\Budgeting\Exceptions\InsufficientSavingsPoolFunds;

class SavingsPoolAccountingService
{
    /**
     * @return array{
     *   income_pool_balance: int,
     *   savings_pool_balance: int
     * }
     */
    public function applySavingsEvent(
        int $incomePoolBalance,
        int $savingsPoolBalance,
        int $savingsEventAmount,
    ): array {
        if ($savingsEventAmount < 0) {
            $withdrawalAmount = abs($savingsEventAmount);

            if ($withdrawalAmount > $savingsPoolBalance) {
                throw InsufficientSavingsPoolFunds::forWithdrawal(
                    savingsPoolBalance: $savingsPoolBalance,
                    withdrawalAmount: $withdrawalAmount,
                );
            }
        }

        return [
            'income_pool_balance' => $incomePoolBalance - $savingsEventAmount,
            'savings_pool_balance' => $savingsPoolBalance + $savingsEventAmount,
        ];
    }

    /**
     * @param  array<int, int>  $plannedSavingsEvents
     * @return array{
     *   current_balance: int,
     *   projected_balance: int,
     *   planned_net_change: int
     * }
     */
    public function projectSavingsPoolBalance(int $currentSavingsPoolBalance, array $plannedSavingsEvents): array
    {
        $plannedNetChange = array_sum($plannedSavingsEvents);

        return [
            'current_balance' => $currentSavingsPoolBalance,
            'projected_balance' => $currentSavingsPoolBalance + $plannedNetChange,
            'planned_net_change' => $plannedNetChange,
        ];
    }
}
