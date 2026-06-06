<?php

namespace App\Events;

use App\Models\Todo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A task's editable details were changed. Lifecycle transitions have their own
 * events and do not dispatch this.
 */
final class TodoUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
        /** @var array<string, array{old: mixed, new: mixed}> */
        public array $changes = [],
    ) {
        //
    }
}
