<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class InsufficientSavingsPoolFunds extends DomainException
{
    public static function forWithdrawal(int $savingsPoolBalance, int $withdrawalAmount): self
    {
        return new self(sprintf(
            'Cannot apply savings withdrawal of %d because insufficient savings pool funds remain at %d.',
            $withdrawalAmount,
            $savingsPoolBalance,
        ));
    }
}
