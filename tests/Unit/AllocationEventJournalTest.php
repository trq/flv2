<?php

use App\Domain\Budgeting\Exceptions\AllocationEventMutationNotAllowed;
use App\Domain\Budgeting\Ledger\AllocationEventJournal;
use App\Domain\Budgeting\Ledger\AllocationEventMutationGuard;

it('blocks event updates at the application layer', function () {
    $guard = new AllocationEventMutationGuard;

    $guard->assertAppendOnly('update');
})->throws(AllocationEventMutationNotAllowed::class, 'append-only');

it('blocks event deletions at the application layer', function () {
    $guard = new AllocationEventMutationGuard;

    $guard->assertAppendOnly('delete');
})->throws(AllocationEventMutationNotAllowed::class, 'append-only');

it('creates compensating events that offset prior balances', function () {
    $journal = new AllocationEventJournal;

    $journal->recordEvent(
        eventId: 'evt_expense_001',
        goalId: 'goal_groceries',
        cycleId: 'cycle_2026_02',
        amount: 125.50,
    );

    $compensatingEvent = $journal->recordCompensatingEvent(
        newEventId: 'evt_expense_001_comp',
        originalEventId: 'evt_expense_001',
    );

    $goalBalances = collect($journal->history())
        ->groupBy('goal_id')
        ->map(fn ($events): float => round($events->sum('amount'), 2));

    expect($compensatingEvent['amount'])->toBe(-125.50)
        ->and($compensatingEvent['compensates_event_id'])->toBe('evt_expense_001')
        ->and($goalBalances['goal_groceries'])->toBe(0.0);
});

it('reconstructs balances deterministically from immutable event history', function () {
    $journal = new AllocationEventJournal;

    $journal->recordEvent(
        eventId: 'evt_income_001',
        goalId: 'goal_income',
        cycleId: 'cycle_2026_02',
        amount: 2_000.00,
    );

    $journal->recordEvent(
        eventId: 'evt_expense_001',
        goalId: 'goal_groceries',
        cycleId: 'cycle_2026_02',
        amount: 120.00,
    );

    $journal->recordEvent(
        eventId: 'evt_savings_001',
        goalId: 'goal_savings',
        cycleId: 'cycle_2026_02',
        amount: 500.00,
    );

    $journal->recordCompensatingEvent(
        newEventId: 'evt_expense_001_comp',
        originalEventId: 'evt_expense_001',
    );

    $reconstructedBalances = collect($journal->history())
        ->groupBy('goal_id')
        ->map(fn ($events): float => round($events->sum('amount'), 2))
        ->sortKeys()
        ->all();

    expect($reconstructedBalances)->toBe([
        'goal_groceries' => 0.0,
        'goal_income' => 2_000.0,
        'goal_savings' => 500.0,
    ]);

    expect($journal->history())->toHaveCount(4);
});
