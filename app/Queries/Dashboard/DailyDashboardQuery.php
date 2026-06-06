<?php

namespace App\Queries\Dashboard;

use App\Enums\ReminderStatus;
use App\Enums\TimeEntryStatus;
use App\Models\Reminder;
use App\Models\TimeEntry;
use App\Models\User;
use App\Queries\Notifications\NotificationInboxQuery;
use App\Queries\Todos\TodoListQuery;

final class DailyDashboardQuery
{
    public function __construct(
        private readonly TodoListQuery $todos,
        private readonly NotificationInboxQuery $notifications,
    ) {}

    /**
     * Browser-rendered daily summary for the user's private workspace.
     *
     * @return array{
     *     date: string,
     *     attention_total: int,
     *     active_total: int,
     *     scheduled_total: int,
     *     schedule_coverage_percent: int,
     *     due_today: int,
     *     overdue: int,
     *     due_soon: int,
     *     unplanned: int,
     *     blocked: int,
     *     due_reminders: int,
     *     pending_reminders: int,
     *     unread_notifications: int,
     *     time_today_seconds: int,
     *     active_timer_count: int
     * }
     */
    public function for(User $user): array
    {
        $today = today()->toDateString();
        $tasks = $this->todos->dashboardTaskCountsFor($user);

        $reminders = Reminder::query()
            ->ownedBy($user)
            ->selectRaw('sum(case when status = ? and remind_at <= ? then 1 else 0 end) as due_count', [ReminderStatus::Pending->value, now()->toDateTimeString()])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as pending_count', [ReminderStatus::Pending->value])
            ->first();

        $time = TimeEntry::query()
            ->ownedBy($user)
            ->selectRaw('sum(case when status = ? and date(entry_date) = ? then duration_seconds else 0 end) as today_seconds', [TimeEntryStatus::Completed->value, $today])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as active_timer_count', [TimeEntryStatus::Running->value])
            ->first();

        $activeTotal = $tasks['active'];
        $scheduledTotal = $tasks['scheduled'];
        $scheduleCoveragePercent = $activeTotal === 0
            ? 100
            : (int) round(($scheduledTotal / $activeTotal) * 100);
        $dueToday = $tasks['today'];
        $overdue = $tasks['overdue'];
        $blocked = $this->todos->blockedFor($user)->count();
        $dueReminders = (int) ($reminders->due_count ?? 0);
        $unreadNotifications = $this->notifications->unreadCountFor($user);

        $attentionTotal = $dueToday
            + $overdue
            + $blocked
            + $dueReminders
            + $unreadNotifications;

        return [
            'date' => $today,
            'attention_total' => $attentionTotal,
            'active_total' => $activeTotal,
            'scheduled_total' => $scheduledTotal,
            'schedule_coverage_percent' => $scheduleCoveragePercent,
            'due_today' => $dueToday,
            'overdue' => $overdue,
            'due_soon' => $tasks['due_soon'],
            'unplanned' => $tasks['unplanned'],
            'blocked' => $blocked,
            'due_reminders' => $dueReminders,
            'pending_reminders' => (int) ($reminders->pending_count ?? 0),
            'unread_notifications' => $unreadNotifications,
            'time_today_seconds' => (int) ($time->today_seconds ?? 0),
            'active_timer_count' => (int) ($time->active_timer_count ?? 0),
        ];
    }
}
