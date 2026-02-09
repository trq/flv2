<?php

namespace App\Domain\Budgeting\Cycles;

class CycleRolloverResult
{
    /**
     * @param  array{
     *   cycle_id: string,
     *   start_date: string,
     *   end_date: string,
     *   state: string
     * }  $nextCycle
     * @param  array{
     *   current_cycle_id: string,
     *   next_cycle_id: string,
     *   rollover_amount: int
     * }  $closeSummary
     * @param  array<int, array{
     *   event_id: string,
     *   budget_id: string,
     *   cycle_id: string,
     *   goal_id: string,
     *   amount: int,
     *   source: string,
     *   append_only: bool,
     *   metadata: array{
     *     actor_type: string,
     *     actor_id: string,
     *     source: string
     *   }
     * }>  $generatedEvents
     */
    public function __construct(
        private array $nextCycle,
        private array $closeSummary,
        private array $generatedEvents,
    ) {}

    /**
     * @return array{
     *   next_cycle: array{
     *     cycle_id: string,
     *     start_date: string,
     *     end_date: string,
     *     state: string
     *   },
     *   close_summary: array{
     *     current_cycle_id: string,
     *     next_cycle_id: string,
     *     rollover_amount: int
     *   },
     *   generated_events: array<int, array{
     *     event_id: string,
     *     budget_id: string,
     *     cycle_id: string,
     *     goal_id: string,
     *     amount: int,
     *     source: string,
     *     append_only: bool,
     *     metadata: array{
     *       actor_type: string,
     *       actor_id: string,
     *       source: string
     *     }
     *   }>
     * }
     */
    public function toArray(): array
    {
        return [
            'next_cycle' => $this->nextCycle,
            'close_summary' => $this->closeSummary,
            'generated_events' => $this->generatedEvents,
        ];
    }
}
