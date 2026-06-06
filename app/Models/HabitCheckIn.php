<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\HabitCheckInPolicy;
use Database\Factories\HabitCheckInFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['occurred_on', 'checked_at'])]
#[UsePolicy(HabitCheckInPolicy::class)]
class HabitCheckIn extends Model
{
    /** @use HasFactory<HabitCheckInFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return BelongsTo<Habit, $this>
     */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_on' => 'immutable_date',
            'checked_at' => 'immutable_datetime',
        ];
    }
}
