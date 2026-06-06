<?php

namespace App\Queries\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationInboxQuery
{
    /**
     * @return Builder<DatabaseNotification>
     */
    public function for(User $user, string $filter = 'all'): Builder
    {
        $query = DatabaseNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->latest('created_at')
            ->latest('id');

        return match ($filter) {
            'unread' => $query->whereNull('read_at'),
            'read' => $query->whereNotNull('read_at'),
            default => $query,
        };
    }

    public function findFor(User $user, string $notificationId): DatabaseNotification
    {
        return $this->for($user)->whereKey($notificationId)->firstOrFail();
    }

    public function unreadCountFor(User $user): int
    {
        return (int) $this->for($user, 'unread')->count();
    }

    /**
     * @return array{all: int, unread: int, read: int}
     */
    public function summaryFor(User $user): array
    {
        return [
            'all' => (int) $this->for($user)->count(),
            'unread' => (int) $this->for($user, 'unread')->count(),
            'read' => (int) $this->for($user, 'read')->count(),
        ];
    }
}
