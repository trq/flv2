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
                status: CycleCloseStatus::BLOCKED->value,
                canClose: false,
                blocker: [
                    'code' => CycleCloseBlockerCode::PENDING_EVENTS->value,
                    'message' => 'Cycle close is blocked until all pending events are resolved.',
                    'pending_event_count' => $pendingEventCount,
                ],
                steps: [
                    [
                        'id' => CycleCloseStepId::RESOLVE_PENDING_EVENTS->value,
                        'status' => CycleCloseStepStatus::BLOCKED->value,
                        'pending_event_count' => $pendingEventCount,
                    ],
                ],
                review: null,
            );
        }

        return new CycleCloseResult(
            status: CycleCloseStatus::READY_FOR_CONFIRMATION->value,
            canClose: true,
            blocker: null,
            steps: [
                [
                    'id' => CycleCloseStepId::RESOLVE_PENDING_EVENTS->value,
                    'status' => CycleCloseStepStatus::PASSED->value,
                ],
                [
                    'id' => CycleCloseStepId::REVIEW_GOAL_OUTCOMES->value,
                    'status' => CycleCloseStepStatus::COMPLETED->value,
                ],
                [
                    'id' => CycleCloseStepId::CONFIRM_ROLLOVER_PLAN->value,
                    'status' => CycleCloseStepStatus::AWAITING_CONFIRMATION->value,
                ],
            ],
            review: [
                'over_goal_count' => $overGoalCount,
                'under_goal_count' => $underGoalCount,
            ],
        );
    }
}
