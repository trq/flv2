<?php

use App\Domain\Budgeting\Exceptions\InsufficientSavingsPoolFunds;
use App\Domain\Budgeting\Goals\SavingsPoolAccountingService;

it('applies positive savings events by decreasing income pool and increasing savings pool', function () {
    $service = new SavingsPoolAccountingService;

    $balances = $service->applySavingsEvent(
        incomePoolBalance: 2_000,
        savingsPoolBalance: 500,
        savingsEventAmount: 200,
    );

    expect($balances)->toBe([
        'income_pool_balance' => 1_800,
        'savings_pool_balance' => 700,
    ]);
});

it('applies negative savings events by increasing income pool and decreasing savings pool', function () {
    $service = new SavingsPoolAccountingService;

    $balances = $service->applySavingsEvent(
        incomePoolBalance: 1_800,
        savingsPoolBalance: 700,
        savingsEventAmount: -150,
    );

    expect($balances)->toBe([
        'income_pool_balance' => 1_950,
        'savings_pool_balance' => 550,
    ]);
});

it('blocks negative savings events when savings pool would underflow', function () {
    $service = new SavingsPoolAccountingService;

    $service->applySavingsEvent(
        incomePoolBalance: 1_800,
        savingsPoolBalance: 100,
        savingsEventAmount: -150,
    );
})->throws(InsufficientSavingsPoolFunds::class, 'insufficient savings pool funds');

it('projects savings pool balances across cycles from current balance and planned events', function () {
    $service = new SavingsPoolAccountingService;

    $projection = $service->projectSavingsPoolBalance(
        currentSavingsPoolBalance: 500,
        plannedSavingsEvents: [200, 150, -100],
    );

    expect($projection)->toBe([
        'current_balance' => 500,
        'projected_balance' => 750,
        'planned_net_change' => 250,
    ]);
});
