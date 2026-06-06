<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\ActivityRecordPolicy;
use Database\Factories\ActivityRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'actor_id', 'event', 'subject_type', 'subject_id', 'subject_title', 'metadata', 'occurred_at'])]
#[UsePolicy(ActivityRecordPolicy::class)]
class ActivityRecord extends Model
{
    /** @use HasFactory<ActivityRecordFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /**
     * The user who performed the action. Currently this is the owner until
     * collaboration introduces distinct workspace actors.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * The private object the activity is about, when it still exists.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
