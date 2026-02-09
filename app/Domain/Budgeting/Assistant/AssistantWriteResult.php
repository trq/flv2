<?php

namespace App\Domain\Budgeting\Assistant;

class AssistantWriteResult
{
    /**
     * @param  array{
     *   proposal_id: string,
     *   action_summary: string,
     *   entities: array<int, array{
     *     entity_type: string,
     *     entity_id: string,
     *     before: array<string, mixed>,
     *     after: array<string, mixed>
     *   }>,
     *   result_status: string
     * }  $confirmationCard
     * @param  array{code: string, message: string}|null  $error
     */
    public function __construct(
        private string $status,
        private bool $requiresConfirmation,
        private array $confirmationCard,
        private ?array $error = null,
    ) {}

    /**
     * @return array{
     *   status: string,
     *   requires_confirmation: bool,
     *   confirmation_card: array{
     *     proposal_id: string,
     *     action_summary: string,
     *     entities: array<int, array{
     *       entity_type: string,
     *       entity_id: string,
     *       before: array<string, mixed>,
     *       after: array<string, mixed>
     *     }>,
     *     result_status: string
     *   },
     *   error: array{code: string, message: string}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'requires_confirmation' => $this->requiresConfirmation,
            'confirmation_card' => $this->confirmationCard,
            'error' => $this->error,
        ];
    }
}
