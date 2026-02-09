<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class InsufficientPoolFunds extends DomainException
{
    public static function forAllocation(
        float $availablePoolAmount,
        float $requestedAllocationAmount,
    ): self {
        $shortfallAmount = $requestedAllocationAmount - $availablePoolAmount;

        return new self(sprintf(
            'Cannot allocate %s against an available pool of %s. Add income or reduce allocations by %s.',
            self::formatCurrency($requestedAllocationAmount),
            self::formatCurrency($availablePoolAmount),
            self::formatCurrency($shortfallAmount),
        ));
    }

    private static function formatCurrency(float $amount): string
    {
        return '$'.number_format($amount, 2, '.', ',');
    }
}
