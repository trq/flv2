<?php

namespace App\Domain\Budgeting\Ledger;

use App\Domain\Budgeting\Exceptions\AllocationEventMutationNotAllowed;

class AllocationEventMutationGuard
{
    public function assertAppendOnly(string $operation): void
    {
        throw AllocationEventMutationNotAllowed::forOperation($operation);
    }
}
