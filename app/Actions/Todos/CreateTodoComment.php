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
    public function __construct(
        private readonly SyncTodoCommentMentions $mentions,
    ) {}

    /**
     * @param  list<int|string>  $mentionedUserIds
     */
    public function handle(User $actor, Todo $todo, string $body, array $mentionedUserIds = []): TodoComment
    {
        Gate::forUser($actor)->authorize('create', [TodoComment::class, $todo]);

        $data = $this->validatedData($body);
        $mentionedUserIds = $this->validatedMentionUserIds($actor, $todo, $mentionedUserIds);

        $comment = $todo->comments()->make([
            'body' => $data->body,
        ]);

        $comment->user()->associate($todo->user_id);
        $comment->author()->associate($actor);
        $comment->save();

        TodoCommentCreated::dispatch($comment);
        $this->notifyOwner($actor, $comment);
        $this->mentions->handle(
            $actor,
            $comment,
            $mentionedUserIds,
            suppressOwnerNotification: (int) $todo->user_id !== (int) $actor->id,
        );

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

    /**
     * @param  list<int|string>  $mentionedUserIds
     * @return list<int>
     */
    private function validatedMentionUserIds(User $actor, Todo $todo, array $mentionedUserIds): array
    {
        /** @var array{mentioned_user_ids?: list<int|string>} $validated */
        $validated = Validator::make(
            ['mentioned_user_ids' => $mentionedUserIds],
            StoreTodoCommentRequest::mentionRules($actor, $todo),
            attributes: StoreTodoCommentRequest::attributeNames(),
        )->validate();

        return collect($validated['mentioned_user_ids'] ?? [])
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
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
