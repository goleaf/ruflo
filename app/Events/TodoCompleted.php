<?php

namespace App\Events;

use App\Models\Todo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A task moved from Active to Completed.
 */
final class TodoCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
    ) {}
}
