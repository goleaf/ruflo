<?php

namespace App\Events;

use App\Models\Todo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TodoRestoredFromTrash
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
    ) {
        //
    }
}
