<?php

namespace App\Events;

use App\Models\TodoComment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TodoCommentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TodoComment $comment,
    ) {}
}
