<?php

namespace App\Domain\Budgeting\Goals;

use App\Domain\Budgeting\Exceptions\GoalDeletionRequiresNetZeroBalance;
use DateTimeImmutable;

class GoalLifecycleService
{
    /**
     * @return array{
     *   goal_amount: int,
     *   allocated_amount: int,
     *   remaining_amount: int,
     *   consumed_percentage: float,
     *   current_burn_rate: float|null,
     *   burn_rate: float|null
     * }
     */
    public function calculateProgress(
        int $goalAmount,
        int $currentAllocatedAmount,
        ?int $elapsedDays = null,
        ?int $totalCycleDays = null,
    ): array {
        $consumedPercentage = $goalAmount === 0
            ? 0.0
            : round(($currentAllocatedAmount / $goalAmount) * 100, 2);
        $currentBurnRate = null;
        $burnRate = null;

        if ($elapsedDays !== null && $totalCycleDays !== null) {
            $safeElapsedDays = max(1, $elapsedDays);
            $safeTotalCycleDays = max(1, $totalCycleDays);

            $currentBurnRate = round($currentAllocatedAmount / $safeElapsedDays, 2);
            $budgetedDailyRate = $goalAmount / $safeTotalCycleDays;
            $burnRate = $budgetedDailyRate === 0.0
                ? 0.0
                : round(($currentBurnRate / $budgetedDailyRate) * 100, 2);
        }

        return [
            'goal_amount' => $goalAmount,
            'allocated_amount' => $currentAllocatedAmount,
            'remaining_amount' => $goalAmount - $currentAllocatedAmount,
            'consumed_percentage' => $consumedPercentage,
            'current_burn_rate' => $currentBurnRate,
            'burn_rate' => $burnRate,
        ];
    }

    /**
     * @param  array<string, mixed>  $goal
     * @return array<string, mixed>
     */
    public function softDeleteGoal(
        array $goal,
        int $cumulativeGoalEventBalance,
        DateTimeImmutable $deletedAt,
    ): array {
        if ($cumulativeGoalEventBalance !== 0) {
            throw GoalDeletionRequiresNetZeroBalance::forBalance($cumulativeGoalEventBalance);
        }

        $goal['state'] = GoalState::SOFT_DELETED->value;
        $goal['deleted_at'] = $deletedAt->format(DATE_ATOM);

        return $goal;
    }
}
