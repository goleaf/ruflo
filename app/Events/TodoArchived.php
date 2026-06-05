<?php

namespace App\Events;

use App\Models\Todo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A task was archived (hidden from active/completed views, not deleted).
 */
final class TodoArchived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
    ) {
        //
    }
}
