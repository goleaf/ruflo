<?php

namespace App\Models;

use App\Enums\AutomationRuleKind;
use App\Enums\AutomationRunStatus;
use App\Models\Concerns\BelongsToUser;
use App\Policies\AutomationRulePolicy;
use Database\Factories\AutomationRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'kind', 'is_enabled', 'settings', 'last_run_at', 'last_status', 'last_message'])]
#[UsePolicy(AutomationRulePolicy::class)]
class AutomationRule extends Model
{
    /** @use HasFactory<AutomationRuleFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AutomationRuleKind::class,
            'is_enabled' => 'boolean',
            'settings' => 'array',
            'last_run_at' => 'immutable_datetime',
            'last_status' => AutomationRunStatus::class,
        ];
    }

    /**
     * @return HasMany<AutomationRuleRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRuleRun::class);
    }

    /**
     * @return HasOne<AutomationRuleRun, $this>
     */
    public function latestRun(): HasOne
    {
        return $this->hasOne(AutomationRuleRun::class)->latestOfMany();
    }
}
