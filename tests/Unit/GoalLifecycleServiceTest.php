<?php

use App\Domain\Budgeting\Exceptions\GoalDeletionRequiresNetZeroBalance;
use App\Domain\Budgeting\Goals\GoalLifecycleService;
use App\Domain\Budgeting\Goals\GoalState;

it('recalculates goal progress immediately when goal amount is edited mid-cycle', function () {
    $service = new GoalLifecycleService;

    $baseline = $service->calculateProgress(
        goalAmount: 800,
        currentAllocatedAmount: 400,
        elapsedDays: 10,
        totalCycleDays: 28,
    );

    $afterEdit = $service->calculateProgress(
        goalAmount: 1_000,
        currentAllocatedAmount: 400,
        elapsedDays: 10,
        totalCycleDays: 28,
    );

    expect($baseline['consumed_percentage'])->toBe(50.0)
        ->and($baseline['current_burn_rate'])->toBe(40.0)
        ->and($baseline['burn_rate'])->toBe(140.0)
        ->and($afterEdit['goal_amount'])->toBe(1_000)
        ->and($afterEdit['allocated_amount'])->toBe(400)
        ->and($afterEdit['remaining_amount'])->toBe(600)
        ->and($afterEdit['consumed_percentage'])->toBe(40.0)
        ->and($afterEdit['current_burn_rate'])->toBe(40.0)
        ->and($afterEdit['burn_rate'])->toBe(112.0);
});

it('returns null burn metrics when cycle day inputs are not provided', function () {
    $service = new GoalLifecycleService;

    $progress = $service->calculateProgress(
        goalAmount: 800,
        currentAllocatedAmount: 400,
    );

    expect($progress['current_burn_rate'])->toBeNull()
        ->and($progress['burn_rate'])->toBeNull();
});

it('blocks soft-delete when cumulative goal event balance is non-zero', function () {
    $service = new GoalLifecycleService;

    $service->softDeleteGoal(
        goal: [
            'goal_id' => 'goal_groceries',
            'state' => GoalState::ACTIVE->value,
            'target_amount' => 800,
        ],
        cumulativeGoalEventBalance: 25,
        deletedAt: new DateTimeImmutable('2026-03-15T10:00:00Z'),
    );
})->throws(GoalDeletionRequiresNetZeroBalance::class, 'net-zero');

it('soft-deletes net-zero goals while preserving existing goal data', function () {
    $service = new GoalLifecycleService;

    $goal = [
        'goal_id' => 'goal_groceries',
        'name' => 'Groceries',
        'state' => GoalState::ACTIVE->value,
        'target_amount' => 800,
    ];

    $deleted = $service->softDeleteGoal(
        goal: $goal,
        cumulativeGoalEventBalance: 0,
        deletedAt: new DateTimeImmutable('2026-03-15T10:00:00Z'),
    );

    expect($deleted['goal_id'])->toBe('goal_groceries')
        ->and($deleted['name'])->toBe('Groceries')
        ->and($deleted['target_amount'])->toBe(800)
        ->and($deleted['state'])->toBe(GoalState::SOFT_DELETED->value)
        ->and($deleted['deleted_at'])->toBe('2026-03-15T10:00:00+00:00');
});
