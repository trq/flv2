<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class AllocationEventNotFound extends DomainException
{
    public static function forEventId(string $eventId): self
    {
        return new self(sprintf(
            'Allocation event with id "%s" was not found.',
            $eventId,
        ));
    }
}
