<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoCommentData;
use App\Events\TodoCommentCreated;
use App\Http\Requests\Todos\StoreTodoCommentRequest;
use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use App\Notifications\TodoCommentAddedNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CreateTodoComment
{
    public function handle(User $actor, Todo $todo, string $body): TodoComment
    {
        Gate::forUser($actor)->authorize('create', [TodoComment::class, $todo]);

        $data = $this->validatedData($body);

        $comment = $todo->comments()->make([
            'body' => $data->body,
        ]);

        $comment->user()->associate($todo->user_id);
        $comment->author()->associate($actor);
        $comment->save();

        TodoCommentCreated::dispatch($comment);
        $this->notifyOwner($actor, $comment);

        return $comment;
    }

    private function validatedData(string $body): TodoCommentData
    {
        /** @var array{body: string} $validated */
        $validated = Validator::make(
            ['body' => $body],
            StoreTodoCommentRequest::baseRules(),
            attributes: StoreTodoCommentRequest::attributeNames(),
        )->validate();

        return TodoCommentData::fromArray($validated);
    }

    private function notifyOwner(User $actor, TodoComment $comment): void
    {
        $todo = $comment->todo;

        if (! $todo instanceof Todo || (int) $todo->user_id === (int) $actor->id) {
            return;
        }

        User::query()
            ->whereKey($todo->user_id)
            ->first()
            ?->notify(new TodoCommentAddedNotification($comment));
    }
}
