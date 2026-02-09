<?php

use App\Domain\Budgeting\Exceptions\SavingsGoalRequiresSavingsPool;
use App\Domain\Budgeting\Goals\GoalType;
use App\Domain\Budgeting\Goals\SavingsGoalPoolLinkageGuard;

it('requires savings pool linkage for savings recurring goals', function () {
    $guard = new SavingsGoalPoolLinkageGuard;

    $guard->assertSavingsGoalHasPool(
        goalType: GoalType::SAVINGS_RECURRING,
        savingsPoolId: null,
    );
})->throws(SavingsGoalRequiresSavingsPool::class);

it('requires savings pool linkage for savings target parent goals', function () {
    $guard = new SavingsGoalPoolLinkageGuard;

    $guard->assertSavingsGoalHasPool(
        goalType: GoalType::SAVINGS_TARGET_PARENT,
        savingsPoolId: null,
    );
})->throws(SavingsGoalRequiresSavingsPool::class);

it('requires savings pool linkage for savings target child goals', function () {
    $guard = new SavingsGoalPoolLinkageGuard;

    $guard->assertSavingsGoalHasPool(
        goalType: GoalType::SAVINGS_TARGET_CHILD,
        savingsPoolId: null,
    );
})->throws(SavingsGoalRequiresSavingsPool::class);

it('allows savings goals when savings pool linkage exists', function () {
    $guard = new SavingsGoalPoolLinkageGuard;

    $guard->assertSavingsGoalHasPool(
        goalType: GoalType::SAVINGS_RECURRING,
        savingsPoolId: 'savings_pool_main',
    );

    expect(true)->toBeTrue();
});

it('does not require savings pool linkage for non-savings goal types', function () {
    $guard = new SavingsGoalPoolLinkageGuard;

    $guard->assertSavingsGoalHasPool(
        goalType: GoalType::EXPENSE,
        savingsPoolId: null,
    );

    expect(true)->toBeTrue();
});
