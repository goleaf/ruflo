<?php

namespace App\Livewire\Dashboard;

use App\Actions\Reminders\ProcessDueReminders;
use App\Models\User;
use App\Queries\Dashboard\DailyDashboardQuery;
use App\Queries\Dashboard\DailySummaryQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('dashboard.title')]
class Index extends Component
{
    /**
     * @var array{matched: int, processed: int, skipped: int, failed: int, remaining: int}|null
     */
    public ?array $reminderRunReport = null;

    #[Session(key: 'dashboard-daily-details-open')]
    public bool $showDailyDetails = true;

    public function mount(ProcessDueReminders $processDueReminders): void
    {
        $result = $processDueReminders->handle($this->currentUser());

        if ($result->changedCount() > 0 || $result->failedCount > 0 || $result->remainingCount > 0) {
            $this->reminderRunReport = [
                'matched' => $result->matchedCount,
                'processed' => $result->processedCount,
                'skipped' => $result->skippedCount,
                'failed' => $result->failedCount,
                'remaining' => $result->remainingCount,
            ];
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.index');
    }

    public function toggleDailyDetails(): void
    {
        $this->showDailyDetails = ! $this->showDailyDetails;
    }

    /**
     * @return array{active: int, overdue: int, completed: int, archived: int, trash: int, projects: int, tags: int, goals: int, milestones: int, habits: int, habit_check_ins: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(DailySummaryQuery::class)->for($this->currentUser());
    }

    /**
     * @return array{date: string, attention_total: int, active_total: int, scheduled_total: int, schedule_coverage_percent: int, due_today: int, overdue: int, due_soon: int, unplanned: int, blocked: int, due_reminders: int, pending_reminders: int, unread_notifications: int, time_today_seconds: int, active_timer_count: int}
     */
    #[Computed]
    public function dailySummary(): array
    {
        return app(DailyDashboardQuery::class)->for($this->currentUser());
    }

    public function formatDailySummarySeconds(int $seconds): string
    {
        $normalizedSeconds = max(0, $seconds);
        $hours = intdiv($normalizedSeconds, 3600);
        $minutes = intdiv($normalizedSeconds % 3600, 60);

        if ($hours > 0) {
            return __('dashboard.daily.time_hours_minutes', [
                'hours' => $hours,
                'minutes' => $minutes,
            ]);
        }

        if ($minutes < 1) {
            return __('dashboard.daily.time_less_than_minute');
        }

        return __('dashboard.daily.time_minutes', ['minutes' => $minutes]);
    }

    #[Computed]
    public function hasDailyWork(): bool
    {
        return $this->dailySummary['attention_total'] > 0
            || $this->dailySummary['due_soon'] > 0
            || $this->dailySummary['unplanned'] > 0
            || $this->dailySummary['pending_reminders'] > 0
            || $this->dailySummary['time_today_seconds'] > 0
            || $this->dailySummary['active_timer_count'] > 0;
    }

    #[Computed]
    public function dailySummaryAria(): string
    {
        return __('dashboard.daily.schedule_coverage.aria', [
            'percent' => $this->dailySummary['schedule_coverage_percent'],
            'scheduled' => $this->dailySummary['scheduled_total'],
            'active' => $this->dailySummary['active_total'],
        ]);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
