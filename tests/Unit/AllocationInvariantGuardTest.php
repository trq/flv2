<?php

use App\Domain\Budgeting\Exceptions\ExpenseGoalCapExceeded;
use App\Domain\Budgeting\Exceptions\InsufficientPoolFunds;
use App\Domain\Budgeting\Ledger\AllocationInvariantGuard;

it('rejects allocation attempts that exceed available funded pool', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertSufficientPoolFunding(
        availablePoolAmount: 50.00,
        allocationAmount: 75.00,
        consumesPool: true,
    );
})->throws(InsufficientPoolFunds::class, 'available pool of $50.00');

it('allows allocations that do not consume pool funding', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertSufficientPoolFunding(
        availablePoolAmount: 0.00,
        allocationAmount: 500.00,
        consumesPool: false,
    );

    expect(true)->toBeTrue();
});

it('rejects expense allocations that exceed hard goal cap', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertExpenseGoalCapacity(
        goalAmount: 800.00,
        alreadyAllocatedAmount: 790.00,
        allocationAmount: 20.00,
    );
})->throws(ExpenseGoalCapExceeded::class, 'hard cap of $800.00');

it('allows negative expense allocations for balance corrections', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertExpenseGoalCapacity(
        goalAmount: 800.00,
        alreadyAllocatedAmount: 790.00,
        allocationAmount: -20.00,
    );

    expect(true)->toBeTrue();
});

it('includes pending allocations when calculating available pool', function () {
    $guard = new AllocationInvariantGuard;

    $availablePool = $guard->calculateAvailablePool(
        incomeTotal: 2_000.00,
        reconciledPoolUsageTotal: 1_250.00,
        pendingPoolUsageTotal: 300.00,
    );

    expect($availablePool)->toBe(450.00);
});
