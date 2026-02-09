<?php

namespace App\Domain\Budgeting\Assistant;

class AssistantIntentRoutingRequest
{
    private function __construct(
        private string $message,
        private string $normalizedMessage,
    ) {}

    public static function fromMessage(string $message): self
    {
        $trimmedMessage = trim($message);

        return new self(
            message: $trimmedMessage,
            normalizedMessage: self::normalizeMessage($trimmedMessage),
        );
    }

    public function message(): string
    {
        return $this->message;
    }

    public function normalizedMessage(): string
    {
        return $this->normalizedMessage;
    }

    /**
     * @return array{
     *   message: string,
     *   normalized_message: string
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'normalized_message' => $this->normalizedMessage,
        ];
    }

    private static function normalizeMessage(string $message): string
    {
        $withoutSpecialCharacters = preg_replace('/[^a-z0-9\\s]/i', ' ', $message);
        $collapsedWhitespace = preg_replace('/\\s+/', ' ', $withoutSpecialCharacters ?? '');

        return strtolower(trim($collapsedWhitespace ?? ''));
    }
}
