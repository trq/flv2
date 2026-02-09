import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { ChatCardRenderer, type ChatCardPayload } from '@/components/chat-card-renderer';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

type DashboardSnapshot = {
    income_allocation: {
        planned: {
            expenses: number;
            savings: number;
            total: number;
        };
        actual: {
            expenses: number;
            savings: number;
            total: number;
        };
        income_pool_balance: number;
    };
    activity_timeline: {
        points: Array<{
            date: string;
            amount: number;
        }>;
    };
    cycle_progress: {
        day: number;
        total_days: number;
        percent: number;
    };
};

type DashboardWorkspaceProps = {
    workspace: {
        layout: string;
        chat_panel_enabled: boolean;
        widgets_enabled: boolean;
        snapshot_url: string;
        chat_cards: ChatCardPayload[];
    };
};

function asPercent(part: number, total: number): number {
    if (total <= 0) {
        return 0;
    }

    return Math.max(0, Math.min(100, Math.round((part / total) * 100)));
}

function formatWholeDollars(amount: number): string {
    return `$${Math.trunc(amount).toLocaleString()}`;
}

export default function Dashboard({ workspace }: DashboardWorkspaceProps) {
    const [snapshot, setSnapshot] = useState<DashboardSnapshot | null>(null);
    const [isLoadingSnapshot, setIsLoadingSnapshot] = useState<boolean>(workspace.widgets_enabled);

    useEffect(() => {
        if (!workspace.widgets_enabled) {
            return;
        }

        let isCancelled = false;
        const controller = new AbortController();

        const loadSnapshot = async () => {
            try {
                setIsLoadingSnapshot(true);

                const response = await fetch(workspace.snapshot_url, {
                    headers: {
                        Accept: 'application/json',
                    },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error('Unable to load dashboard snapshot.');
                }

                const payload = (await response.json()) as DashboardSnapshot;

                if (!isCancelled) {
                    setSnapshot(payload);
                }
            } catch {
                if (!isCancelled) {
                    setSnapshot(null);
                }
            } finally {
                if (!isCancelled) {
                    setIsLoadingSnapshot(false);
                }
            }
        };

        void loadSnapshot();

        return () => {
            isCancelled = true;
            controller.abort();
        };
    }, [workspace.snapshot_url, workspace.widgets_enabled]);

    const timelinePoints = snapshot?.activity_timeline.points ?? [];

    const maxTimelineAmount = useMemo(() => {
        return Math.max(1, ...timelinePoints.map((point) => point.amount));
    }, [timelinePoints]);

    const plannedExpenses = snapshot?.income_allocation.planned.expenses ?? 0;
    const plannedSavings = snapshot?.income_allocation.planned.savings ?? 0;
    const plannedTotal = snapshot?.income_allocation.planned.total ?? 0;

    const actualExpenses = snapshot?.income_allocation.actual.expenses ?? 0;
    const actualSavings = snapshot?.income_allocation.actual.savings ?? 0;
    const actualTotal = snapshot?.income_allocation.actual.total ?? 0;

    const cycleDay = snapshot?.cycle_progress.day ?? 0;
    const cycleTotalDays = snapshot?.cycle_progress.total_days ?? 0;
    const cyclePercent = snapshot?.cycle_progress.percent ?? 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <section
                data-layout={workspace.layout}
                className="flex flex-1 flex-col gap-4 overflow-x-hidden rounded-xl p-4"
            >
                <div className="grid gap-4 lg:grid-cols-[minmax(0,1.8fr)_minmax(18rem,1fr)]">
                    {workspace.chat_panel_enabled ? (
                        <Card className="min-h-[70vh] border-sidebar-border/70 py-0">
                            <CardHeader className="border-b border-sidebar-border/70 py-6">
                                <CardTitle>Assistant Chat</CardTitle>
                                <CardDescription>
                                    Capture spending, update goals, and ask budget questions in plain language.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex h-full min-h-[22rem] flex-col gap-4 py-6">
                                <div className="flex-1 space-y-3 overflow-auto rounded-xl border border-dashed border-border/80 bg-muted/20 p-3">
                                    <div className="ml-auto max-w-[85%] rounded-lg bg-primary/10 px-3 py-2 text-sm">
                                        Spent $40 at IGA
                                    </div>
                                    <div className="max-w-[85%] rounded-lg bg-secondary px-3 py-2 text-sm">
                                        Logged to Groceries. You are 95% away from this cycle cap.
                                    </div>
                                    <div className="space-y-3">
                                        {workspace.chat_cards.map((card) => (
                                            <ChatCardRenderer key={card.id} card={card} />
                                        ))}
                                    </div>
                                </div>
                                <div className="rounded-xl border border-border bg-background px-4 py-3 text-sm text-muted-foreground">
                                    Type a message to create events, adjust goals, or ask for analysis.
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card className="min-h-[24rem] border-sidebar-border/70 py-0">
                            <CardHeader className="py-6">
                                <CardTitle>Assistant Chat</CardTitle>
                                <CardDescription>Chat panel is currently disabled for this workspace.</CardDescription>
                            </CardHeader>
                        </Card>
                    )}

                    {workspace.widgets_enabled ? (
                        <div className="flex flex-col gap-4">
                            <Card className="border-sidebar-border/70 py-4">
                                <CardHeader>
                                    <CardTitle>Income Allocation</CardTitle>
                                    <CardDescription>
                                        Planned vs actual totals with current income pool balance.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {isLoadingSnapshot ? (
                                        <div className="space-y-3">
                                            <div className="h-3 animate-pulse rounded-full bg-muted" />
                                            <div className="h-3 animate-pulse rounded-full bg-muted" />
                                            <div className="h-3 w-1/2 animate-pulse rounded-full bg-muted" />
                                        </div>
                                    ) : (
                                        <>
                                            <div className="space-y-1">
                                                <p className="text-xs uppercase tracking-wide text-muted-foreground">Planned</p>
                                                <div className="flex h-3 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full bg-emerald-500/70"
                                                        style={{ width: `${asPercent(plannedExpenses, plannedTotal)}%` }}
                                                    />
                                                    <div
                                                        className="h-full bg-amber-500/70"
                                                        style={{ width: `${asPercent(plannedSavings, plannedTotal)}%` }}
                                                    />
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    Expenses {formatWholeDollars(plannedExpenses)} | Savings{' '}
                                                    {formatWholeDollars(plannedSavings)}
                                                </p>
                                            </div>
                                            <div className="space-y-1">
                                                <p className="text-xs uppercase tracking-wide text-muted-foreground">Actual</p>
                                                <div className="flex h-3 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full bg-sky-500/70"
                                                        style={{ width: `${asPercent(actualExpenses, actualTotal)}%` }}
                                                    />
                                                    <div
                                                        className="h-full bg-indigo-500/70"
                                                        style={{ width: `${asPercent(actualSavings, actualTotal)}%` }}
                                                    />
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    Expenses {formatWholeDollars(actualExpenses)} | Savings{' '}
                                                    {formatWholeDollars(actualSavings)}
                                                </p>
                                            </div>
                                            <p className="text-sm font-medium text-foreground">
                                                Pool Balance:{' '}
                                                {formatWholeDollars(
                                                    snapshot?.income_allocation.income_pool_balance ?? 0,
                                                )}
                                            </p>
                                        </>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 py-4">
                                <CardHeader>
                                    <CardTitle>Activity Timeline</CardTitle>
                                    <CardDescription>Current-cycle allocation events.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {isLoadingSnapshot ? (
                                        <div className="h-20 animate-pulse rounded-xl bg-muted" />
                                    ) : timelinePoints.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">No events recorded yet.</p>
                                    ) : (
                                        <div className="flex h-20 items-end gap-2">
                                            {timelinePoints.map((point) => {
                                                const height = Math.max(
                                                    8,
                                                    Math.round((point.amount / maxTimelineAmount) * 100),
                                                );

                                                return (
                                                    <div
                                                        key={point.date}
                                                        className="flex-1 rounded-t bg-sky-500/60"
                                                        style={{ height: `${height}%` }}
                                                        title={`${point.date}: ${formatWholeDollars(point.amount)}`}
                                                    />
                                                );
                                            })}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 py-4">
                                <CardHeader>
                                    <CardTitle>Cycle Progress</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    {isLoadingSnapshot ? (
                                        <div className="space-y-2">
                                            <div className="h-4 w-1/2 animate-pulse rounded bg-muted" />
                                            <div className="h-2 animate-pulse rounded-full bg-muted" />
                                        </div>
                                    ) : (
                                        <>
                                            <p className="text-sm text-muted-foreground">
                                                Day {cycleDay} of {cycleTotalDays}
                                            </p>
                                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className="h-full bg-indigo-500/70"
                                                    style={{ width: `${cyclePercent}%` }}
                                                />
                                            </div>
                                            <p className="text-xs text-muted-foreground">{cyclePercent}% complete</p>
                                        </>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    ) : (
                        <Card className="border-sidebar-border/70 py-4">
                            <CardHeader>
                                <CardTitle>Widgets</CardTitle>
                                <CardDescription>Widget column is currently disabled for this workspace.</CardDescription>
                            </CardHeader>
                        </Card>
                    )}
                </div>
            </section>
        </AppLayout>
    );
}
