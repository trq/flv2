<?php

namespace App\Domain\Budgeting\Ledger;

use App\Domain\Budgeting\Exceptions\AllocationEventNotFound;

class AllocationEventJournal
{
    /**
     * @var array<int, array{
     *   event_id: string,
     *   goal_id: string,
     *   cycle_id: string,
     *   amount: int,
     *   compensates_event_id: string|null
     * }>
     */
    private array $events = [];

    /**
     * @return array{
     *   event_id: string,
     *   goal_id: string,
     *   cycle_id: string,
     *   amount: int,
     *   compensates_event_id: string|null
     * }
     */
    public function recordEvent(
        string $eventId,
        string $goalId,
        string $cycleId,
        int $amount,
        ?string $compensatesEventId = null,
    ): array {
        $event = [
            'event_id' => $eventId,
            'goal_id' => $goalId,
            'cycle_id' => $cycleId,
            'amount' => $amount,
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
     *   amount: int,
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

    /**
     * @return array<int, array{
     *   event_id: string,
     *   goal_id: string,
     *   cycle_id: string,
     *   amount: int,
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
     *   amount: int,
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
