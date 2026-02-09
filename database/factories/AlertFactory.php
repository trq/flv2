<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

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
            'cycle_id' => 'cycle_'.$this->faker->randomNumber(3),
            'goal_id' => 'goal_'.$this->faker->randomNumber(3),
            'rule_type' => 'overspend_risk',
            'severity' => 'warning',
            'status' => 'open',
            'window_start' => now()->startOfDay(),
            'window_end' => now()->endOfDay(),
            'dedupe_key' => $this->faker->uuid(),
            'context' => [],
            'resolved_at' => null,
        ];
    }
}
