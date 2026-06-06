<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

final class DailySummaryNotification extends Notification
{
    public function __construct(
        private readonly int $dueCount,
        private readonly int $overdueCount,
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
        return 'daily-summary';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'daily_summary',
            'title' => __('reminders.notifications.daily_summary.title'),
            'message' => __('reminders.notifications.daily_summary.message', [
                'due' => $this->dueCount,
                'overdue' => $this->overdueCount,
            ]),
            'action_url' => route('dashboard'),
            'due_count' => $this->dueCount,
            'overdue_count' => $this->overdueCount,
        ];
    }
}
