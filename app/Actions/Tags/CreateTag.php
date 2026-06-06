<?php

namespace App\Actions\Tags;

use App\Data\Tags\TagData;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Creates a tag for the user, or returns the existing one with the same
 * (normalized) name so labels never fragment into near-duplicates.
 */
final class CreateTag
{
    public function handle(User $user, TagData $data): Tag
    {
        if ($data->name === '') {
            throw ValidationException::withMessages([
                'name' => __('todos.validation.tag_name'),
            ]);
        }

        return $user->tags()->firstOrCreate(
            ['name' => $data->name],
            ['color' => $data->color],
        );
    }
}
