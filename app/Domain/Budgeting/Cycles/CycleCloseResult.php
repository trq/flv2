<?php

namespace App\Domain\Budgeting\Cycles;

class CycleCloseResult
{
    /**
     * @param  array<int, array{
     *   id: string,
     *   status: string,
     *   pending_event_count?: int
     * }>  $steps
     * @param  array{
     *   code: string,
     *   message: string,
     *   pending_event_count: int
     * }|null  $blocker
     * @param  array{
     *   over_goal_count: int,
     *   under_goal_count: int
     * }|null  $review
     */
    public function __construct(
        private string $status,
        private bool $canClose,
        private ?array $blocker,
        private array $steps,
        private ?array $review,
    ) {}

    /**
     * @return array{
     *   status: string,
     *   can_close: bool,
     *   blocker: array{
     *     code: string,
     *     message: string,
     *     pending_event_count: int
     *   }|null,
     *   steps: array<int, array{
     *     id: string,
     *     status: string,
     *     pending_event_count?: int
     *   }>,
     *   review: array{
     *     over_goal_count: int,
     *     under_goal_count: int
     *   }|null
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'can_close' => $this->canClose,
            'blocker' => $this->blocker,
            'steps' => $this->steps,
            'review' => $this->review,
        ];
    }
}
