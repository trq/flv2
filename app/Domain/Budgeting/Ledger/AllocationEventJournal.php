<?php

namespace App\Domain\Budgeting\Ledger;

use App\Domain\Budgeting\Exceptions\AllocationEventMutationNotAllowed;
use App\Domain\Budgeting\Exceptions\AllocationEventNotFound;

class AllocationEventJournal
{
    /**
     * @var array<int, array{
     *   event_id: string,
     *   goal_id: string,
     *   cycle_id: string,
     *   amount: float,
     *   compensates_event_id: string|null
     * }>
     */
    private array $events = [];

    /**
     * @return array{
     *   event_id: string,
     *   goal_id: string,
     *   cycle_id: string,
     *   amount: float,
     *   compensates_event_id: string|null
     * }
     */
    public function recordEvent(
        string $eventId,
        string $goalId,
        string $cycleId,
        float $amount,
        ?string $compensatesEventId = null,
    ): array {
        $event = [
            'event_id' => $eventId,
            'goal_id' => $goalId,
            'cycle_id' => $cycleId,
            'amount' => round($amount, 2),
            'compensates_event_id' => $compensatesEventId,
        ];

        $this->events[] = $event;

        return $event;
    }

    /**
     * @return array{
     *   event_id: string,
     *   goal_id: string,
     *   cycle_id: string,
     *   amount: float,
     *   compensates_event_id: string|null
     * }
     */
    public function recordCompensatingEvent(string $newEventId, string $originalEventId): array
    {
        $originalEvent = $this->findById($originalEventId);

        return $this->recordEvent(
            eventId: $newEventId,
            goalId: $originalEvent['goal_id'],
            cycleId: $originalEvent['cycle_id'],
            amount: -1 * $originalEvent['amount'],
            compensatesEventId: $originalEvent['event_id'],
        );
    }

    public function updateEvent(string $eventId, float $amount): void
    {
        unset($eventId, $amount);

        throw AllocationEventMutationNotAllowed::forOperation('update');
    }

    public function deleteEvent(string $eventId): void
    {
        unset($eventId);

        throw AllocationEventMutationNotAllowed::forOperation('delete');
    }

    public function goalBalance(string $goalId): float
    {
        $balance = 0.0;

        foreach ($this->events as $event) {
            if ($event['goal_id'] !== $goalId) {
                continue;
            }

            $balance += $event['amount'];
        }

        return round($balance, 2);
    }

    /**
     * @return array<string, float>
     */
    public function reconstructGoalBalances(): array
    {
        $balances = [];

        foreach ($this->events as $event) {
            if (! array_key_exists($event['goal_id'], $balances)) {
                $balances[$event['goal_id']] = 0.0;
            }

            $balances[$event['goal_id']] += $event['amount'];
            $balances[$event['goal_id']] = round($balances[$event['goal_id']], 2);
        }

        ksort($balances);

        return $balances;
    }

    /**
     * @return array<int, array{
     *   event_id: string,
     *   goal_id: string,
     *   cycle_id: string,
     *   amount: float,
     *   compensates_event_id: string|null
     * }>
     */
    public function history(): array
    {
        return $this->events;
    }

    /**
     * @return array{
     *   event_id: string,
     *   goal_id: string,
     *   cycle_id: string,
     *   amount: float,
     *   compensates_event_id: string|null
     * }
     */
    private function findById(string $eventId): array
    {
        foreach ($this->events as $event) {
            if ($event['event_id'] === $eventId) {
                return $event;
            }
        }

        throw AllocationEventNotFound::forEventId($eventId);
    }
}
