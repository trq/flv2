<?php

namespace App\Domain\Budgeting\Goals;

class SavingsTargetPlanningService
{
    /**
     * @param  array<int, string>  $cycleIds
     * @return array<int, array{
     *   parent_goal_id: string,
     *   cycle_id: string,
     *   target_amount: int
     * }>
     */
    public function generateInitialChildPlan(string $parentGoalId, int $totalTargetAmount, array $cycleIds): array
    {
        return collect($this->distributedTargets($totalTargetAmount, count($cycleIds)))
            ->values()
            ->map(
                fn (int $targetAmount, int $index): array => [
                    'parent_goal_id' => $parentGoalId,
                    'cycle_id' => $cycleIds[$index],
                    'target_amount' => $targetAmount,
                ],
            )
            ->all();
    }

    /**
     * @param  array<int, array{
     *   child_goal_id: string,
     *   cycle_id: string,
     *   target_amount: int,
     *   is_closed_cycle: bool
     * }>  $existingChildren
     * @return array<int, array{
     *   child_goal_id: string,
     *   cycle_id: string,
     *   target_amount: int,
     *   is_closed_cycle: bool
     * }>
     */
    public function realignFutureChildPlanAtClose(
        int $totalTargetAmount,
        int $amountAlreadySaved,
        array $existingChildren,
    ): array {
        $remainingTargetAmount = max(0, $totalTargetAmount - $amountAlreadySaved);
        $futureChildren = collect($existingChildren)->filter(
            fn (array $child): bool => $child['is_closed_cycle'] === false,
        )->values();

        $redistributedTargets = $this->distributedTargets(
            totalAmount: $remainingTargetAmount,
            parts: $futureChildren->count(),
        );

        $futureIndex = 0;

        return collect($existingChildren)
            ->map(function (array $child) use (&$futureIndex, $redistributedTargets): array {
                if ($child['is_closed_cycle']) {
                    return $child;
                }

                $child['target_amount'] = $redistributedTargets[$futureIndex];
                $futureIndex++;

                return $child;
            })->all();
    }

    /**
     * @param  array<int, array{
     *   child_goal_id: string,
     *   cycle_id: string,
     *   target_amount: int,
     *   is_closed_cycle: bool
     * }>  $existingChildren
     * @return array<int, array{
     *   child_goal_id: string,
     *   cycle_id: string,
     *   target_amount: int,
     *   is_closed_cycle: bool
     * }>
     */
    public function preservePlanDuringOpenCycle(array $existingChildren): array
    {
        return $existingChildren;
    }

    /**
     * @return array<int, int>
     */
    private function distributedTargets(int $totalAmount, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }

        $baseAmount = intdiv($totalAmount, $parts);
        $remainder = $totalAmount % $parts;

        return collect(range(1, $parts))
            ->map(fn (int $position): int => $baseAmount + ($position <= $remainder ? 1 : 0))
            ->all();
    }
}
