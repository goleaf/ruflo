<?php

namespace App\Events;

use App\Models\Todo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A task was restored from the archive. Its completion state is unchanged, so
 * it returns to whichever bucket (active or completed) it left.
 */
final class TodoUnarchived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
    ) {
        //
    }
}
