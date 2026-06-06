<?php

namespace App\Contracts\Processing;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface ManualWebProcess
{
    public function key(): string;

    /**
     * Build the owner-scoped, safely ordered work query for this browser run.
     *
     * @return Builder<Model>
     */
    public function query(User $user): Builder;

    /**
     * Process one record. Return true when a live run changed the record.
     */
    public function process(User $user, Model $record): bool;

    /**
     * Return a sanitized, user-visible detail row for progress reporting.
     *
     * @return array<string, mixed>
     */
    public function detail(Model $record): array;
}
