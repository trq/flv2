<?php

use Illuminate\Support\Arr;

test('mongodb connection is configured for the budgeting domain', function () {
    $mongodbConnection = config('database.connections.mongodb');

    expect($mongodbConnection)
        ->toBeArray()
        ->and(Arr::get($mongodbConnection, 'driver'))->toBe('mongodb')
        ->and(Arr::get($mongodbConnection, 'port'))->toBe(27018)
        ->and(Arr::get($mongodbConnection, 'database'))->toBeString();
});

test('budgeting mongo migration defines required collections and indexes', function () {
    $migrationPath = collect(glob(database_path('migrations/*_create_budgeting_mongodb_collections.php')))
        ->first();

    expect($migrationPath)->not->toBeFalse();

    /** @var object $migration */
    $migration = require $migrationPath;

    expect(method_exists($migration, 'collectionIndexes'))->toBeTrue();
    expect(method_exists($migration, 'shouldRunMigration'))->toBeFalse();

    /** @var array<string, array<int, array<string, mixed>>> $collectionIndexes */
    $collectionIndexes = $migration->collectionIndexes();

    expect($collectionIndexes)
        ->toHaveKeys([
            'budgets',
            'budget_members',
            'budget_settings',
            'cycles',
            'goals',
            'savings_pools',
            'allocation_events',
            'merchant_rules',
            'assistant_runs',
            'alert_rules',
            'alerts',
        ]);

    expect($collectionIndexes['cycles'])->toContain([
        'fields' => ['budget_id' => 1, 'status' => 1, 'starts_at' => 1],
        'options' => [],
    ]);

    expect($collectionIndexes['goals'])->toContain(
        ['fields' => ['budget_id' => 1, 'cycle_id' => 1, 'type' => 1, 'status' => 1], 'options' => []],
        ['fields' => ['parent_goal_id' => 1], 'options' => []],
        ['fields' => ['budget_id' => 1, 'savings_pool_id' => 1, 'type' => 1], 'options' => []],
    );

    expect($collectionIndexes['savings_pools'])->toContain(
        ['fields' => ['budget_id' => 1, 'name' => 1], 'options' => ['unique' => true]],
        ['fields' => ['budget_id' => 1, 'deleted_at' => 1], 'options' => []],
    );

    expect($collectionIndexes['allocation_events'])->toContain(
        ['fields' => ['budget_id' => 1, 'cycle_id' => 1, 'goal_id' => 1, 'status' => 1, 'created_at' => 1], 'options' => []],
        ['fields' => ['budget_id' => 1, 'cycle_id' => 1, 'created_at' => 1], 'options' => []],
        ['fields' => ['budget_id' => 1, 'savings_pool_id' => 1, 'created_at' => 1], 'options' => []],
    );

    expect($collectionIndexes['merchant_rules'])->toContain([
        'fields' => ['user_id' => 1, 'normalized_merchant' => 1],
        'options' => ['unique' => true],
    ]);

    expect($collectionIndexes['alerts'])->toContain([
        'fields' => ['budget_id' => 1, 'cycle_id' => 1, 'resolved_at' => 1],
        'options' => [],
    ]);
});
