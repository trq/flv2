<?php

namespace App\Domain\Budgeting\Assistant;

class AssistantWriteProposal
{
    /**
     * @param  array<int, array{
     *   entity_type: string,
     *   entity_id: string,
     *   before: array<string, mixed>,
     *   after: array<string, mixed>
     * }>  $entities
     */
    public function __construct(
        public string $proposalId,
        public string $actionSummary,
        public float $confidence,
        public array $entities,
    ) {}

    /**
     * @return array{
     *   proposal_id: string,
     *   action_summary: string,
     *   confidence: float,
     *   entities: array<int, array{
     *     entity_type: string,
     *     entity_id: string,
     *     before: array<string, mixed>,
     *     after: array<string, mixed>
     *   }>
     * }
     */
    public function toArray(): array
    {
        return [
            'proposal_id' => $this->proposalId,
            'action_summary' => $this->actionSummary,
            'confidence' => $this->confidence,
            'entities' => $this->entities,
        ];
    }
}
