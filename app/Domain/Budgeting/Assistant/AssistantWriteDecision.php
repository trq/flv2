<?php

namespace App\Domain\Budgeting\Assistant;

enum AssistantWriteDecision: string
{
    case NONE = 'none';
    case APPROVE = 'approve';
    case REJECT = 'reject';
}
