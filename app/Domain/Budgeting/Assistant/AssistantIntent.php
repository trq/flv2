<?php

namespace App\Domain\Budgeting\Assistant;

enum AssistantIntent: string
{
    case ONBOARDING = 'onboarding';
    case GOAL_MANAGEMENT = 'goal_management';
    case ALLOCATION_CREATE = 'allocation_create';
    case ANALYTICS_QUERY = 'analytics_query';

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        return [
            self::ONBOARDING,
            self::GOAL_MANAGEMENT,
            self::ALLOCATION_CREATE,
            self::ANALYTICS_QUERY,
        ];
    }
}
