import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { ChatCardRenderer } from '@/components/chat-card-renderer';

describe('ChatCardRenderer', () => {
    it('renders write confirmation card status and summary', () => {
        render(
            <ChatCardRenderer
                card={{
                    id: 'card_001',
                    type: 'write_confirmation',
                    payload: {
                        action_summary: 'Recorded $40 expense at IGA against Groceries.',
                        result_status: 'succeeded',
                        entities: [
                            {
                                entity_type: 'allocation_event',
                                entity_id: 'event_001',
                                before: {},
                                after: { amount: 40 },
                            },
                        ],
                    },
                }}
            />,
        );

        expect(screen.getByText('Action Confirmed')).toBeInTheDocument();
        expect(screen.getByText('Status: succeeded')).toBeInTheDocument();
    });

    it('renders blocked action reason and next step prompt', () => {
        render(
            <ChatCardRenderer
                card={{
                    id: 'card_002',
                    type: 'blocked_action',
                    payload: {
                        code: 'insufficient_pool_funds',
                        reason: 'Allocation blocked because available pool funds would go below zero.',
                        next_step: 'Adjust a goal target or add income before retrying this allocation.',
                    },
                }}
            />,
        );

        expect(screen.getByText('Action Blocked')).toBeInTheDocument();
        expect(screen.getByText('Code: insufficient_pool_funds')).toBeInTheDocument();
        expect(screen.getByText(/Adjust a goal target or add income/)).toBeInTheDocument();
    });

    it('renders deterministic metrics payload values', () => {
        render(
            <ChatCardRenderer
                card={{
                    id: 'card_003',
                    type: 'metrics',
                    payload: {
                        goal_name: 'Miscellaneous',
                        spent_amount: 220,
                        cap_amount: 500,
                        remaining_amount: 280,
                        burn_rate_percent: 88,
                    },
                }}
            />,
        );

        expect(screen.getByText('Miscellaneous Metrics')).toBeInTheDocument();
        expect(screen.getByText('Spent: $220')).toBeInTheDocument();
        expect(screen.getByText('Cap: $500')).toBeInTheDocument();
        expect(screen.getByText('Remaining: $280')).toBeInTheDocument();
        expect(screen.getByText('Burn Rate: 88%')).toBeInTheDocument();
    });
});
