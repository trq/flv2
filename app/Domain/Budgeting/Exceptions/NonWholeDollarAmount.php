<?php

namespace App\Domain\Budgeting\Exceptions;

use DomainException;

class NonWholeDollarAmount extends DomainException
{
    public static function forField(string $field, int|float|string $value): self
    {
        return new self(sprintf(
            'Field [%s] must be a signed whole-dollar integer. Received [%s].',
            $field,
            self::stringify($value),
        ));
    }

    private static function stringify(int|float|string $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return (string) $value;
    }
}
