import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type WriteConfirmationCard = {
    id: string;
    type: 'write_confirmation';
    payload: {
        action_summary: string;
        result_status: string;
        entities: Array<{
            entity_type: string;
            entity_id: string;
            before: Record<string, unknown>;
            after: Record<string, unknown>;
        }>;
    };
};

type BlockedActionCard = {
    id: string;
    type: 'blocked_action';
    payload: {
        code: string;
        reason: string;
        next_step: string;
    };
};

type MetricsCard = {
    id: string;
    type: 'metrics';
    payload: {
        goal_name: string;
        spent_amount: number;
        cap_amount: number;
        remaining_amount: number;
        burn_rate_percent: number;
    };
};

export type ChatCardPayload = WriteConfirmationCard | BlockedActionCard | MetricsCard;

function formatWholeDollars(amount: number): string {
    return `$${Math.trunc(amount).toLocaleString()}`;
}

export function ChatCardRenderer({ card }: { card: ChatCardPayload }) {
    if (card.type === 'write_confirmation') {
        return (
            <Card className="border-emerald-500/40 py-4">
                <CardHeader>
                    <CardTitle>Action Confirmed</CardTitle>
                    <CardDescription>{card.payload.action_summary}</CardDescription>
                </CardHeader>
                <CardContent className="space-y-2 text-sm">
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">
                        Status: {card.payload.result_status}
                    </p>
                    {card.payload.entities.map((entity) => (
                        <div key={`${entity.entity_type}:${entity.entity_id}`} className="rounded-lg bg-muted/40 p-2">
                            <p className="font-medium">
                                {entity.entity_type}: {entity.entity_id}
                            </p>
                        </div>
                    ))}
                </CardContent>
            </Card>
        );
    }

    if (card.type === 'blocked_action') {
        return (
            <Card className="border-destructive/50 py-4">
                <CardHeader>
                    <CardTitle>Action Blocked</CardTitle>
                    <CardDescription>{card.payload.reason}</CardDescription>
                </CardHeader>
                <CardContent className="space-y-2 text-sm">
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">Code: {card.payload.code}</p>
                    <p className="rounded-lg bg-destructive/10 p-2">Next: {card.payload.next_step}</p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="border-sky-500/40 py-4">
            <CardHeader>
                <CardTitle>{card.payload.goal_name} Metrics</CardTitle>
                <CardDescription>Deterministic analytics payload</CardDescription>
            </CardHeader>
            <CardContent className="space-y-1 text-sm">
                <p>Spent: {formatWholeDollars(card.payload.spent_amount)}</p>
                <p>Cap: {formatWholeDollars(card.payload.cap_amount)}</p>
                <p>Remaining: {formatWholeDollars(card.payload.remaining_amount)}</p>
                <p>Burn Rate: {card.payload.burn_rate_percent}%</p>
            </CardContent>
        </Card>
    );
}
