<?php

use App\Domain\Budgeting\Goals\SavingsTargetPlanningService;

it('generates deterministic parent-child plan with even distribution across remaining cycles', function () {
    $service = new SavingsTargetPlanningService;

    $plan = $service->generateInitialChildPlan(
        parentGoalId: 'goal_target_parent_001',
        totalTargetAmount: 5_000,
        cycleIds: ['cycle_2026_03', 'cycle_2026_04', 'cycle_2026_05', 'cycle_2026_06'],
    );

    expect($plan)->toHaveCount(4)
        ->and(collect($plan)->pluck('parent_goal_id')->unique()->all())->toBe(['goal_target_parent_001'])
        ->and(collect($plan)->pluck('cycle_id')->all())->toBe([
            'cycle_2026_03',
            'cycle_2026_04',
            'cycle_2026_05',
            'cycle_2026_06',
        ])
        ->and(collect($plan)->pluck('target_amount')->all())->toBe([1250, 1250, 1250, 1250]);
});

it('distributes uneven totals by allocating remainder to earliest future cycles', function () {
    $service = new SavingsTargetPlanningService;

    $plan = $service->generateInitialChildPlan(
        parentGoalId: 'goal_target_parent_001',
        totalTargetAmount: 5_002,
        cycleIds: ['cycle_2026_03', 'cycle_2026_04', 'cycle_2026_05', 'cycle_2026_06'],
    );

    expect(collect($plan)->pluck('target_amount')->all())->toBe([1251, 1251, 1250, 1250]);
});

it('realigns only future child goals at cycle close and preserves closed cycle history', function () {
    $service = new SavingsTargetPlanningService;

    $existingChildren = [
        ['child_goal_id' => 'child_01', 'cycle_id' => 'cycle_2026_03', 'target_amount' => 1250, 'is_closed_cycle' => true],
        ['child_goal_id' => 'child_02', 'cycle_id' => 'cycle_2026_04', 'target_amount' => 1250, 'is_closed_cycle' => false],
        ['child_goal_id' => 'child_03', 'cycle_id' => 'cycle_2026_05', 'target_amount' => 1250, 'is_closed_cycle' => false],
        ['child_goal_id' => 'child_04', 'cycle_id' => 'cycle_2026_06', 'target_amount' => 1250, 'is_closed_cycle' => false],
    ];

    $realigned = $service->realignFutureChildPlanAtClose(
        totalTargetAmount: 5_000,
        amountAlreadySaved: 2_000,
        existingChildren: $existingChildren,
    );

    expect($realigned)->toBe([
        ['child_goal_id' => 'child_01', 'cycle_id' => 'cycle_2026_03', 'target_amount' => 1250, 'is_closed_cycle' => true],
        ['child_goal_id' => 'child_02', 'cycle_id' => 'cycle_2026_04', 'target_amount' => 1000, 'is_closed_cycle' => false],
        ['child_goal_id' => 'child_03', 'cycle_id' => 'cycle_2026_05', 'target_amount' => 1000, 'is_closed_cycle' => false],
        ['child_goal_id' => 'child_04', 'cycle_id' => 'cycle_2026_06', 'target_amount' => 1000, 'is_closed_cycle' => false],
    ]);
});

it('does not regenerate child goals mid-cycle', function () {
    $service = new SavingsTargetPlanningService;

    $existingChildren = [
        ['child_goal_id' => 'child_01', 'cycle_id' => 'cycle_2026_03', 'target_amount' => 1250, 'is_closed_cycle' => false],
        ['child_goal_id' => 'child_02', 'cycle_id' => 'cycle_2026_04', 'target_amount' => 1250, 'is_closed_cycle' => false],
    ];

    $unchanged = $service->preservePlanDuringOpenCycle($existingChildren);

    expect($unchanged)->toBe($existingChildren);
});
