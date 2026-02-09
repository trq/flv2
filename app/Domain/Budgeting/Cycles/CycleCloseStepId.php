<?php

namespace App\Domain\Budgeting\Cycles;

enum CycleCloseStepId: string
{
    case RESOLVE_PENDING_EVENTS = 'resolve_pending_events';
    case REVIEW_GOAL_OUTCOMES = 'review_goal_outcomes';
    case CONFIRM_ROLLOVER_PLAN = 'confirm_rollover_plan';
}
