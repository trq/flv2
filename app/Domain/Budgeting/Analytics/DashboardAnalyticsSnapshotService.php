<?php

declare(strict_types=1);

namespace App\Domain\Budgeting\Analytics;

use Carbon\CarbonImmutable;

class DashboardAnalyticsSnapshotService
{
    /**
     * @return array{
     *   income_allocation: array{
     *     planned: array{expenses: int, savings: int, total: int},
     *     actual: array{expenses: int, savings: int, total: int},
     *     income_pool_balance: int
     *   },
     *   activity_timeline: array{
     *     points: array<int, array{date: string, amount: int}>
     *   },
     *   cycle_progress: array{
     *     day: int,
     *     total_days: int,
     *     percent: int
     *   }
     * }
     */
    public function snapshotForDate(CarbonImmutable $today): array
    {
        $cycleStart = $today->startOfMonth();
        $cycleEnd = $today->endOfMonth();
        $timelinePoints = $this->buildTimelinePoints($cycleStart, $today);

        $plannedExpenses = 1800;
        $plannedSavings = 1000;
        $plannedTotal = $plannedExpenses + $plannedSavings;

        $actualExpenses = array_sum(array_column($timelinePoints, 'amount'));
        $actualSavings = intdiv($plannedSavings * $this->cycleDay($today, $cycleStart), $this->totalDays($cycleStart, $cycleEnd));
        $actualTotal = $actualExpenses + $actualSavings;
        $incomePoolBalance = max(0, 6000 - $actualTotal);

        return [
            'income_allocation' => [
                'planned' => [
                    'expenses' => $plannedExpenses,
                    'savings' => $plannedSavings,
                    'total' => $plannedTotal,
                ],
                'actual' => [
                    'expenses' => $actualExpenses,
                    'savings' => $actualSavings,
                    'total' => $actualTotal,
                ],
                'income_pool_balance' => $incomePoolBalance,
            ],
            'activity_timeline' => [
                'points' => $timelinePoints,
            ],
            'cycle_progress' => [
                'day' => $this->cycleDay($today, $cycleStart),
                'total_days' => $this->totalDays($cycleStart, $cycleEnd),
                'percent' => intdiv(
                    $this->cycleDay($today, $cycleStart) * 100,
                    max(1, $this->totalDays($cycleStart, $cycleEnd)),
                ),
            ],
        ];
    }

    /**
     * @return array<int, array{date: string, amount: int}>
     */
    private function buildTimelinePoints(CarbonImmutable $cycleStart, CarbonImmutable $today): array
    {
        $windowStart = $today->subDays(9);
        $start = $windowStart->greaterThan($cycleStart) ? $windowStart : $cycleStart;
        $points = [];

        for ($cursor = $start; $cursor->lessThanOrEqualTo($today); $cursor = $cursor->addDay()) {
            $day = (int) $cursor->format('j');
            $amount = (($day * 37) % 140) + 30;

            if ($day % 2 === 0) {
                $amount += 20;
            }

            $points[] = [
                'date' => $cursor->toDateString(),
                'amount' => $amount,
            ];
        }

        return $points;
    }

    private function cycleDay(CarbonImmutable $today, CarbonImmutable $cycleStart): int
    {
        return (int) $cycleStart->diffInDays($today) + 1;
    }

    private function totalDays(CarbonImmutable $cycleStart, CarbonImmutable $cycleEnd): int
    {
        return (int) $cycleStart->diffInDays($cycleEnd) + 1;
    }
}
