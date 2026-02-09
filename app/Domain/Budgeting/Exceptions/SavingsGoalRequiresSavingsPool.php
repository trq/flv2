<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class SavingsGoalRequiresSavingsPool extends DomainException
{
    public static function forGoalType(string $goalType): self
    {
        return new self(sprintf(
            'Goal type [%s] requires a linked savings pool.',
            $goalType,
        ));
    }
}
