<?php

namespace App\Ai\Agents;

use App\Domain\Budgeting\Assistant\AssistantIntent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class BudgetIntentClassifierAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'PROMPT'
You classify one user budgeting message into the Flowly V1 intent contract.

Available intents:
- onboarding
- goal_management
- allocation_create
- analytics_query

Rules:
1. Return exactly one primary_intent when confidence is sufficient.
2. If ambiguous, set requires_clarification=true and primary_intent=null.
3. Always return confidence_by_intent for all intents with values between 0 and 1.
4. Keep confidence aligned with the selected primary intent confidence.
5. Keep candidate_intents sorted highest confidence first.
PROMPT;
    }

    /**
     * Get the schema of the expected structured output.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'primary_intent' => $schema->string()->enum(AssistantIntent::class)->nullable(),
            'requires_clarification' => $schema->boolean()->required(),
            'confidence' => $schema->number()->min(0)->max(1)->required(),
            'confidence_by_intent' => $schema->object(fn (JsonSchema $schema): array => [
                AssistantIntent::ONBOARDING->value => $schema->number()->min(0)->max(1)->required(),
                AssistantIntent::GOAL_MANAGEMENT->value => $schema->number()->min(0)->max(1)->required(),
                AssistantIntent::ALLOCATION_CREATE->value => $schema->number()->min(0)->max(1)->required(),
                AssistantIntent::ANALYTICS_QUERY->value => $schema->number()->min(0)->max(1)->required(),
            ])->required()->withoutAdditionalProperties(),
            'candidate_intents' => $schema->array()
                ->items($schema->object(fn (JsonSchema $schema): array => [
                    'intent' => $schema->string()->enum(AssistantIntent::class)->required(),
                    'confidence' => $schema->number()->min(0)->max(1)->required(),
                ])->withoutAdditionalProperties())
                ->min(0)
                ->max(4)
                ->required(),
            'clarification_prompt' => $schema->string()->nullable(),
        ];
    }
}
