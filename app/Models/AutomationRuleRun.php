<?php

namespace App\Models;

use App\Enums\AutomationRunStatus;
use App\Models\Concerns\BelongsToUser;
use App\Policies\AutomationRuleRunPolicy;
use Database\Factories\AutomationRuleRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['automation_rule_id', 'status', 'dry_run', 'matched_count', 'changed_count', 'skipped_count', 'details', 'message', 'started_at', 'finished_at'])]
#[UsePolicy(AutomationRuleRunPolicy::class)]
class AutomationRuleRun extends Model
{
    /** @use HasFactory<AutomationRuleRunFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AutomationRunStatus::class,
            'dry_run' => 'boolean',
            'details' => 'array',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<AutomationRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
}
