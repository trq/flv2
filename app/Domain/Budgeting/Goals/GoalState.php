<?php

namespace App\Domain\Budgeting\Goals;

enum GoalState: string
{
    case ACTIVE = 'active';
    case SOFT_DELETED = 'soft_deleted';
}
