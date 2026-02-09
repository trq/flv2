<?php

use App\Domain\Budgeting\Cycles\CyclePostingGuard;
use App\Domain\Budgeting\Cycles\CycleState;
use App\Domain\Budgeting\Cycles\CycleStateMachine;
use App\Domain\Budgeting\Exceptions\ClosedCycleReadOnly;
use App\Domain\Budgeting\Exceptions\InvalidCycleStateTransition;
use App\Domain\Budgeting\Exceptions\MultipleOpenCyclesNotAllowed;
use App\Domain\Budgeting\Exceptions\NonCurrentCyclePostingNotAllowed;

it('allows posting to the current open cycle', function () {
    $guard = new CyclePostingGuard;

    $guard->assertCanPostAllocation(
        cycleState: CycleState::OPEN,
        isCurrentCycle: true,
    );

    expect(true)->toBeTrue();
});

it('rejects posting allocations to closed cycles', function () {
    $guard = new CyclePostingGuard;

    $guard->assertCanPostAllocation(
        cycleState: CycleState::CLOSED,
        isCurrentCycle: true,
    );
})->throws(ClosedCycleReadOnly::class, 'closed');

it('rejects posting allocations to non-current cycles', function () {
    $guard = new CyclePostingGuard;

    $guard->assertCanPostAllocation(
        cycleState: CycleState::OPEN,
        isCurrentCycle: false,
    );
})->throws(NonCurrentCyclePostingNotAllowed::class, 'current');

it('opens a cycle when no other cycle is currently open for the budget', function () {
    $stateMachine = new CycleStateMachine;

    $newState = $stateMachine->openCycle(existingOpenCycleCount: 0);

    expect($newState)->toBe(CycleState::OPEN);
});

it('rejects opening a cycle when the budget already has an open cycle', function () {
    $stateMachine = new CycleStateMachine;

    $stateMachine->openCycle(existingOpenCycleCount: 1);
})->throws(MultipleOpenCyclesNotAllowed::class, 'already has 1 open cycle');

it('transitions an open cycle to closed', function () {
    $stateMachine = new CycleStateMachine;

    $nextState = $stateMachine->closeCycle(currentState: CycleState::OPEN);

    expect($nextState)->toBe(CycleState::CLOSED);
});

it('rejects invalid close transitions for non-open cycles', function () {
    $stateMachine = new CycleStateMachine;

    $stateMachine->closeCycle(currentState: CycleState::CLOSED);
})->throws(InvalidCycleStateTransition::class, 'cannot transition');
