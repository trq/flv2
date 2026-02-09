<?php

namespace App\Domain\Budgeting\Cycles;

use App\Domain\Budgeting\Exceptions\NonWholeDollarAmount;
use DateInterval;
use DateTimeImmutable;

class CycleRolloverOrchestrator
{
    /**
     * @param  array<int, array{event_id: string, goal_id: string, amount: int|float|string}>  $adjustmentSweepEvents
     */
    public function runConfirmedClose(
        string $budgetId,
        string $currentCycleId,
        string $nextCycleId,
        DateTimeImmutable $currentCycleStart,
        DateTimeImmutable $currentCycleEnd,
        string $nextCycleIncomeAdjustmentGoalId,
        string $rolloverEventId,
        int|float|string $rolloverAmount,
        array $adjustmentSweepEvents,
    ): CycleRolloverResult {
        $validatedRolloverAmount = $this->assertWholeDollarAmount(
            amount: $rolloverAmount,
            field: 'rolloverAmount',
        );

        $nextCycle = $this->nextCycleWindow($nextCycleId, $currentCycleStart, $currentCycleEnd);
        $generatedEvents = [];

        foreach ($adjustmentSweepEvents as $index => $event) {
            $generatedEvents[] = $this->buildGeneratedEvent(
                eventId: $event['event_id'],
                budgetId: $budgetId,
                cycleId: $currentCycleId,
                goalId: $event['goal_id'],
                amount: $this->assertWholeDollarAmount(
                    amount: $event['amount'],
                    field: sprintf('adjustmentSweepEvents[%d].amount', $index),
                ),
                source: CycleGeneratedEventSource::ADJUSTMENT_SWEEP,
            );
        }

        $generatedEvents[] = $this->buildGeneratedEvent(
            eventId: $rolloverEventId,
            budgetId: $budgetId,
            cycleId: $nextCycleId,
            goalId: $nextCycleIncomeAdjustmentGoalId,
            amount: $validatedRolloverAmount,
            source: CycleGeneratedEventSource::ROLLOVER_INCOME_ADJUSTMENT,
        );

        return new CycleRolloverResult(
            nextCycle: $nextCycle,
            closeSummary: [
                'current_cycle_id' => $currentCycleId,
                'next_cycle_id' => $nextCycleId,
                'rollover_amount' => $validatedRolloverAmount,
            ],
            generatedEvents: $generatedEvents,
        );
    }

    /**
     * @return array{
     *   cycle_id: string,
     *   start_date: string,
     *   end_date: string,
     *   state: string
     * }
     */
    private function nextCycleWindow(
        string $nextCycleId,
        DateTimeImmutable $currentCycleStart,
        DateTimeImmutable $currentCycleEnd,
    ): array {
        $cycleLengthDays = $currentCycleStart->diff($currentCycleEnd)->days + 1;

        $nextCycleStart = $currentCycleEnd->add(new DateInterval('P1D'));
        $nextCycleEnd = $nextCycleStart->add(new DateInterval(sprintf('P%dD', $cycleLengthDays - 1)));

        return [
            'cycle_id' => $nextCycleId,
            'start_date' => $nextCycleStart->format('Y-m-d'),
            'end_date' => $nextCycleEnd->format('Y-m-d'),
            'state' => CycleState::OPEN->value,
        ];
    }

    /**
     * @return array{
     *   event_id: string,
     *   budget_id: string,
     *   cycle_id: string,
     *   goal_id: string,
     *   amount: int,
     *   source: string,
     *   append_only: bool,
     *   metadata: array{
     *     actor_type: string,
     *     actor_id: string,
     *     source: string
     *   }
     * }
     */
    private function buildGeneratedEvent(
        string $eventId,
        string $budgetId,
        string $cycleId,
        string $goalId,
        int $amount,
        CycleGeneratedEventSource $source,
    ): array {
        return [
            'event_id' => $eventId,
            'budget_id' => $budgetId,
            'cycle_id' => $cycleId,
            'goal_id' => $goalId,
            'amount' => $amount,
            'source' => $source->value,
            'append_only' => true,
            'metadata' => [
                'actor_type' => 'system',
                'actor_id' => 'cycle_close_orchestrator',
                'source' => CycleGeneratedEventMetadataSource::CYCLE_CLOSE_CONFIRMATION->value,
            ],
        ];
    }

    private function assertWholeDollarAmount(int|float|string $amount, string $field): int
    {
        if (! is_int($amount)) {
            throw NonWholeDollarAmount::forField($field, $amount);
        }

        return $amount;
    }
}
