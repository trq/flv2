<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class AlertRule extends Model
{
    /** @use HasFactory<\Database\Factories\AlertRuleFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected $table = 'alert_rules';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'budget_id',
        'goal_id',
        'cycle_id',
        'rule_type',
        'threshold_percent',
        'is_active',
        'context',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'threshold_percent' => 'integer',
            'is_active' => 'boolean',
            'context' => 'array',
        ];
    }
}
