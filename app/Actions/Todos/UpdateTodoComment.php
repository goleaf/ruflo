<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoCommentData;
use App\Events\TodoCommentUpdated;
use App\Http\Requests\Todos\UpdateTodoCommentRequest;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class UpdateTodoComment
{
    public function handle(User $actor, TodoComment $comment, string $body): TodoComment
    {
        Gate::forUser($actor)->authorize('update', $comment);

        $data = $this->validatedData($body);

        if ($comment->body === $data->body) {
            return $comment->refresh();
        }

        $comment->forceFill([
            'body' => $data->body,
            'edited_at' => now(),
        ])->save();

        TodoCommentUpdated::dispatch($comment);

        return $comment->refresh();
    }

    private function validatedData(string $body): TodoCommentData
    {
        /** @var array{body: string} $validated */
        $validated = Validator::make(
            ['body' => $body],
            UpdateTodoCommentRequest::baseRules(),
            attributes: UpdateTodoCommentRequest::attributeNames(),
        )->validate();

        return TodoCommentData::fromArray($validated);
    }
}
