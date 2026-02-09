<?php

use App\Http\Controllers\DashboardSnapshotController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard', [
        'workspace' => [
            'layout' => 'chat_dashboard_columns',
            'chat_panel_enabled' => true,
            'widgets_enabled' => true,
            'snapshot_url' => route('dashboard.snapshot'),
            'chat_cards' => [
                [
                    'id' => 'card_001',
                    'type' => 'write_confirmation',
                    'payload' => [
                        'action_summary' => 'Recorded $40 expense at IGA against Groceries.',
                        'result_status' => 'succeeded',
                        'entities' => [
                            [
                                'entity_type' => 'allocation_event',
                                'entity_id' => 'event_001',
                                'before' => [],
                                'after' => [
                                    'goal_id' => 'goal_groceries',
                                    'amount' => 40,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'card_002',
                    'type' => 'blocked_action',
                    'payload' => [
                        'code' => 'insufficient_pool_funds',
                        'reason' => 'Allocation blocked because available pool funds would go below zero.',
                        'next_step' => 'Adjust a goal target or add income before retrying this allocation.',
                    ],
                ],
                [
                    'id' => 'card_003',
                    'type' => 'metrics',
                    'payload' => [
                        'goal_name' => 'Miscellaneous',
                        'spent_amount' => 220,
                        'cap_amount' => 500,
                        'remaining_amount' => 280,
                        'burn_rate_percent' => 88,
                    ],
                ],
            ],
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('dashboard/snapshot', DashboardSnapshotController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard.snapshot');

require __DIR__.'/settings.php';
