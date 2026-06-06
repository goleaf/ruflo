<?php

namespace App\Notifications;

use App\Models\TodoComment;
use Illuminate\Notifications\Notification;

final class TodoCommentAddedNotification extends Notification
{
    public function __construct(
        private readonly TodoComment $comment,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'todo-comment-added';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $todo = $this->comment->todo()->first();
        $author = $this->comment->author()->first();

        return [
            'kind' => 'todo_comment_added',
            'todo_id' => $todo?->id,
            'comment_id' => $this->comment->id,
            'author_id' => $author?->id,
            'title' => __('notifications.comments.created.title'),
            'message' => __('notifications.comments.created.message', [
                'author' => $author?->name ?? __('todos.comments.author.deleted'),
                'task' => $todo?->title ?? __('activity.subjects.deleted'),
            ]),
            'action_url' => $todo === null ? route('todos.index') : route('todos.show', $todo),
        ];
    }
}
