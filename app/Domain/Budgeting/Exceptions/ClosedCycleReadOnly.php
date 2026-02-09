<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class ClosedCycleReadOnly extends DomainException
{
    public static function create(): self
    {
        return new self('Cannot post allocation events to a closed cycle.');
    }
}
