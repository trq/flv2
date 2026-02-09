<?php

namespace App\Domain\Budgeting\Goals;

enum GoalType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';
    case SAVINGS_RECURRING = 'savings_recurring';
    case SAVINGS_TARGET_PARENT = 'savings_target_parent';
    case SAVINGS_TARGET_CHILD = 'savings_target_child';

    public function requiresSavingsPool(): bool
    {
        return match ($this) {
            self::SAVINGS_RECURRING,
            self::SAVINGS_TARGET_PARENT,
            self::SAVINGS_TARGET_CHILD => true,
            default => false,
        };
    }
}
