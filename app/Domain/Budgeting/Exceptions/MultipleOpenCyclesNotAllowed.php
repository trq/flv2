<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class MultipleOpenCyclesNotAllowed extends DomainException
{
    public static function forOpenCycleCount(int $openCycleCount): self
    {
        return new self(sprintf(
            'Cannot open a new cycle because this budget already has %d open cycle%s.',
            $openCycleCount,
            $openCycleCount === 1 ? '' : 's',
        ));
    }
}
