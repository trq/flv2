<?php

namespace App\Domain\Budgeting\Assistant;

use Throwable;

class AssistantWriteOrchestrator
{
    /**
     * @param  callable(AssistantWriteProposal): bool  $executeWrite
     */
    public function run(
        AssistantWriteProposal $proposal,
        AssistantWritePolicy $policy,
        AssistantWriteDecision $decision,
        callable $executeWrite,
    ): AssistantWriteResult {
        if ($decision === AssistantWriteDecision::REJECT) {
            return new AssistantWriteResult(
                status: 'rejected',
                requiresConfirmation: false,
                confirmationCard: $this->confirmationCard($proposal, 'rejected'),
                error: [
                    'code' => 'write_rejected_by_user',
                    'message' => 'Write action rejected by user confirmation.',
                ],
            );
        }

        if ($this->requiresConfirmation($proposal, $policy, $decision)) {
            return new AssistantWriteResult(
                status: 'proposed',
                requiresConfirmation: true,
                confirmationCard: $this->confirmationCard($proposal, 'pending_confirmation'),
            );
        }

        try {
            $wasSuccessful = (bool) $executeWrite($proposal);
        } catch (Throwable) {
            $wasSuccessful = false;
        }

        if (! $wasSuccessful) {
            return new AssistantWriteResult(
                status: 'failed',
                requiresConfirmation: false,
                confirmationCard: $this->confirmationCard($proposal, 'failed'),
                error: [
                    'code' => 'write_execution_failed',
                    'message' => 'Write action execution failed.',
                ],
            );
        }

        return new AssistantWriteResult(
            status: 'succeeded',
            requiresConfirmation: false,
            confirmationCard: $this->confirmationCard($proposal, 'succeeded'),
        );
    }

    private function requiresConfirmation(
        AssistantWriteProposal $proposal,
        AssistantWritePolicy $policy,
        AssistantWriteDecision $decision,
    ): bool {
        if ($decision === AssistantWriteDecision::APPROVE) {
            return false;
        }

        return ! $policy->shouldAutoExecute($proposal->confidence);
    }

    /**
     * @return array{
     *   proposal_id: string,
     *   action_summary: string,
     *   entities: array<int, array{
     *     entity_type: string,
     *     entity_id: string,
     *     before: array<string, mixed>,
     *     after: array<string, mixed>
     *   }>,
     *   result_status: string
     * }
     */
    private function confirmationCard(AssistantWriteProposal $proposal, string $resultStatus): array
    {
        return [
            'proposal_id' => $proposal->proposalId,
            'action_summary' => $proposal->actionSummary,
            'entities' => $proposal->entities,
            'result_status' => $resultStatus,
        ];
    }
}
