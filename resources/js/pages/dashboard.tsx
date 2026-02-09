import { Head } from '@inertiajs/react';
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

type DashboardWorkspaceProps = {
    workspace: {
        layout: string;
        chat_panel_enabled: boolean;
        widgets_enabled: boolean;
    };
};

export default function Dashboard({ workspace }: DashboardWorkspaceProps) {
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
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="h-3 overflow-hidden rounded-full bg-muted">
                                        <div className="h-full w-7/12 bg-emerald-500/70" />
                                    </div>
                                    <div className="h-3 overflow-hidden rounded-full bg-muted">
                                        <div className="h-full w-5/12 bg-amber-500/70" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 py-4">
                                <CardHeader>
                                    <CardTitle>Activity Timeline</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex h-20 items-end gap-2">
                                        <div className="w-full rounded-t bg-sky-500/60" style={{ height: '38%' }} />
                                        <div className="w-full rounded-t bg-sky-500/60" style={{ height: '62%' }} />
                                        <div className="w-full rounded-t bg-sky-500/60" style={{ height: '47%' }} />
                                        <div className="w-full rounded-t bg-sky-500/60" style={{ height: '79%' }} />
                                        <div className="w-full rounded-t bg-sky-500/60" style={{ height: '54%' }} />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 py-4">
                                <CardHeader>
                                    <CardTitle>Cycle Progress</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    <p className="text-sm text-muted-foreground">Day 11 of 31</p>
                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                        <div className="h-full w-[35%] bg-indigo-500/70" />
                                    </div>
                                    <p className="text-xs text-muted-foreground">35% complete</p>
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
