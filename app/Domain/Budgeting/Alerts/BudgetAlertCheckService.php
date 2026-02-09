<?php

declare(strict_types=1);

namespace App\Domain\Budgeting\Alerts;

use App\Models\Alert;
use App\Models\AlertRule;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Collection;

class BudgetAlertCheckService
{
    /**
     * @return Collection<int, Alert>
     */
    public function runWindow(CarbonImmutable $windowStart, CarbonImmutable $windowEnd): Collection
    {
        /** @var Collection<int, Alert> $createdAlerts */
        $createdAlerts = collect();

        /** @var \Illuminate\Support\Collection<int, AlertRule> $rules */
        $rules = AlertRule::query()
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $evaluation = $this->evaluateRule($rule, $windowEnd);

            if ($evaluation === null) {
                continue;
            }

            $alert = Alert::query()->firstOrCreate(
                [
                    'dedupe_key' => $this->dedupeKey($rule, $windowStart, $windowEnd),
                ],
                [
                    'user_id' => $rule->getAttribute('user_id'),
                    'budget_id' => (string) $rule->getAttribute('budget_id'),
                    'cycle_id' => $rule->getAttribute('cycle_id'),
                    'goal_id' => $rule->getAttribute('goal_id'),
                    'rule_type' => (string) $rule->getAttribute('rule_type'),
                    'severity' => $evaluation['severity'],
                    'status' => 'open',
                    'window_start' => $windowStart,
                    'window_end' => $windowEnd,
                    'context' => $evaluation['context'],
                    'resolved_at' => null,
                ],
            );

            if ($alert->wasRecentlyCreated) {
                $createdAlerts->push($alert);
            }
        }

        return $createdAlerts;
    }

    /**
     * @return array{
     *   severity: string,
     *   context: array<string, int|string>
     * }|null
     */
    private function evaluateRule(AlertRule $rule, CarbonImmutable $windowEnd): ?array
    {
        return match ((string) $rule->getAttribute('rule_type')) {
            'overspend_risk' => $this->evaluateOverspendRisk($rule),
            'missed_income' => $this->evaluateMissedIncome($rule, $windowEnd),
            'savings_drift' => $this->evaluateSavingsDrift($rule),
            default => null,
        };
    }

    /**
     * @return array{
     *   severity: string,
     *   context: array<string, int|string>
     * }|null
     */
    private function evaluateOverspendRisk(AlertRule $rule): ?array
    {
        $context = $this->context($rule);
        $capAmount = (int) data_get($context, 'cap_amount', 0);
        $spentAmount = (int) data_get($context, 'spent_amount', 0);
        $thresholdPercent = (int) $rule->getAttribute('threshold_percent');

        if ($capAmount <= 0 || $spentAmount * 100 < $capAmount * $thresholdPercent) {
            return null;
        }

        return [
            'severity' => $thresholdPercent >= 90 ? 'critical' : 'warning',
            'context' => [
                'goal_name' => (string) data_get($context, 'goal_name', 'Goal'),
                'rule_type' => 'overspend_risk',
                'reason' => 'Spend is above configured threshold for this goal.',
                'next_step' => 'Review recent allocations and adjust the goal cap or spending plan.',
                'spent_amount' => $spentAmount,
                'cap_amount' => $capAmount,
            ],
        ];
    }

    /**
     * @return array{
     *   severity: string,
     *   context: array<string, int|string>
     * }|null
     */
    private function evaluateMissedIncome(AlertRule $rule, CarbonImmutable $windowEnd): ?array
    {
        $context = $this->context($rule);
        $expectedAmount = (int) data_get($context, 'expected_amount', 0);
        $receivedAmount = (int) data_get($context, 'received_amount', 0);
        $expectedAt = $this->parseExpectedAt(data_get($context, 'expected_at'));

        if ($expectedAmount <= 0 || $expectedAt === null) {
            return null;
        }

        if ($expectedAt->greaterThan($windowEnd) || $receivedAmount >= $expectedAmount) {
            return null;
        }

        return [
            'severity' => 'critical',
            'context' => [
                'goal_name' => (string) data_get($context, 'goal_name', 'Income Goal'),
                'rule_type' => 'missed_income',
                'reason' => 'Expected income has not been fully received for this window.',
                'next_step' => 'Record received income or lower this cycle targets before closing.',
                'expected_amount' => $expectedAmount,
                'received_amount' => $receivedAmount,
                'shortfall_amount' => $expectedAmount - $receivedAmount,
            ],
        ];
    }

    /**
     * @return array{
     *   severity: string,
     *   context: array<string, int|string>
     * }|null
     */
    private function evaluateSavingsDrift(AlertRule $rule): ?array
    {
        $context = $this->context($rule);
        $targetAmount = (int) data_get($context, 'target_amount', 0);
        $savedAmount = (int) data_get($context, 'saved_amount', 0);

        if ($targetAmount <= 0 || $savedAmount >= $targetAmount) {
            return null;
        }

        return [
            'severity' => 'warning',
            'context' => [
                'goal_name' => (string) data_get($context, 'goal_name', 'Savings Goal'),
                'rule_type' => 'savings_drift',
                'reason' => 'Savings contribution is behind target for this window.',
                'next_step' => 'Increase savings allocation or adjust remaining cycle plan.',
                'target_amount' => $targetAmount,
                'saved_amount' => $savedAmount,
                'drift_amount' => $targetAmount - $savedAmount,
            ],
        ];
    }

    private function parseExpectedAt(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    private function dedupeKey(AlertRule $rule, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): string
    {
        $parts = [
            (string) $rule->getKey(),
            (string) $rule->getAttribute('rule_type'),
            $windowStart->toIso8601String(),
            $windowEnd->toIso8601String(),
        ];

        return sha1(implode('|', $parts));
    }

    /**
     * @return array<string, mixed>
     */
    private function context(AlertRule $rule): array
    {
        $context = $rule->getAttribute('context');

        return is_array($context) ? $context : [];
    }
}
