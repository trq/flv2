<?php

namespace App\Domain\Budgeting\Assistant;

class AssistantIntentClassificationResult
{
    /**
     * @param  array<string, float>  $confidenceByIntent
     * @param  array<int, array{intent: string, confidence: float}>  $candidateIntents
     */
    public function __construct(
        private ?AssistantIntent $primaryIntent,
        private bool $requiresClarification,
        private float $confidence,
        private array $confidenceByIntent,
        private array $candidateIntents,
        private ?string $clarificationPrompt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $confidenceByIntent = collect(AssistantIntent::all())
            ->mapWithKeys(function (AssistantIntent $intent) use ($payload): array {
                return [
                    $intent->value => self::normalizeConfidence(
                        value: data_get($payload, sprintf('confidence_by_intent.%s', $intent->value), 0.0),
                    ),
                ];
            })->all();

        $explicitPrimaryIntent = self::resolveIntent(data_get($payload, 'primary_intent'));
        $requiresClarification = (bool) data_get($payload, 'requires_clarification', false);

        $inferredPrimaryIntent = collect($confidenceByIntent)
            ->sortDesc()
            ->keys()
            ->first();
        $primaryIntent = $explicitPrimaryIntent
            ?? ($inferredPrimaryIntent === null ? null : AssistantIntent::from($inferredPrimaryIntent));

        if ($requiresClarification) {
            $primaryIntent = null;
        }

        $confidence = self::normalizeConfidence(
            value: data_get(
                $payload,
                'confidence',
                $primaryIntent === null
                    ? max($confidenceByIntent)
                    : $confidenceByIntent[$primaryIntent->value],
            ),
        );

        $candidateIntents = collect((array) data_get($payload, 'candidate_intents', []))
            ->map(function (mixed $candidate): ?array {
                if (! is_array($candidate)) {
                    return null;
                }

                $intent = self::resolveIntent($candidate['intent'] ?? null);

                if ($intent === null) {
                    return null;
                }

                return [
                    'intent' => $intent->value,
                    'confidence' => self::normalizeConfidence($candidate['confidence'] ?? 0.0),
                ];
            })
            ->filter()
            ->sortByDesc('confidence')
            ->values()
            ->all();

        if ($requiresClarification && count($candidateIntents) < 2) {
            $candidateIntents = collect($confidenceByIntent)
                ->sortDesc()
                ->take(2)
                ->map(
                    fn (float $intentConfidence, string $intent): array => [
                        'intent' => $intent,
                        'confidence' => $intentConfidence,
                    ],
                )->values()->all();
        }

        return new self(
            primaryIntent: $primaryIntent,
            requiresClarification: $requiresClarification,
            confidence: $confidence,
            confidenceByIntent: $confidenceByIntent,
            candidateIntents: $candidateIntents,
            clarificationPrompt: data_get($payload, 'clarification_prompt'),
        );
    }

    public static function ambiguousFallback(?string $clarificationPrompt = null): self
    {
        $confidenceByIntent = collect(AssistantIntent::all())
            ->mapWithKeys(fn (AssistantIntent $intent): array => [$intent->value => 0.0])
            ->all();

        return new self(
            primaryIntent: null,
            requiresClarification: true,
            confidence: 0.0,
            confidenceByIntent: $confidenceByIntent,
            candidateIntents: collect($confidenceByIntent)
                ->take(2)
                ->map(
                    fn (float $intentConfidence, string $intent): array => [
                        'intent' => $intent,
                        'confidence' => $intentConfidence,
                    ],
                )->values()->all(),
            clarificationPrompt: $clarificationPrompt,
        );
    }

    /**
     * @return array<string, float>
     */
    public function confidenceByIntent(): array
    {
        return $this->confidenceByIntent;
    }

    public function topIntentConfidence(): float
    {
        return $this->confidence;
    }

    public function primaryIntent(): ?AssistantIntent
    {
        return $this->primaryIntent;
    }

    public function requiresClarification(): bool
    {
        return $this->requiresClarification;
    }

    /**
     * @return array<int, array{intent: string, confidence: float}>
     */
    public function candidateIntents(): array
    {
        return $this->candidateIntents;
    }

    public function clarificationPrompt(): ?string
    {
        return $this->clarificationPrompt;
    }

    private static function resolveIntent(mixed $value): ?AssistantIntent
    {
        if (! is_string($value)) {
            return null;
        }

        return collect(AssistantIntent::all())
            ->first(fn (AssistantIntent $intent): bool => $intent->value === $value);
    }

    private static function normalizeConfidence(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        $normalized = (float) $value;

        return max(0.0, min(1.0, round($normalized, 4)));
    }
}
