<?php

namespace App\Actions\Tags;

use App\Models\Tag;

/**
 * Deletes a tag. The pivot rows are removed by the cascading foreign key, so
 * tasks simply lose the label; the tasks themselves are untouched.
 */
final class DeleteTag
{
    public function handle(Tag $tag): void
    {
        $tag->delete();
    }
}
