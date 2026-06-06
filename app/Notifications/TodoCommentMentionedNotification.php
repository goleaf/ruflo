<?php

namespace App\Notifications;

use App\Models\Todo;
use App\Models\TodoComment;
use Illuminate\Notifications\Notification;

final class TodoCommentMentionedNotification extends Notification
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
        return 'todo-comment-mentioned';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $todo = $this->comment->todo()->first();
        $author = $this->comment->author()->first();

        return [
            'kind' => 'todo_comment_mentioned',
            'todo_id' => $todo?->id,
            'comment_id' => $this->comment->id,
            'author_id' => $author?->id,
            'title' => __('notifications.comments.mentioned.title'),
            'message' => __('notifications.comments.mentioned.message', [
                'author' => $author?->name ?? __('todos.comments.author.deleted'),
                'task' => $todo instanceof Todo ? $todo->title : __('activity.subjects.deleted'),
            ]),
            'action_url' => $todo instanceof Todo ? route('todos.show', $todo) : route('todos.index'),
        ];
    }
}
