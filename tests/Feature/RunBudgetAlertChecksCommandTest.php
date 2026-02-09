<?php

declare(strict_types=1);

use App\Models\Alert;
use App\Models\AlertRule;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('creates alerts for active rules and remains idempotent for the same check window', function () {
    CarbonImmutable::setTestNow('2026-02-10 10:00:00');
    $budgetId = 'budget_alert_checks_a_'.Str::uuid();

    AlertRule::query()->create([
        'budget_id' => $budgetId,
        'goal_id' => 'goal_groceries',
        'rule_type' => 'overspend_risk',
        'threshold_percent' => 80,
        'is_active' => true,
        'context' => [
            'goal_name' => 'Groceries',
            'cap_amount' => 800,
            'spent_amount' => 700,
        ],
    ]);

    AlertRule::query()->create([
        'budget_id' => $budgetId,
        'goal_id' => 'goal_income_main',
        'rule_type' => 'missed_income',
        'threshold_percent' => 100,
        'is_active' => true,
        'context' => [
            'goal_name' => 'Monthly Pay',
            'expected_amount' => 3500,
            'received_amount' => 0,
            'expected_at' => '2026-02-10',
        ],
    ]);

    AlertRule::query()->create([
        'budget_id' => $budgetId,
        'goal_id' => 'goal_savings_emergency',
        'rule_type' => 'savings_drift',
        'threshold_percent' => 100,
        'is_active' => true,
        'context' => [
            'goal_name' => 'Emergency Savings',
            'target_amount' => 1000,
            'saved_amount' => 600,
        ],
    ]);

    $this->artisan('budget:check-alerts --window-hours=24')->assertExitCode(0);

    expect(Alert::query()->where('budget_id', $budgetId)->count())->toBe(3);

    $this->artisan('budget:check-alerts --window-hours=24')->assertExitCode(0);

    expect(Alert::query()->where('budget_id', $budgetId)->count())->toBe(3);

    CarbonImmutable::setTestNow();
});

it('stores alert context payload for UI follow-up actions', function () {
    CarbonImmutable::setTestNow('2026-02-10 10:00:00');
    $budgetId = 'budget_alert_checks_b_'.Str::uuid();

    AlertRule::query()->create([
        'budget_id' => $budgetId,
        'goal_id' => 'goal_misc',
        'rule_type' => 'overspend_risk',
        'threshold_percent' => 70,
        'is_active' => true,
        'context' => [
            'goal_name' => 'Miscellaneous',
            'cap_amount' => 500,
            'spent_amount' => 420,
        ],
    ]);

    $this->artisan('budget:check-alerts --window-hours=24')->assertExitCode(0);

    $alert = Alert::query()
        ->where('budget_id', $budgetId)
        ->where('goal_id', 'goal_misc')
        ->first();

    expect($alert)->not->toBeNull();

    expect($alert->context)->toMatchArray([
        'goal_name' => 'Miscellaneous',
        'rule_type' => 'overspend_risk',
        'reason' => 'Spend is above configured threshold for this goal.',
        'next_step' => 'Review recent allocations and adjust the goal cap or spending plan.',
    ]);

    CarbonImmutable::setTestNow();
});

it('registers budget checks in the schedule', function () {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $event = collect($schedule->events())->first(function ($scheduledEvent): bool {
        return str_contains((string) $scheduledEvent->command, 'budget:check-alerts');
    });

    expect($event)->not->toBeNull();
});
