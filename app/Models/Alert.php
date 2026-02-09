<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Alert extends Model
{
    /** @use HasFactory<\Database\Factories\AlertFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected $table = 'alerts';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'budget_id',
        'cycle_id',
        'goal_id',
        'rule_type',
        'severity',
        'status',
        'window_start',
        'window_end',
        'dedupe_key',
        'context',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'window_start' => 'immutable_datetime',
            'window_end' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
