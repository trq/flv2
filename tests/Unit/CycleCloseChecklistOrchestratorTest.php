<?php

use App\Domain\Budgeting\Cycles\CycleCloseChecklistOrchestrator;

it('fails fast when unresolved pending events exist', function () {
    $orchestrator = new CycleCloseChecklistOrchestrator;

    $result = $orchestrator->run(
        pendingEventCount: 2,
        overGoalCount: 1,
        underGoalCount: 3,
    );

    expect($result->toArray())->toBe([
        'status' => 'blocked',
        'can_close' => false,
        'blocker' => [
            'code' => 'pending_events',
            'message' => 'Cycle close is blocked until all pending events are resolved.',
            'pending_event_count' => 2,
        ],
        'steps' => [
            [
                'id' => 'resolve_pending_events',
                'status' => 'blocked',
                'pending_event_count' => 2,
            ],
        ],
        'review' => null,
    ]);
});

it('executes checklist steps in deterministic order when pending events are resolved', function () {
    $orchestrator = new CycleCloseChecklistOrchestrator;

    $result = $orchestrator->run(
        pendingEventCount: 0,
        overGoalCount: 2,
        underGoalCount: 1,
    );

    $steps = collect($result->toArray()['steps']);

    expect($steps->pluck('id')->all())->toBe([
        'resolve_pending_events',
        'review_goal_outcomes',
        'confirm_rollover_plan',
    ])->and($steps->pluck('status')->all())->toBe([
        'passed',
        'completed',
        'awaiting_confirmation',
    ]);
});

it('emits a structured payload suitable for chat confirmation cards', function () {
    $orchestrator = new CycleCloseChecklistOrchestrator;

    $payload = $orchestrator->run(
        pendingEventCount: 0,
        overGoalCount: 4,
        underGoalCount: 6,
    )->toArray();

    expect($payload)->toHaveKeys([
        'status',
        'can_close',
        'blocker',
        'steps',
        'review',
    ])->and($payload['status'])->toBe('ready_for_confirmation')
        ->and($payload['can_close'])->toBeTrue()
        ->and($payload['blocker'])->toBeNull()
        ->and($payload['review'])->toBe([
            'over_goal_count' => 4,
            'under_goal_count' => 6,
        ]);
});
