<?php

namespace App\Domain\Budgeting\Cycles;

enum CycleGeneratedEventMetadataSource: string
{
    case CYCLE_CLOSE_CONFIRMATION = 'cycle_close_confirmation';
}
