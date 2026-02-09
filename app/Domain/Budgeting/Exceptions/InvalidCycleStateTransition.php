<?php

namespace App\Domain\Budgeting\Exceptions;

use App\Domain\Budgeting\Cycles\CycleState;
use DomainException;

class InvalidCycleStateTransition extends DomainException
{
    public static function forTransition(CycleState $from, CycleState $to): self
    {
        return new self(sprintf(
            'Cycle cannot transition from [%s] to [%s].',
            $from->value,
            $to->value,
        ));
    }
}
