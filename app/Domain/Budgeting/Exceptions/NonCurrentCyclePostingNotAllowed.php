<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class NonCurrentCyclePostingNotAllowed extends DomainException
{
    public static function create(): self
    {
        return new self('Cannot post allocation events to a non-current cycle.');
    }
}
