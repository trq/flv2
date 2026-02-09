<?php

namespace App\Domain\Budgeting\Assistant;

class AssistantIntentRouteResult
{
    /**
     * @param  array<string, float>  $confidenceByIntent
     * @param  array{
     *   reason: string,
     *   prompt: string,
     *   candidate_intents: array<int, array{
     *     intent: string,
     *     confidence: float
     *   }>
     * }|null  $clarification
     */
    public function __construct(
        private string $routeType,
        private ?string $primaryIntent,
        private bool $requiresClarification,
        private float $confidence,
        private array $confidenceByIntent,
        private ?array $clarification,
    ) {}

    /**
     * @return array{
     *   route_type: string,
     *   primary_intent: string|null,
     *   requires_clarification: bool,
     *   confidence: float,
     *   confidence_by_intent: array<string, float>,
     *   clarification: array{
     *     reason: string,
     *     prompt: string,
     *     candidate_intents: array<int, array{
     *       intent: string,
     *       confidence: float
     *     }>
     *   }|null
     * }
     */
    public function toArray(): array
    {
        return [
            'route_type' => $this->routeType,
            'primary_intent' => $this->primaryIntent,
            'requires_clarification' => $this->requiresClarification,
            'confidence' => $this->confidence,
            'confidence_by_intent' => $this->confidenceByIntent,
            'clarification' => $this->clarification,
        ];
    }
}
