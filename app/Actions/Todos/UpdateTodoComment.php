<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoCommentData;
use App\Events\TodoCommentUpdated;
use App\Http\Requests\Todos\UpdateTodoCommentRequest;
use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class UpdateTodoComment
{
    public function __construct(
        private readonly SyncTodoCommentMentions $mentions,
    ) {}

    /**
     * @param  list<int|string>  $mentionedUserIds
     */
    public function handle(User $actor, TodoComment $comment, string $body, array $mentionedUserIds = []): TodoComment
    {
        Gate::forUser($actor)->authorize('update', $comment);

        $todo = $comment->todo()->firstOrFail();
        $data = $this->validatedData($body);
        $mentionedUserIds = $this->validatedMentionUserIds($actor, $todo, $mentionedUserIds);

        if ($comment->body !== $data->body) {
            $comment->forceFill([
                'body' => $data->body,
                'edited_at' => now(),
            ])->save();

            TodoCommentUpdated::dispatch($comment);
        }

        $this->mentions->handle($actor, $comment->refresh(), $mentionedUserIds);

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

    /**
     * @param  list<int|string>  $mentionedUserIds
     * @return list<int>
     */
    private function validatedMentionUserIds(User $actor, Todo $todo, array $mentionedUserIds): array
    {
        /** @var array{mentioned_user_ids?: list<int|string>} $validated */
        $validated = Validator::make(
            ['mentioned_user_ids' => $mentionedUserIds],
            UpdateTodoCommentRequest::mentionRules($actor, $todo),
            attributes: UpdateTodoCommentRequest::attributeNames(),
        )->validate();

        return collect($validated['mentioned_user_ids'] ?? [])
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
