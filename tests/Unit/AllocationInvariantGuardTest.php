<?php

declare(strict_types=1);

use App\Domain\Budgeting\Exceptions\ExpenseGoalCapExceeded;
use App\Domain\Budgeting\Exceptions\InsufficientPoolFunds;
use App\Domain\Budgeting\Ledger\AllocationInvariantGuard;

it('rejects allocation attempts that exceed available funded pool', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertSufficientPoolFunding(
        availablePoolAmount: 50,
        allocationAmount: 75,
        consumesPool: true,
    );
})->throws(InsufficientPoolFunds::class, 'available pool of $50');

it('allows allocations that do not consume pool funding', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertSufficientPoolFunding(
        availablePoolAmount: 0,
        allocationAmount: 500,
        consumesPool: false,
    );

    expect(true)->toBeTrue();
});

it('rejects expense allocations that exceed hard goal cap', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertExpenseGoalCapacity(
        goalAmount: 800,
        alreadyAllocatedAmount: 790,
        allocationAmount: 20,
    );
})->throws(ExpenseGoalCapExceeded::class, 'hard cap of $800');

it('allows negative expense allocations for balance corrections', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertExpenseGoalCapacity(
        goalAmount: 800,
        alreadyAllocatedAmount: 790,
        allocationAmount: -20,
    );

    expect(true)->toBeTrue();
});

it('includes pending allocations when calculating available pool', function () {
    $guard = new AllocationInvariantGuard;

    $availablePool = $guard->calculateAvailablePool(
        incomeTotal: 2_000,
        reconciledPoolUsageTotal: 1_250,
        pendingPoolUsageTotal: 300,
    );

    expect($availablePool)->toBe(450);
});

it('rejects non-integer allocation amounts for whole-dollar policy', function () {
    $guard = new AllocationInvariantGuard;

    $guard->assertSufficientPoolFunding(
        availablePoolAmount: 500,
        allocationAmount: 75.25,
        consumesPool: true,
    );
})->throws(TypeError::class);

it('rejects non-integer pool components when calculating availability', function () {
    $guard = new AllocationInvariantGuard;

    $guard->calculateAvailablePool(
        incomeTotal: 2_000,
        reconciledPoolUsageTotal: 1_250.50,
        pendingPoolUsageTotal: 300,
    );
})->throws(TypeError::class);
