<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Budgeting\Alerts\BudgetAlertCheckService;
use App\Models\User;
use App\Notifications\BudgetAlertTriggeredNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RunBudgetAlertChecks extends Command
{
    protected $signature = 'budget:check-alerts {--window-hours=24 : Hours to evaluate for the alert window}';

    protected $description = 'Run scheduled budget checks and generate alert records.';

    public function __construct(
        private BudgetAlertCheckService $budgetAlertCheckService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $windowHours = max(1, (int) $this->option('window-hours'));
        $windowEnd = CarbonImmutable::now()->startOfMinute();
        $windowStart = $windowEnd->subHours($windowHours);

        $createdAlerts = $this->budgetAlertCheckService->runWindow($windowStart, $windowEnd);
        $sentNotifications = 0;

        foreach ($createdAlerts as $alert) {
            $userId = $alert->getAttribute('user_id');

            if (! is_string($userId) || $userId === '') {
                continue;
            }

            $user = User::query()->find($userId);

            if ($user === null) {
                continue;
            }

            $user->notify(new BudgetAlertTriggeredNotification($alert));
            $sentNotifications++;
        }

        $createdAlertCount = $createdAlerts->count();

        $this->info("Created {$createdAlertCount} alerts for {$windowStart->toDateTimeString()} to {$windowEnd->toDateTimeString()}.");
        $this->info("Sent {$sentNotifications} in-app notifications.");

        return self::SUCCESS;
    }
}
