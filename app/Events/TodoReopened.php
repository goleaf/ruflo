<?php

namespace App\Events;

use App\Models\Todo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A completed task moved back to Active.
 */
final class TodoReopened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
    ) {}
}
