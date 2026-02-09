<?php

declare(strict_types=1);

namespace App\Domain\Budgeting\Assistant;

class MerchantGoalMappingResult
{
    public function __construct(
        private string $status,
        private ?string $goalId,
        private MerchantGoalMappingMatchType $matchType,
        private bool $requiresConfirmation,
    ) {}

    /**
     * @return array{
     *   status: string,
     *   goal_id: string|null,
     *   match_type: string,
     *   requires_confirmation: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'goal_id' => $this->goalId,
            'match_type' => $this->matchType->value,
            'requires_confirmation' => $this->requiresConfirmation,
        ];
    }
}
