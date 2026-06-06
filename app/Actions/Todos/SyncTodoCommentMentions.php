<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\TodoCommentMention;
use App\Models\User;
use App\Notifications\TodoCommentMentionedNotification;
use App\Queries\Todos\TodoMentionCandidateQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SyncTodoCommentMentions
{
    public function __construct(
        private readonly TodoMentionCandidateQuery $mentions,
    ) {}

    /**
     * @param  list<int|string>  $selectedUserIds
     * @return Collection<int, User>
     */
    public function handle(User $actor, TodoComment $comment, array $selectedUserIds = [], bool $notify = true, bool $suppressOwnerNotification = false): Collection
    {
        $todo = $comment->todo()->first();

        if (! $todo instanceof Todo || (int) $todo->user_id !== (int) $comment->user_id) {
            $comment->mentions()->delete();

            return collect();
        }

        $resolvedMentions = $this->mentions->mentionedUsersFor($actor, $todo, $comment->body, $selectedUserIds);
        $resolvedUserIds = $resolvedMentions
            ->pluck('user.id')
            ->map(fn (mixed $userId): int => (int) $userId)
            ->all();

        $existingUserIds = $comment->mentions()
            ->pluck('mentioned_user_id')
            ->map(fn (mixed $userId): int => (int) $userId)
            ->all();

        $newMentionedUsers = collect();

        DB::transaction(function () use ($comment, $existingUserIds, $newMentionedUsers, $resolvedMentions, $resolvedUserIds): void {
            if ($resolvedUserIds === []) {
                $comment->mentions()->delete();

                return;
            }

            $comment->mentions()
                ->whereNotIn('mentioned_user_id', $resolvedUserIds)
                ->delete();

            $resolvedMentions->each(function (array $candidate) use ($comment, $existingUserIds, $newMentionedUsers): void {
                /** @var User $mentionedUser */
                $mentionedUser = $candidate['user'];

                TodoCommentMention::query()->updateOrCreate(
                    [
                        'todo_comment_id' => $comment->id,
                        'mentioned_user_id' => $mentionedUser->id,
                    ],
                    [
                        'user_id' => $comment->user_id,
                        'handle' => $candidate['handle'],
                    ],
                );

                if (! in_array((int) $mentionedUser->id, $existingUserIds, true)) {
                    $newMentionedUsers->push($mentionedUser);
                }
            });
        });

        if ($notify) {
            $this->notifyMentionedUsers($actor, $comment, $newMentionedUsers, $suppressOwnerNotification);
        }

        return $resolvedMentions
            ->pluck('user')
            ->filter(fn (mixed $user): bool => $user instanceof User)
            ->values();
    }

    /**
     * @param  Collection<int, User>  $mentionedUsers
     */
    private function notifyMentionedUsers(User $actor, TodoComment $comment, Collection $mentionedUsers, bool $suppressOwnerNotification): void
    {
        $todo = $comment->todo()->first();

        $mentionedUsers
            ->unique(fn (User $user): int => $user->id)
            ->reject(fn (User $user): bool => (int) $user->id === (int) $actor->id)
            ->reject(fn (User $user): bool => $suppressOwnerNotification
                && $todo instanceof Todo
                && (int) $user->id === (int) $todo->user_id)
            ->each(fn (User $user): mixed => $user->notify(new TodoCommentMentionedNotification($comment)));
    }
}
