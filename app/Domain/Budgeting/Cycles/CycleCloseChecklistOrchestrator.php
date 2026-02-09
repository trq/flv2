<?php

namespace App\Domain\Budgeting\Cycles;

class CycleCloseChecklistOrchestrator
{
    public function run(
        int $pendingEventCount,
        int $overGoalCount,
        int $underGoalCount,
    ): CycleCloseResult {
        if ($pendingEventCount > 0) {
            return new CycleCloseResult(
                status: 'blocked',
                canClose: false,
                blocker: [
                    'code' => 'pending_events',
                    'message' => 'Cycle close is blocked until all pending events are resolved.',
                    'pending_event_count' => $pendingEventCount,
                ],
                steps: [
                    [
                        'id' => 'resolve_pending_events',
                        'status' => 'blocked',
                        'pending_event_count' => $pendingEventCount,
                    ],
                ],
                review: null,
            );
        }

        return new CycleCloseResult(
            status: 'ready_for_confirmation',
            canClose: true,
            blocker: null,
            steps: [
                [
                    'id' => 'resolve_pending_events',
                    'status' => 'passed',
                ],
                [
                    'id' => 'review_goal_outcomes',
                    'status' => 'completed',
                ],
                [
                    'id' => 'confirm_rollover_plan',
                    'status' => 'awaiting_confirmation',
                ],
            ],
            review: [
                'over_goal_count' => $overGoalCount,
                'under_goal_count' => $underGoalCount,
            ],
        );
    }
}
