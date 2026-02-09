import { render, screen, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Dashboard from '@/pages/dashboard';

vi.mock('@/layouts/app-layout', () => ({
    default: ({ children }: { children: ReactNode }) => <div data-testid="app-layout">{children}</div>,
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
}));

vi.mock('@/routes', () => ({
    dashboard: () => ({
        url: '/dashboard',
    }),
}));

type DashboardSnapshot = {
    income_allocation: {
        planned: { expenses: number; savings: number; total: number };
        actual: { expenses: number; savings: number; total: number };
        income_pool_balance: number;
    };
    activity_timeline: {
        points: Array<{ date: string; amount: number }>;
    };
    cycle_progress: {
        day: number;
        total_days: number;
        percent: number;
    };
};

const sampleSnapshot: DashboardSnapshot = {
    income_allocation: {
        planned: { expenses: 1800, savings: 1000, total: 2800 },
        actual: { expenses: 920, savings: 320, total: 1240 },
        income_pool_balance: 4760,
    },
    activity_timeline: {
        points: [
            { date: '2026-02-01', amount: 140 },
            { date: '2026-02-02', amount: 80 },
        ],
    },
    cycle_progress: {
        day: 11,
        total_days: 31,
        percent: 35,
    },
};

const workspaceProps = {
    layout: 'chat_dashboard_columns',
    chat_panel_enabled: true,
    widgets_enabled: true,
    snapshot_url: '/dashboard/snapshot',
    chat_cards: [
        {
            id: 'card_001',
            type: 'write_confirmation' as const,
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
        },
        {
            id: 'card_002',
            type: 'blocked_action' as const,
            payload: {
                code: 'insufficient_pool_funds',
                reason: 'Allocation blocked because available pool funds would go below zero.',
                next_step: 'Adjust a goal target or add income before retrying this allocation.',
            },
        },
        {
            id: 'card_003',
            type: 'metrics' as const,
            payload: {
                goal_name: 'Miscellaneous',
                spent_amount: 220,
                cap_amount: 500,
                remaining_amount: 280,
                burn_rate_percent: 88,
            },
        },
    ],
};

afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
});

describe('Dashboard workspace', () => {
    it('renders the two-column shell and chat card statuses', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => ({
                ok: true,
                json: async () => sampleSnapshot,
            })),
        );

        const { container } = render(<Dashboard workspace={workspaceProps} />);

        const shellSection = container.querySelector('section[data-layout="chat_dashboard_columns"]');
        const columns = shellSection?.querySelector('div.grid');

        expect(columns).toBeInTheDocument();
        expect(columns?.className).toContain('lg:grid-cols-[minmax(0,1.8fr)_minmax(18rem,1fr)]');

        await waitFor(() => {
            expect(screen.getByText('Action Confirmed')).toBeInTheDocument();
            expect(screen.getByText('Action Blocked')).toBeInTheDocument();
            expect(screen.getByText('Miscellaneous Metrics')).toBeInTheDocument();
        });
    });

    it('renders loading state while snapshot fetch is pending', () => {
        vi.stubGlobal('fetch', vi.fn(() => new Promise(() => undefined)));

        render(<Dashboard workspace={workspaceProps} />);

        expect(document.querySelectorAll('.animate-pulse').length).toBeGreaterThan(0);
    });

    it('renders error state when snapshot fetch fails', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => ({
                ok: false,
                json: async () => ({}),
            })),
        );

        render(<Dashboard workspace={workspaceProps} />);

        await waitFor(() => {
            expect(screen.getByText('Snapshot unavailable.')).toBeInTheDocument();
            expect(screen.getByText('Unable to load activity timeline.')).toBeInTheDocument();
            expect(screen.getByText('Unable to load cycle progress.')).toBeInTheDocument();
        });
    });

    it('renders timeline empty state when snapshot has no points', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => ({
                ok: true,
                json: async () => ({
                    ...sampleSnapshot,
                    activity_timeline: { points: [] },
                }),
            })),
        );

        render(<Dashboard workspace={workspaceProps} />);

        await waitFor(() => {
            expect(screen.getByText('No events recorded yet.')).toBeInTheDocument();
        });
    });
});
