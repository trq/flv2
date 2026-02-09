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

it('forecasts savings pool balance by date across multiple cycles', function () {
    $service = new SavingsPoolAccountingService;

    $forecast = $service->forecastSavingsPoolBalanceByDate(
        currentSavingsPoolBalance: 500,
        forecastDate: new DateTimeImmutable('2026-04-15'),
        plannedSavingsEvents: [
            ['event_date' => new DateTimeImmutable('2026-03-20'), 'amount' => 200],
            ['event_date' => new DateTimeImmutable('2026-04-14'), 'amount' => 150],
            ['event_date' => new DateTimeImmutable('2026-04-16'), 'amount' => -50],
        ],
    );

    expect($forecast)->toBe([
        'current_balance' => 500,
        'forecast_date' => '2026-04-15',
        'included_net_change' => 350,
        'included_event_count' => 2,
        'projected_balance' => 850,
    ]);
});
