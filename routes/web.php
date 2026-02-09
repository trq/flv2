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
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('dashboard/snapshot', DashboardSnapshotController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard.snapshot');

require __DIR__.'/settings.php';
