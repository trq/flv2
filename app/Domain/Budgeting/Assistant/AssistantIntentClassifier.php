<?php

namespace App\Domain\Budgeting\Assistant;

use App\Ai\Agents\BudgetIntentClassifierAgent;
use Illuminate\Contracts\Support\Arrayable;
use Throwable;

class AssistantIntentClassifier
{
    public function __construct(private ?BudgetIntentClassifierAgent $agent = null) {}

    public function classify(AssistantIntentRoutingRequest $request): AssistantIntentClassificationResult
    {
        try {
            $response = ($this->agent ?? BudgetIntentClassifierAgent::make())->prompt(
                prompt: $this->buildPrompt($request),
                provider: (string) config('services.ai.intent_provider', 'openrouter'),
                model: (string) config('services.ai.intent_model', 'anthropic/sonnet'),
                timeout: (int) config('services.ai.intent_timeout', 30),
            );

            if (! $response instanceof Arrayable) {
                return AssistantIntentClassificationResult::ambiguousFallback(
                    clarificationPrompt: 'I could not confidently classify that request. What would you like to do?',
                );
            }

            return AssistantIntentClassificationResult::fromPayload((array) $response->toArray());
        } catch (Throwable) {
            return AssistantIntentClassificationResult::ambiguousFallback(
                clarificationPrompt: 'I could not confidently classify that request. What would you like to do?',
            );
        }
    }

    private function buildPrompt(AssistantIntentRoutingRequest $request): string
    {
        return json_encode([
            'message' => $request->message(),
            'normalized_message' => $request->normalizedMessage(),
            'task' => 'Classify the user message into one Flowly intent.',
        ], JSON_THROW_ON_ERROR);
    }
}
