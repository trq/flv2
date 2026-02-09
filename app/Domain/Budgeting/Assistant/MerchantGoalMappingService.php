<?php

declare(strict_types=1);

namespace App\Domain\Budgeting\Assistant;

class MerchantGoalMappingService
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $exactMappingsByUser = [];

    /**
     * @var array<string, array<string, string>>
     */
    private array $aliasMappingsByUser = [];

    /**
     * @var array<string, array<int, array{
     *   action: string,
     *   merchant: string,
     *   before_goal_id: string|null,
     *   after_goal_id: string
     * }>>
     */
    private array $auditTrailByUser = [];

    /**
     * @param  callable(): ?string  $heuristicResolver
     */
    public function resolve(string $userId, string $merchant, callable $heuristicResolver): MerchantGoalMappingResult
    {
        $normalizedMerchant = $this->normalizeMerchant($merchant);

        $exactGoalId = $this->exactMappingsByUser[$userId][$normalizedMerchant] ?? null;

        if ($exactGoalId !== null) {
            return new MerchantGoalMappingResult(
                status: 'resolved',
                goalId: $exactGoalId,
                matchType: MerchantGoalMappingMatchType::EXACT,
                requiresConfirmation: false,
            );
        }

        $aliasGoalId = $this->resolveAliasGoalId($userId, $normalizedMerchant);

        if ($aliasGoalId !== null) {
            return new MerchantGoalMappingResult(
                status: 'resolved',
                goalId: $aliasGoalId,
                matchType: MerchantGoalMappingMatchType::ALIAS_FUZZY,
                requiresConfirmation: false,
            );
        }

        $heuristicGoalId = $heuristicResolver();

        if ($heuristicGoalId !== null) {
            return new MerchantGoalMappingResult(
                status: 'needs_confirmation',
                goalId: $heuristicGoalId,
                matchType: MerchantGoalMappingMatchType::HEURISTIC,
                requiresConfirmation: true,
            );
        }

        return new MerchantGoalMappingResult(
            status: 'needs_confirmation',
            goalId: null,
            matchType: MerchantGoalMappingMatchType::UNKNOWN,
            requiresConfirmation: true,
        );
    }

    public function confirmMapping(string $userId, string $merchant, string $goalId): void
    {
        $normalizedMerchant = $this->normalizeMerchant($merchant);
        $beforeGoalId = $this->exactMappingsByUser[$userId][$normalizedMerchant] ?? null;

        $this->exactMappingsByUser[$userId][$normalizedMerchant] = $goalId;

        if ($beforeGoalId === $goalId) {
            return;
        }

        $action = $beforeGoalId === null ? 'create_mapping' : 'override_mapping';

        $this->auditTrailByUser[$userId][] = [
            'action' => $action,
            'merchant' => $normalizedMerchant,
            'before_goal_id' => $beforeGoalId,
            'after_goal_id' => $goalId,
        ];
    }

    public function setAliasMapping(string $userId, string $alias, string $goalId): void
    {
        $this->aliasMappingsByUser[$userId][$this->normalizeMerchant($alias)] = $goalId;
    }

    /**
     * @return array{exact: array<string, string>, alias: array<string, string>}
     */
    public function mappingsForUser(string $userId): array
    {
        return [
            'exact' => $this->exactMappingsByUser[$userId] ?? [],
            'alias' => $this->aliasMappingsByUser[$userId] ?? [],
        ];
    }

    /**
     * @return array<int, array{
     *   action: string,
     *   merchant: string,
     *   before_goal_id: string|null,
     *   after_goal_id: string
     * }>
     */
    public function auditTrailForUser(string $userId): array
    {
        return $this->auditTrailByUser[$userId] ?? [];
    }

    private function resolveAliasGoalId(string $userId, string $normalizedMerchant): ?string
    {
        $aliases = $this->aliasMappingsByUser[$userId] ?? [];

        if ($aliases === []) {
            return null;
        }

        ksort($aliases);

        $bestGoalId = null;
        $bestScore = 0.0;

        foreach ($aliases as $alias => $goalId) {
            if ($alias === $normalizedMerchant) {
                return $goalId;
            }

            if (str_contains($alias, $normalizedMerchant) || str_contains($normalizedMerchant, $alias)) {
                return $goalId;
            }

            similar_text($alias, $normalizedMerchant, $score);

            if ($score >= 55.0 && $score > $bestScore) {
                $bestScore = $score;
                $bestGoalId = $goalId;
            }
        }

        return $bestGoalId;
    }

    private function normalizeMerchant(string $merchant): string
    {
        $value = strtolower(trim($merchant));
        $value = preg_replace('/[^a-z0-9 ]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
