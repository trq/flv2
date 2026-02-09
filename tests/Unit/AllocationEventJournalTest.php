<?php

use App\Domain\Budgeting\Exceptions\AllocationEventMutationNotAllowed;
use App\Domain\Budgeting\Ledger\AllocationEventJournal;

it('blocks event updates at the application layer', function () {
    $journal = new AllocationEventJournal;

    $journal->recordEvent(
        eventId: 'evt_income_001',
        goalId: 'goal_income',
        cycleId: 'cycle_2026_02',
        amount: 2_000.00,
    );

    $journal->updateEvent(
        eventId: 'evt_income_001',
        amount: 1_900.00,
    );
})->throws(AllocationEventMutationNotAllowed::class, 'append-only');

it('blocks event deletions at the application layer', function () {
    $journal = new AllocationEventJournal;

    $journal->recordEvent(
        eventId: 'evt_expense_001',
        goalId: 'goal_groceries',
        cycleId: 'cycle_2026_02',
        amount: 50.00,
    );

    $journal->deleteEvent(eventId: 'evt_expense_001');
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

    expect($compensatingEvent['amount'])->toBe(-125.50)
        ->and($compensatingEvent['compensates_event_id'])->toBe('evt_expense_001')
        ->and($journal->goalBalance('goal_groceries'))->toBe(0.0);
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

    $reconstructedBalances = $journal->reconstructGoalBalances();

    expect($reconstructedBalances)->toBe([
        'goal_groceries' => 0.0,
        'goal_income' => 2_000.0,
        'goal_savings' => 500.0,
    ]);

    expect($journal->history())->toHaveCount(4);
});
