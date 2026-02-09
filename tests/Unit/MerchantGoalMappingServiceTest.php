<?php

declare(strict_types=1);

use App\Domain\Budgeting\Assistant\MerchantGoalMappingMatchType;
use App\Domain\Budgeting\Assistant\MerchantGoalMappingService;

it('resolves by exact mapping before alias and heuristic fallback', function () {
    $service = new MerchantGoalMappingService;

    $service->confirmMapping(userId: 'user_001', merchant: 'IGA', goalId: 'goal_groceries');
    $service->setAliasMapping(userId: 'user_001', alias: 'iga supermarket', goalId: 'goal_misc');

    $result = $service->resolve(
        userId: 'user_001',
        merchant: 'IGA',
        heuristicResolver: fn (): ?string => 'goal_fuel',
    )->toArray();

    expect($result['status'])->toBe('resolved')
        ->and($result['goal_id'])->toBe('goal_groceries')
        ->and($result['match_type'])->toBe(MerchantGoalMappingMatchType::EXACT->value)
        ->and($result['requires_confirmation'])->toBeFalse();
});

it('resolves by alias or fuzzy match when exact mapping does not exist', function () {
    $service = new MerchantGoalMappingService;

    $service->setAliasMapping(userId: 'user_001', alias: 'woolworths', goalId: 'goal_groceries');

    $result = $service->resolve(
        userId: 'user_001',
        merchant: 'Woolies',
        heuristicResolver: fn (): ?string => 'goal_misc',
    )->toArray();

    expect($result['status'])->toBe('resolved')
        ->and($result['goal_id'])->toBe('goal_groceries')
        ->and($result['match_type'])->toBe(MerchantGoalMappingMatchType::ALIAS_FUZZY->value)
        ->and($result['requires_confirmation'])->toBeFalse();
});

it('returns confirmation-required heuristic suggestion when no exact or alias mapping exists', function () {
    $service = new MerchantGoalMappingService;

    $result = $service->resolve(
        userId: 'user_001',
        merchant: 'BP Highway',
        heuristicResolver: fn (): ?string => 'goal_fuel',
    )->toArray();

    expect($result['status'])->toBe('needs_confirmation')
        ->and($result['goal_id'])->toBe('goal_fuel')
        ->and($result['match_type'])->toBe(MerchantGoalMappingMatchType::HEURISTIC->value)
        ->and($result['requires_confirmation'])->toBeTrue();
});

it('returns unknown-merchant confirmation path when no mapping is found', function () {
    $service = new MerchantGoalMappingService;

    $result = $service->resolve(
        userId: 'user_001',
        merchant: 'Completely Unknown Merchant',
        heuristicResolver: fn (): ?string => null,
    )->toArray();

    expect($result['status'])->toBe('needs_confirmation')
        ->and($result['goal_id'])->toBeNull()
        ->and($result['match_type'])->toBe(MerchantGoalMappingMatchType::UNKNOWN->value)
        ->and($result['requires_confirmation'])->toBeTrue();
});

it('applies learned mappings only for the user who confirmed them', function () {
    $service = new MerchantGoalMappingService;

    $service->confirmMapping(userId: 'user_001', merchant: 'IGA', goalId: 'goal_groceries');

    $resolvedForOwner = $service->resolve(
        userId: 'user_001',
        merchant: 'IGA',
        heuristicResolver: fn (): ?string => null,
    )->toArray();

    $resolvedForOtherUser = $service->resolve(
        userId: 'user_002',
        merchant: 'IGA',
        heuristicResolver: fn (): ?string => null,
    )->toArray();

    expect($resolvedForOwner['status'])->toBe('resolved')
        ->and($resolvedForOwner['goal_id'])->toBe('goal_groceries')
        ->and($resolvedForOtherUser['status'])->toBe('needs_confirmation')
        ->and($resolvedForOtherUser['goal_id'])->toBeNull();
});

it('records deterministic audit trail for mapping overrides', function () {
    $service = new MerchantGoalMappingService;

    $service->confirmMapping(userId: 'user_001', merchant: 'IGA', goalId: 'goal_groceries');
    $service->confirmMapping(userId: 'user_001', merchant: 'IGA', goalId: 'goal_misc');

    $auditTrail = $service->auditTrailForUser('user_001');

    expect($auditTrail)->toHaveCount(2)
        ->and($auditTrail[0])->toMatchArray([
            'action' => 'create_mapping',
            'merchant' => 'iga',
            'before_goal_id' => null,
            'after_goal_id' => 'goal_groceries',
        ])
        ->and($auditTrail[1])->toMatchArray([
            'action' => 'override_mapping',
            'merchant' => 'iga',
            'before_goal_id' => 'goal_groceries',
            'after_goal_id' => 'goal_misc',
        ]);

    expect($service->mappingsForUser('user_001'))->toMatchArray([
        'exact' => ['iga' => 'goal_misc'],
    ]);
});
