<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoCommentMentionPolicy;
use Database\Factories\TodoCommentMentionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'todo_comment_id', 'mentioned_user_id', 'handle'])]
#[UsePolicy(TodoCommentMentionPolicy::class)]
class TodoCommentMention extends Model
{
    /** @use HasFactory<TodoCommentMentionFactory> */
    use BelongsToUser, HasFactory;

    /**
     * The comment containing the mention.
     *
     * @return BelongsTo<TodoComment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(TodoComment::class, 'todo_comment_id')->withTrashed();
    }

    /**
     * The user resolved from the mention token.
     *
     * @return BelongsTo<User, $this>
     */
    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }
}
