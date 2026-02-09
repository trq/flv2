<?php

use App\Domain\Budgeting\Cycles\CycleRolloverOrchestrator;

it('creates the next cycle with expected boundaries', function () {
    $orchestrator = new CycleRolloverOrchestrator;

    $result = $orchestrator->runConfirmedClose(
        budgetId: 'budget_001',
        currentCycleId: 'cycle_2026_02',
        nextCycleId: 'cycle_2026_03',
        currentCycleStart: new DateTimeImmutable('2026-02-15'),
        currentCycleEnd: new DateTimeImmutable('2026-03-14'),
        nextCycleIncomeAdjustmentGoalId: 'goal_income_adjustment_cycle_2026_03',
        rolloverEventId: 'evt_rollover_2026_03_001',
        rolloverAmount: 340,
        adjustmentSweepEvents: [],
    );

    expect($result->toArray()['next_cycle'])->toBe([
        'cycle_id' => 'cycle_2026_03',
        'start_date' => '2026-03-15',
        'end_date' => '2026-04-11',
        'state' => 'open',
    ]);
});

it('makes rollover amount traceable from close summary to created income adjustment event', function () {
    $orchestrator = new CycleRolloverOrchestrator;

    $result = $orchestrator->runConfirmedClose(
        budgetId: 'budget_001',
        currentCycleId: 'cycle_2026_02',
        nextCycleId: 'cycle_2026_03',
        currentCycleStart: new DateTimeImmutable('2026-02-15'),
        currentCycleEnd: new DateTimeImmutable('2026-03-14'),
        nextCycleIncomeAdjustmentGoalId: 'goal_income_adjustment_cycle_2026_03',
        rolloverEventId: 'evt_rollover_2026_03_001',
        rolloverAmount: 525,
        adjustmentSweepEvents: [],
    );

    $payload = $result->toArray();

    $rolloverEvent = collect($payload['generated_events'])
        ->first(fn (array $event): bool => $event['source'] === 'rollover_income_adjustment');

    expect($payload['close_summary']['rollover_amount'])->toBe(525)
        ->and($rolloverEvent)->not->toBeNull()
        ->and($rolloverEvent['event_id'])->toBe('evt_rollover_2026_03_001')
        ->and($rolloverEvent['amount'])->toBe(525)
        ->and($rolloverEvent['cycle_id'])->toBe('cycle_2026_03')
        ->and($rolloverEvent['goal_id'])->toBe('goal_income_adjustment_cycle_2026_03');
});

it('marks system generated close events with actor and source metadata for append-only trail', function () {
    $orchestrator = new CycleRolloverOrchestrator;

    $result = $orchestrator->runConfirmedClose(
        budgetId: 'budget_001',
        currentCycleId: 'cycle_2026_02',
        nextCycleId: 'cycle_2026_03',
        currentCycleStart: new DateTimeImmutable('2026-02-15'),
        currentCycleEnd: new DateTimeImmutable('2026-03-14'),
        nextCycleIncomeAdjustmentGoalId: 'goal_income_adjustment_cycle_2026_03',
        rolloverEventId: 'evt_rollover_2026_03_001',
        rolloverAmount: 180,
        adjustmentSweepEvents: [
            ['event_id' => 'evt_close_adj_001', 'goal_id' => 'goal_groceries', 'amount' => -80],
            ['event_id' => 'evt_close_adj_002', 'goal_id' => 'goal_fuel', 'amount' => -100],
        ],
    );

    $events = $result->toArray()['generated_events'];

    expect($events)->toHaveCount(3);

    foreach ($events as $event) {
        expect($event['metadata'])->toBe([
            'actor_type' => 'system',
            'actor_id' => 'cycle_close_orchestrator',
            'source' => 'cycle_close_confirmation',
        ])->and($event['append_only'])->toBeTrue();
    }

    expect(collect($events)->pluck('event_id')->all())->toBe([
        'evt_close_adj_001',
        'evt_close_adj_002',
        'evt_rollover_2026_03_001',
    ]);
});
