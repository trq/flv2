<?php

namespace App\Domain\Budgeting\Assistant;

use InvalidArgumentException;

class AssistantWritePolicy
{
    public function __construct(
        public AssistantWriteExecutionMode $mode,
        public float $autoExecuteConfidenceThreshold = 0.9,
    ) {
        if ($this->autoExecuteConfidenceThreshold < 0 || $this->autoExecuteConfidenceThreshold > 1) {
            throw new InvalidArgumentException('Auto execute confidence threshold must be between 0 and 1.');
        }
    }

    public function shouldAutoExecute(float $confidence): bool
    {
        if ($this->mode !== AssistantWriteExecutionMode::CONFIDENCE_BASED) {
            return false;
        }

        return $confidence >= $this->autoExecuteConfidenceThreshold;
    }
}
