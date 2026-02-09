<?php

declare(strict_types=1);

use App\Ai\Agents\BudgetIntentClassifierAgent;
use App\Domain\Budgeting\Assistant\AssistantIntent;
use App\Domain\Budgeting\Assistant\AssistantIntentClassifier;
use App\Domain\Budgeting\Assistant\AssistantIntentRouter;
use App\Domain\Budgeting\Assistant\AssistantIntentRoutingRequest;
use Tests\TestCase;

uses(TestCase::class);

it('routes onboarding chat input to a single onboarding primary intent', function () {
    BudgetIntentClassifierAgent::fake([
        [
            'primary_intent' => AssistantIntent::ONBOARDING->value,
            'requires_clarification' => false,
            'confidence' => 0.96,
            'confidence_by_intent' => [
                AssistantIntent::ONBOARDING->value => 0.96,
                AssistantIntent::GOAL_MANAGEMENT->value => 0.01,
                AssistantIntent::ALLOCATION_CREATE->value => 0.01,
                AssistantIntent::ANALYTICS_QUERY->value => 0.02,
            ],
            'candidate_intents' => [
                ['intent' => AssistantIntent::ONBOARDING->value, 'confidence' => 0.96],
            ],
            'clarification_prompt' => null,
        ],
    ]);

    $router = new AssistantIntentRouter(new AssistantIntentClassifier);

    $result = $router->route(
        AssistantIntentRoutingRequest::fromMessage('Help me set up my budget and get started.'),
    )->toArray();

    expect($result['primary_intent'])->toBe(AssistantIntent::ONBOARDING->value)
        ->and($result['route_type'])->toBe('intent')
        ->and($result['requires_clarification'])->toBeFalse()
        ->and($result['confidence'])->toBeFloat()
        ->and($result['confidence_by_intent'])->toHaveKeys([
            AssistantIntent::ONBOARDING->value,
            AssistantIntent::GOAL_MANAGEMENT->value,
            AssistantIntent::ALLOCATION_CREATE->value,
            AssistantIntent::ANALYTICS_QUERY->value,
        ]);
});

it('routes goal-management input to exactly one goal-management primary intent', function () {
    BudgetIntentClassifierAgent::fake([
        [
            'primary_intent' => AssistantIntent::GOAL_MANAGEMENT->value,
            'requires_clarification' => false,
            'confidence' => 0.92,
            'confidence_by_intent' => [
                AssistantIntent::ONBOARDING->value => 0.02,
                AssistantIntent::GOAL_MANAGEMENT->value => 0.92,
                AssistantIntent::ALLOCATION_CREATE->value => 0.02,
                AssistantIntent::ANALYTICS_QUERY->value => 0.04,
            ],
            'candidate_intents' => [
                ['intent' => AssistantIntent::GOAL_MANAGEMENT->value, 'confidence' => 0.92],
            ],
            'clarification_prompt' => null,
        ],
    ]);

    $router = new AssistantIntentRouter(new AssistantIntentClassifier);

    $result = $router->route(
        AssistantIntentRoutingRequest::fromMessage('Increase my groceries goal to 900 for this cycle.'),
    )->toArray();

    expect($result['primary_intent'])->toBe(AssistantIntent::GOAL_MANAGEMENT->value)
        ->and($result['requires_clarification'])->toBeFalse();
});

it('routes allocation creation input to exactly one allocation primary intent', function () {
    BudgetIntentClassifierAgent::fake([
        [
            'primary_intent' => AssistantIntent::ALLOCATION_CREATE->value,
            'requires_clarification' => false,
            'confidence' => 0.94,
            'confidence_by_intent' => [
                AssistantIntent::ONBOARDING->value => 0.01,
                AssistantIntent::GOAL_MANAGEMENT->value => 0.02,
                AssistantIntent::ALLOCATION_CREATE->value => 0.94,
                AssistantIntent::ANALYTICS_QUERY->value => 0.03,
            ],
            'candidate_intents' => [
                ['intent' => AssistantIntent::ALLOCATION_CREATE->value, 'confidence' => 0.94],
            ],
            'clarification_prompt' => null,
        ],
    ]);

    $router = new AssistantIntentRouter(new AssistantIntentClassifier);

    $result = $router->route(
        AssistantIntentRoutingRequest::fromMessage('Spent $40 at IGA.'),
    )->toArray();

    expect($result['primary_intent'])->toBe(AssistantIntent::ALLOCATION_CREATE->value)
        ->and($result['requires_clarification'])->toBeFalse();
});

