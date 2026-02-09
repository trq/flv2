<?php

declare(strict_types=1);

namespace App\Domain\Budgeting\Assistant;

enum MerchantGoalMappingMatchType: string
{
    case EXACT = 'exact';
    case ALIAS_FUZZY = 'alias_fuzzy';
    case HEURISTIC = 'heuristic';
    case UNKNOWN = 'unknown';
}
