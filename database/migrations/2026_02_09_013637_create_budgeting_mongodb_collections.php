<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->shouldRunMigration()) {
            return;
        }

        foreach ($this->collectionIndexes() as $collection => $indexes) {
            Schema::connection('mongodb')->create($collection, function (Blueprint $blueprint) use ($indexes): void {
                foreach ($indexes as $index) {
                    $blueprint->index($index['fields'], null, null, $index['options']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->shouldRunMigration()) {
            return;
        }

        foreach (array_keys($this->collectionIndexes()) as $collection) {
            Schema::connection('mongodb')->dropIfExists($collection);
        }
    }

    /**
     * @return array<string, array<int, array{fields: array<string, int>, options: array<string, mixed>}>>
     */
    public function collectionIndexes(): array
    {
        return [
            'budgets' => [
                ['fields' => ['owner_user_id' => 1], 'options' => []],
                ['fields' => ['deleted_at' => 1], 'options' => []],
            ],
            'budget_members' => [
                ['fields' => ['budget_id' => 1, 'user_id' => 1], 'options' => ['unique' => true]],
                ['fields' => ['user_id' => 1], 'options' => []],
            ],
            'budget_settings' => [
                ['fields' => ['budget_id' => 1], 'options' => ['unique' => true]],
            ],
            'cycles' => [
                ['fields' => ['budget_id' => 1, 'status' => 1, 'starts_at' => 1], 'options' => []],
            ],
            'goals' => [
                ['fields' => ['budget_id' => 1, 'cycle_id' => 1, 'type' => 1, 'status' => 1], 'options' => []],
                ['fields' => ['parent_goal_id' => 1], 'options' => []],
                ['fields' => ['budget_id' => 1, 'savings_pool_id' => 1, 'type' => 1], 'options' => []],
            ],
            'savings_pools' => [
                ['fields' => ['budget_id' => 1, 'name' => 1], 'options' => ['unique' => true]],
                ['fields' => ['budget_id' => 1, 'deleted_at' => 1], 'options' => []],
            ],
            'allocation_events' => [
                ['fields' => ['budget_id' => 1, 'cycle_id' => 1, 'goal_id' => 1, 'status' => 1, 'created_at' => 1], 'options' => []],
                ['fields' => ['budget_id' => 1, 'cycle_id' => 1, 'created_at' => 1], 'options' => []],
                ['fields' => ['budget_id' => 1, 'savings_pool_id' => 1, 'created_at' => 1], 'options' => []],
            ],
            'merchant_rules' => [
                ['fields' => ['user_id' => 1, 'normalized_merchant' => 1], 'options' => ['unique' => true]],
            ],
            'assistant_runs' => [
                ['fields' => ['budget_id' => 1, 'cycle_id' => 1, 'created_at' => 1], 'options' => []],
                ['fields' => ['user_id' => 1, 'created_at' => 1], 'options' => []],
            ],
            'alert_rules' => [
                ['fields' => ['budget_id' => 1, 'goal_id' => 1], 'options' => []],
                ['fields' => ['budget_id' => 1, 'is_active' => 1], 'options' => []],
            ],
            'alerts' => [
                ['fields' => ['budget_id' => 1, 'cycle_id' => 1, 'resolved_at' => 1], 'options' => []],
            ],
        ];
    }

    private function shouldRunMigration(): bool
    {
        return config('database.default') === 'mongodb';
    }
};
