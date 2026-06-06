<?php

namespace App\Events;

use App\Models\Todo;
use App\Models\TodoChecklistItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A contained checklist row changed on a task.
 *
 * Future activity-history and notification features can listen to this event
 * without making checklist actions depend on those later domains.
 */
final class TodoChecklistChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
        public ?TodoChecklistItem $item,
        public string $change,
    ) {}
}
