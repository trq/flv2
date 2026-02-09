<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class AllocationEventMutationNotAllowed extends DomainException
{
    public static function forOperation(string $operation): self
    {
        return new self(sprintf(
            'Allocation events are append-only. %s operations are not allowed.',
            ucfirst($operation),
        ));
    }
}
