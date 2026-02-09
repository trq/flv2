<?php

namespace App\Domain\Budgeting\Assistant;

enum AssistantWriteExecutionMode: string
{
    case CONFIRMATION_ONLY = 'confirmation_only';
    case CONFIDENCE_BASED = 'confidence_based';
}