it('routes analytics input to exactly one analytics primary intent', function () {
    BudgetIntentClassifierAgent::fake([
        [
            'primary_intent' => AssistantIntent::ANALYTICS_QUERY->value,
            'requires_clarification' => false,
            'confidence' => 0.9,
            'confidence_by_intent' => [
                AssistantIntent::ONBOARDING->value => 0.03,
                AssistantIntent::GOAL_MANAGEMENT->value => 0.02,
                AssistantIntent::ALLOCATION_CREATE->value => 0.05,
                AssistantIntent::ANALYTICS_QUERY->value => 0.9,
            ],
            'candidate_intents' => [
                ['intent' => AssistantIntent::ANALYTICS_QUERY->value, 'confidence' => 0.9],
            ],
            'clarification_prompt' => null,
        ],
    ]);

    $router = new AssistantIntentRouter(new AssistantIntentClassifier);

    $result = $router->route(
        AssistantIntentRoutingRequest::fromMessage('How is groceries tracking this cycle?'),
    )->toArray();

    expect($result['primary_intent'])->toBe(AssistantIntent::ANALYTICS_QUERY->value)
        ->and($result['requires_clarification'])->toBeFalse();
});

it('returns a clarification path for ambiguous chat input', function () {
    BudgetIntentClassifierAgent::fake([
        [
            'primary_intent' => null,
            'requires_clarification' => true,
            'confidence' => 0.41,
            'confidence_by_intent' => [
                AssistantIntent::ONBOARDING->value => 0.27,
                AssistantIntent::GOAL_MANAGEMENT->value => 0.24,
                AssistantIntent::ALLOCATION_CREATE->value => 0.22,
                AssistantIntent::ANALYTICS_QUERY->value => 0.27,
            ],
            'candidate_intents' => [
                ['intent' => AssistantIntent::ONBOARDING->value, 'confidence' => 0.27],
                ['intent' => AssistantIntent::ANALYTICS_QUERY->value, 'confidence' => 0.27],
            ],
            'clarification_prompt' => 'Are you trying to set up your budget or check analytics?',
        ],
    ]);

    $router = new AssistantIntentRouter(new AssistantIntentClassifier);

    $result = $router->route(
        AssistantIntentRoutingRequest::fromMessage('Can you help?'),
    )->toArray();

    expect($result['route_type'])->toBe('clarification')
        ->and($result['primary_intent'])->toBeNull()
        ->and($result['requires_clarification'])->toBeTrue()
        ->and($result['clarification'])->toHaveKeys([
            'reason',
            'prompt',
            'candidate_intents',
        ])
        ->and($result['clarification']['candidate_intents'])->toBeArray()
        ->and(count($result['clarification']['candidate_intents']))->toBeGreaterThanOrEqual(2);
});

it('normalizes routing request and response contracts for downstream actions', function () {
    BudgetIntentClassifierAgent::fake([
        [
            'primary_intent' => AssistantIntent::ALLOCATION_CREATE->value,
            'requires_clarification' => false,
            'confidence' => 0.95,
            'confidence_by_intent' => [
                AssistantIntent::ONBOARDING->value => 0.01,
                AssistantIntent::GOAL_MANAGEMENT->value => 0.02,
                AssistantIntent::ALLOCATION_CREATE->value => 0.95,
                AssistantIntent::ANALYTICS_QUERY->value => 0.02,
            ],
            'candidate_intents' => [
                ['intent' => AssistantIntent::ALLOCATION_CREATE->value, 'confidence' => 0.95],
            ],
            'clarification_prompt' => null,
        ],
    ]);

    $router = new AssistantIntentRouter(new AssistantIntentClassifier);

    $request = AssistantIntentRoutingRequest::fromMessage('   Spent $40 at IGA   ');
    $result = $router->route($request)->toArray();

    expect($request->toArray())->toMatchArray([
        'message' => 'Spent $40 at IGA',
        'normalized_message' => 'spent 40 at iga',
    ]);

    BudgetIntentClassifierAgent::assertPrompted(
        fn ($prompt): bool => $prompt->contains('"normalized_message":"spent 40 at iga"'),
    );

    expect($result)->toHaveKeys([
        'route_type',
        'primary_intent',
        'requires_clarification',
        'confidence',
        'confidence_by_intent',
        'clarification',
    ]);
});
