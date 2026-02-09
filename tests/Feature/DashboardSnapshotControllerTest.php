<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from dashboard snapshot endpoint', function () {
    $this->getJson(route('dashboard.snapshot'))
        ->assertUnauthorized();
});

it('returns dashboard analytics snapshot schema for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('dashboard.snapshot'));

    $response
        ->assertOk()
        ->assertJsonStructure([
            'income_allocation' => [
                'planned' => ['expenses', 'savings', 'total'],
                'actual' => ['expenses', 'savings', 'total'],
                'income_pool_balance',
            ],
            'activity_timeline' => [
                'points' => [
                    '*' => ['date', 'amount'],
                ],
            ],
            'cycle_progress' => ['day', 'total_days', 'percent'],
        ]);

    expect($response->json('income_allocation.planned.total'))->toBeInt()
        ->and($response->json('income_allocation.actual.total'))->toBeInt()
        ->and($response->json('income_allocation.income_pool_balance'))->toBeInt()
        ->and($response->json('cycle_progress.day'))->toBeInt()
        ->and($response->json('cycle_progress.total_days'))->toBeInt()
        ->and($response->json('cycle_progress.percent'))->toBeInt();

    foreach ($response->json('activity_timeline.points') as $point) {
        expect($point['date'])->toBeString()
            ->and($point['amount'])->toBeInt();
    }
});
