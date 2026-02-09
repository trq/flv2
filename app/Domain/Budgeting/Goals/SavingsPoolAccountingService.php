<?php

namespace App\Domain\Budgeting\Goals;

use App\Domain\Budgeting\Exceptions\InsufficientSavingsPoolFunds;
use DateTimeImmutable;

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

    /**
     * @param  array<int, array{
     *   event_date: DateTimeImmutable,
     *   amount: int
     * }>  $plannedSavingsEvents
     * @return array{
     *   current_balance: int,
     *   forecast_date: string,
     *   included_net_change: int,
     *   included_event_count: int,
     *   projected_balance: int
     * }
     */
    public function forecastSavingsPoolBalanceByDate(
        int $currentSavingsPoolBalance,
        DateTimeImmutable $forecastDate,
        array $plannedSavingsEvents,
    ): array {
        $includedEvents = collect($plannedSavingsEvents)
            ->filter(fn (array $event): bool => $event['event_date'] <= $forecastDate)
            ->values();
        $includedNetChange = $includedEvents->sum('amount');

        return [
            'current_balance' => $currentSavingsPoolBalance,
            'forecast_date' => $forecastDate->format('Y-m-d'),
            'included_net_change' => $includedNetChange,
            'included_event_count' => $includedEvents->count(),
            'projected_balance' => $currentSavingsPoolBalance + $includedNetChange,
        ];
    }
}
