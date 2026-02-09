<?php

namespace App\Domain\Budgeting\Assistant;

class AssistantIntentRouter
{
    public function __construct(private AssistantIntentClassifier $classifier) {}

    public function route(AssistantIntentRoutingRequest $request): AssistantIntentRouteResult
    {
        $classification = $this->classifier->classify($request);
        $primaryIntent = $classification->primaryIntent();
        $requiresClarification = $classification->requiresClarification() || $primaryIntent === null;

        if ($requiresClarification) {
            return new AssistantIntentRouteResult(
                routeType: 'clarification',
                primaryIntent: null,
                requiresClarification: true,
                confidence: $classification->topIntentConfidence(),
                confidenceByIntent: $classification->confidenceByIntent(),
                clarification: [
                    'reason' => 'ambiguous_intent',
                    'prompt' => $classification->clarificationPrompt()
                        ?? 'Did you want onboarding, goal management, allocation entry, or analytics?',
                    'candidate_intents' => $classification->candidateIntents(),
                ],
            );
        }

        return new AssistantIntentRouteResult(
            routeType: 'intent',
            primaryIntent: $primaryIntent?->value,
            requiresClarification: false,
            confidence: $classification->topIntentConfidence(),
            confidenceByIntent: $classification->confidenceByIntent(),
            clarification: null,
        );
    }
}
