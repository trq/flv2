<?php

declare(strict_types=1);

use App\Domain\Budgeting\Assistant\AssistantWriteDecision;
use App\Domain\Budgeting\Assistant\AssistantWriteExecutionMode;
use App\Domain\Budgeting\Assistant\AssistantWriteOrchestrator;
use App\Domain\Budgeting\Assistant\AssistantWritePolicy;
use App\Domain\Budgeting\Assistant\AssistantWriteProposal;

function sampleWriteProposal(float $confidence = 0.95): AssistantWriteProposal
{
    return new AssistantWriteProposal(
        proposalId: 'proposal_001',
        actionSummary: 'Update groceries goal target to $900.',
        confidence: $confidence,
        entities: [
            [
                'entity_type' => 'goal',
                'entity_id' => 'goal_groceries',
                'before' => ['target_amount' => 800],
                'after' => ['target_amount' => 900],
            ],
        ],
    );
}

it('returns a confirmation-required proposal when policy is confirmation-only and no approval is provided', function () {
    $orchestrator = new AssistantWriteOrchestrator;

    $result = $orchestrator->run(
        proposal: sampleWriteProposal(),
        policy: new AssistantWritePolicy(AssistantWriteExecutionMode::CONFIRMATION_ONLY),
        decision: AssistantWriteDecision::NONE,
        executeWrite: fn (): bool => true,
    )->toArray();

    expect($result['status'])->toBe('proposed')
        ->and($result['requires_confirmation'])->toBeTrue()
        ->and($result['confirmation_card']['result_status'])->toBe('pending_confirmation');
});

it('executes write after explicit approval and emits a structured confirmation card', function () {
    $orchestrator = new AssistantWriteOrchestrator;
    $writeExecutionCount = 0;

    $result = $orchestrator->run(
        proposal: sampleWriteProposal(),
        policy: new AssistantWritePolicy(AssistantWriteExecutionMode::CONFIRMATION_ONLY),
        decision: AssistantWriteDecision::APPROVE,
        executeWrite: function () use (&$writeExecutionCount): bool {
            $writeExecutionCount++;

            return true;
        },
    )->toArray();

    expect($writeExecutionCount)->toBe(1)
        ->and($result['status'])->toBe('succeeded')
        ->and($result['requires_confirmation'])->toBeFalse()
        ->and($result['confirmation_card'])->toHaveKeys([
            'proposal_id',
            'action_summary',
            'entities',
            'result_status',
        ])
        ->and($result['confirmation_card']['action_summary'])->toBe('Update groceries goal target to $900.')
        ->and($result['confirmation_card']['entities'][0])->toHaveKeys([
            'entity_type',
            'entity_id',
            'before',
            'after',
        ])
        ->and($result['confirmation_card']['result_status'])->toBe('succeeded');
});

it('auto-executes writes in confidence-based mode when confidence meets threshold', function () {
    $orchestrator = new AssistantWriteOrchestrator;
    $writeExecutionCount = 0;

    $result = $orchestrator->run(
        proposal: sampleWriteProposal(0.93),
        policy: new AssistantWritePolicy(
            mode: AssistantWriteExecutionMode::CONFIDENCE_BASED,
            autoExecuteConfidenceThreshold: 0.9,
        ),
        decision: AssistantWriteDecision::NONE,
        executeWrite: function () use (&$writeExecutionCount): bool {
            $writeExecutionCount++;

            return true;
        },
    )->toArray();

    expect($writeExecutionCount)->toBe(1)
        ->and($result['status'])->toBe('succeeded')
        ->and($result['requires_confirmation'])->toBeFalse();
});

it('keeps writes in pending confirmation when confidence is below threshold', function () {
    $orchestrator = new AssistantWriteOrchestrator;
    $writeExecutionCount = 0;

    $result = $orchestrator->run(
        proposal: sampleWriteProposal(0.63),
        policy: new AssistantWritePolicy(
            mode: AssistantWriteExecutionMode::CONFIDENCE_BASED,
            autoExecuteConfidenceThreshold: 0.9,
        ),
        decision: AssistantWriteDecision::NONE,
        executeWrite: function () use (&$writeExecutionCount): bool {
            $writeExecutionCount++;

            return true;
        },
    )->toArray();

    expect($writeExecutionCount)->toBe(0)
        ->and($result['status'])->toBe('proposed')
        ->and($result['requires_confirmation'])->toBeTrue()
        ->and($result['confirmation_card']['result_status'])->toBe('pending_confirmation');
});

it('returns deterministic rejection payload when user rejects write', function () {
    $orchestrator = new AssistantWriteOrchestrator;
    $writeExecutionCount = 0;

    $result = $orchestrator->run(
        proposal: sampleWriteProposal(),
        policy: new AssistantWritePolicy(AssistantWriteExecutionMode::CONFIRMATION_ONLY),
        decision: AssistantWriteDecision::REJECT,
        executeWrite: function () use (&$writeExecutionCount): bool {
            $writeExecutionCount++;

            return true;
        },
    )->toArray();

    expect($writeExecutionCount)->toBe(0)
        ->and($result['status'])->toBe('rejected')
        ->and($result['requires_confirmation'])->toBeFalse()
        ->and($result['error'])->toBe([
            'code' => 'write_rejected_by_user',
            'message' => 'Write action rejected by user confirmation.',
        ])
        ->and($result['confirmation_card']['result_status'])->toBe('rejected');
});
