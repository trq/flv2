<?php

declare(strict_types=1);

use App\Domain\Budgeting\Assistant\AssistantWriteDecision;
use App\Domain\Budgeting\Assistant\AssistantWriteExecutionMode;
use App\Domain\Budgeting\Assistant\AssistantWriteOrchestrator;
use App\Domain\Budgeting\Assistant\AssistantWritePolicy;
use App\Domain\Budgeting\Assistant\AssistantWriteProposal;
use App\Models\AlertRule;
use App\Models\User;
use App\Notifications\BudgetAlertTriggeredNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('dispatches in-app notifications for background alerts with deep-link metadata', function () {
    CarbonImmutable::setTestNow('2026-02-11 08:00:00');

    $user = User::factory()->create();
    Notification::fake();

    AlertRule::query()->create([
        'user_id' => (string) $user->getKey(),
        'budget_id' => 'budget_'.Str::uuid(),
        'goal_id' => 'goal_groceries',
        'cycle_id' => 'cycle_2026_02',
        'rule_type' => 'overspend_risk',
        'threshold_percent' => 80,
        'is_active' => true,
        'context' => [
            'goal_name' => 'Groceries',
            'cap_amount' => 800,
            'spent_amount' => 760,
        ],
    ]);

    $this->artisan('budget:check-alerts --window-hours=24')->assertExitCode(0);

    Notification::assertSentTo(
        $user,
        BudgetAlertTriggeredNotification::class,
        function (BudgetAlertTriggeredNotification $notification, array $channels, User $notifiable): bool {
            $payload = $notification->toArray($notifiable);

            expect($channels)->toContain('database')
                ->and($channels)->toContain('broadcast');

            expect($payload)->toMatchArray([
                'type' => 'budget_alert',
                'goal_id' => 'goal_groceries',
                'cycle_id' => 'cycle_2026_02',
            ])->and($payload['deep_link'])->toMatchArray([
                'route' => 'dashboard',
                'params' => [
                    'cycle_id' => 'cycle_2026_02',
                    'goal_id' => 'goal_groceries',
                ],
            ]);

            return true;
        }
    );

    Notification::assertCount(1);

    CarbonImmutable::setTestNow();
});

it('does not emit duplicate notifications when the same check window reruns', function () {
    CarbonImmutable::setTestNow('2026-02-11 08:00:00');

    $user = User::factory()->create();
    Notification::fake();

    AlertRule::query()->create([
        'user_id' => (string) $user->getKey(),
        'budget_id' => 'budget_'.Str::uuid(),
        'goal_id' => 'goal_savings',
        'cycle_id' => 'cycle_2026_02',
        'rule_type' => 'savings_drift',
        'threshold_percent' => 100,
        'is_active' => true,
        'context' => [
            'goal_name' => 'Emergency Savings',
            'target_amount' => 1000,
            'saved_amount' => 500,
        ],
    ]);

    $this->artisan('budget:check-alerts --window-hours=24')->assertExitCode(0);
    $this->artisan('budget:check-alerts --window-hours=24')->assertExitCode(0);

    Notification::assertSentToTimes($user, BudgetAlertTriggeredNotification::class, 1);

    CarbonImmutable::setTestNow();
});

it('keeps chat write actions free of background alert notifications', function () {
    Notification::fake();

    $orchestrator = new AssistantWriteOrchestrator;

    $orchestrator->run(
        proposal: new AssistantWriteProposal(
            proposalId: 'proposal_100',
            actionSummary: 'Create allocation event',
            confidence: 0.95,
            entities: [
                [
                    'entity_type' => 'allocation_event',
                    'entity_id' => 'event_100',
                    'before' => [],
                    'after' => ['amount' => 40],
                ],
            ],
        ),
        policy: new AssistantWritePolicy(AssistantWriteExecutionMode::CONFIRMATION_ONLY),
        decision: AssistantWriteDecision::APPROVE,
        executeWrite: fn (): bool => true,
    );

    Notification::assertNothingSent();
});
