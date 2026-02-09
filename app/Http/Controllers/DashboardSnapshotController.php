<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Budgeting\Analytics\DashboardAnalyticsSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardSnapshotController extends Controller
{
    public function __construct(
        private DashboardAnalyticsSnapshotService $dashboardAnalyticsSnapshotService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(
            $this->dashboardAnalyticsSnapshotService->snapshotForDate(CarbonImmutable::now()),
        );
    }
}
