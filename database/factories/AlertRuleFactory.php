<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertRule>
 */
class AlertRuleFactory extends Factory
{
    protected $model = AlertRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => (string) $this->faker->numberBetween(1, 9999),
            'budget_id' => 'budget_'.$this->faker->randomNumber(3),
            'goal_id' => 'goal_'.$this->faker->randomNumber(3),
            'cycle_id' => 'cycle_'.$this->faker->randomNumber(3),
            'rule_type' => $this->faker->randomElement(['overspend_risk', 'missed_income', 'savings_drift']),
            'threshold_percent' => 80,
            'is_active' => true,
            'context' => [],
        ];
    }
}
