<?php

namespace App\Livewire\Dashboard;

use App\Actions\Reminders\ProcessDueReminders;
use App\Models\User;
use App\Queries\Dashboard\DailyDashboardQuery;
use App\Queries\Dashboard\DailySummaryQuery;
use App\Queries\Dashboard\DashboardFoundationQuery;
use App\Queries\Dashboard\ProjectProgressDashboardQuery;
use App\Support\Charts\BrowserBarChart;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('dashboard.title')]
class Index extends Component
{
    /**
     * @var list<string>
     */
    private const array FOUNDATION_WIDGET_KEYS = [
        'today',
        'overdue',
        'upcoming',
        'priorities',
        'reminders',
        'recurrence',
        'goals',
        'habits',
        'projects',
        'time',
    ];

    /**
     * @var array{matched: int, processed: int, skipped: int, failed: int, remaining: int}|null
     */
    public ?array $reminderRunReport = null;

    #[Session(key: 'dashboard-daily-details-open')]
    public bool $showDailyDetails = true;

    #[Session(key: 'dashboard-foundation-details-open')]
    public bool $showFoundationDetails = true;

    #[Session(key: 'dashboard-foundation-customizer-open')]
    public bool $showFoundationCustomizer = false;

    /**
     * @var list<string>
     */
    #[Session(key: 'dashboard-foundation-widget-order')]
    public array $foundationWidgetOrder = [];

    /**
     * @var list<string>
     */
    #[Session(key: 'dashboard-foundation-hidden-widgets')]
    public array $hiddenFoundationWidgets = [];

    public function mount(ProcessDueReminders $processDueReminders): void
    {
        $this->normalizeFoundationWidgetPreferences();

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

    public function toggleFoundationDetails(): void
    {
        $this->showFoundationDetails = ! $this->showFoundationDetails;
    }

    public function toggleFoundationCustomizer(): void
    {
        $this->showFoundationCustomizer = ! $this->showFoundationCustomizer;
    }

    public function toggleFoundationWidget(string $key): void
    {
        $key = $this->validatedFoundationWidgetKey($key, 'foundationWidgetVisibility');

        $this->resetValidation('foundationWidgetVisibility');

        $hiddenWidgets = $this->normalizedHiddenFoundationWidgets();

        if (in_array($key, $hiddenWidgets, true)) {
            $hiddenWidgets = array_values(array_filter(
                $hiddenWidgets,
                fn (string $hiddenWidget): bool => $hiddenWidget !== $key,
            ));
        } else {
            $hiddenWidgets[] = $key;
        }

        $this->hiddenFoundationWidgets = $hiddenWidgets;
        $this->normalizeFoundationWidgetPreferences();
    }

    public function moveFoundationWidget(string $key, string $direction): void
    {
        $key = $this->validatedFoundationWidgetKey($key, 'foundationWidgetOrder');
        $direction = $this->validatedFoundationWidgetDirection($direction);

        $this->normalizeFoundationWidgetPreferences();
        $this->resetValidation('foundationWidgetOrder');

        $order = $this->foundationWidgetOrder;
        $currentIndex = array_search($key, $order, true);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up'
            ? $currentIndex - 1
            : $currentIndex + 1;

        if (! array_key_exists($targetIndex, $order)) {
            return;
        }

        [$order[$currentIndex], $order[$targetIndex]] = [$order[$targetIndex], $order[$currentIndex]];

        $this->foundationWidgetOrder = array_values($order);
    }

    public function resetFoundationWidgets(): void
    {
        $this->foundationWidgetOrder = self::FOUNDATION_WIDGET_KEYS;
        $this->hiddenFoundationWidgets = [];

        $this->resetValidation('foundationWidgetOrder');
        $this->resetValidation('foundationWidgetVisibility');
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

    /**
     * @return array{today: int, overdue: int, upcoming: int, priority_urgent: int, priority_high: int, priority_normal: int, priority_low: int, reminders_due: int, reminders_pending: int, recurrence_enabled: int, recurrence_paused: int, recurrence_generated: int, goals_open: int, goals_due_soon: int, milestones_open: int, habits_active: int, habits_checked_today: int, projects_active: int, projects_with_active_tasks: int, time_today_seconds: int, time_week_seconds: int, active_timers: int}
     */
    #[Computed]
    public function foundation(): array
    {
        return app(DashboardFoundationQuery::class)->for($this->currentUser());
    }

    /**
     * @return array{
     *     generated_on: string,
     *     projects: list<array{id: int, name: string, color: string, active: int, completed: int, overdue: int, due_soon: int, undated: int, stale: int, total: int, attention: int, completion_percent: int}>,
     *     no_project: array{active: int, overdue: int, due_soon: int, undated: int, stale: int, attention: int},
     *     totals: array{active_projects: int, archived_projects: int, displayed_projects: int, active_tasks: int, completed_tasks: int, total_tasks: int, completion_percent: int, overdue: int, due_soon: int, undated: int, stale: int, no_project_active: int, cleanup_signals: int}
     * }
     */
    #[Computed]
    public function projectProgress(): array
    {
        return app(ProjectProgressDashboardQuery::class)->for($this->currentUser());
    }

    #[Computed]
    public function hasProjectProgress(): bool
    {
        return $this->projectProgress['totals']['active_projects'] > 0
            || $this->projectProgress['totals']['active_tasks'] > 0
            || $this->projectProgress['totals']['completed_tasks'] > 0
            || $this->projectProgress['totals']['no_project_active'] > 0;
    }

    /**
     * @return list<array{key: string, label: string, description: string, value: string, badge: string, color: string, href: string, action: string, chart_value: int, metrics: list<array{key: string, label: string, value: string}>}>
     */
    #[Computed]
    public function foundationWidgets(): array
    {
        $hiddenWidgets = $this->normalizedHiddenFoundationWidgets();

        return array_values(array_filter(
            $this->orderedFoundationWidgets($this->baseFoundationWidgets()),
            fn (array $widget): bool => ! in_array($widget['key'], $hiddenWidgets, true),
        ));
    }

    /**
     * @return list<array{key: string, label: string, description: string, visible: bool, position: int, can_move_up: bool, can_move_down: bool}>
     */
    #[Computed]
    public function foundationWidgetSettings(): array
    {
        $hiddenWidgets = $this->normalizedHiddenFoundationWidgets();
        $widgets = $this->orderedFoundationWidgets($this->baseFoundationWidgets());
        $lastIndex = count($widgets) - 1;

        return array_map(
            fn (array $widget, int $index): array => [
                'key' => $widget['key'],
                'label' => $widget['label'],
                'description' => $widget['description'],
                'visible' => ! in_array($widget['key'], $hiddenWidgets, true),
                'position' => $index + 1,
                'can_move_up' => $index > 0,
                'can_move_down' => $index < $lastIndex,
            ],
            $widgets,
            array_keys($widgets),
        );
    }

    #[Computed]
    public function hasVisibleFoundationWidgets(): bool
    {
        return $this->foundationWidgets !== [];
    }

    /**
     * @return list<array{key: string, label: string, description: string, value: string, badge: string, color: string, href: string, action: string, chart_value: int, metrics: list<array{key: string, label: string, value: string}>}>
     */
    private function baseFoundationWidgets(): array
    {
        $foundation = $this->foundation;
        $importantPriorityCount = $foundation['priority_urgent'] + $foundation['priority_high'];

        return [
            [
                'key' => 'today',
                'label' => __('dashboard.foundation.widgets.today.label'),
                'description' => __('dashboard.foundation.widgets.today.description'),
                'value' => (string) $foundation['today'],
                'badge' => __('dashboard.foundation.badges.tasks'),
                'color' => 'blue',
                'href' => route('todos.today'),
                'action' => __('dashboard.foundation.actions.open_today'),
                'chart_value' => $foundation['today'],
                'metrics' => [
                    ['key' => 'overdue', 'label' => __('dashboard.foundation.metrics.overdue'), 'value' => (string) $foundation['overdue']],
                    ['key' => 'upcoming', 'label' => __('dashboard.foundation.metrics.upcoming'), 'value' => (string) $foundation['upcoming']],
                ],
            ],
            [
                'key' => 'overdue',
                'label' => __('dashboard.foundation.widgets.overdue.label'),
                'description' => __('dashboard.foundation.widgets.overdue.description'),
                'value' => (string) $foundation['overdue'],
                'badge' => __('dashboard.foundation.badges.tasks'),
                'color' => 'rose',
                'href' => route('todos.overdue'),
                'action' => __('dashboard.foundation.actions.open_overdue'),
                'chart_value' => $foundation['overdue'],
                'metrics' => [
                    ['key' => 'today', 'label' => __('dashboard.foundation.metrics.today'), 'value' => (string) $foundation['today']],
                    ['key' => 'upcoming', 'label' => __('dashboard.foundation.metrics.upcoming'), 'value' => (string) $foundation['upcoming']],
                ],
            ],
            [
                'key' => 'upcoming',
                'label' => __('dashboard.foundation.widgets.upcoming.label'),
                'description' => __('dashboard.foundation.widgets.upcoming.description'),
                'value' => (string) $foundation['upcoming'],
                'badge' => __('dashboard.foundation.badges.tasks'),
                'color' => 'cyan',
                'href' => route('todos.upcoming'),
                'action' => __('dashboard.foundation.actions.open_upcoming'),
                'chart_value' => $foundation['upcoming'],
                'metrics' => [
                    ['key' => 'today', 'label' => __('dashboard.foundation.metrics.today'), 'value' => (string) $foundation['today']],
                    ['key' => 'overdue', 'label' => __('dashboard.foundation.metrics.overdue'), 'value' => (string) $foundation['overdue']],
                ],
            ],
            [
                'key' => 'priorities',
                'label' => __('dashboard.foundation.widgets.priorities.label'),
                'description' => __('dashboard.foundation.widgets.priorities.description'),
                'value' => (string) $importantPriorityCount,
                'badge' => __('dashboard.foundation.badges.important'),
                'color' => 'red',
                'href' => route('todos.index', ['sort' => 'priority']),
                'action' => __('dashboard.foundation.actions.review_priorities'),
                'chart_value' => $importantPriorityCount,
                'metrics' => [
                    ['key' => 'urgent', 'label' => __('dashboard.foundation.metrics.urgent'), 'value' => (string) $foundation['priority_urgent']],
                    ['key' => 'high', 'label' => __('dashboard.foundation.metrics.high'), 'value' => (string) $foundation['priority_high']],
                    ['key' => 'normal', 'label' => __('dashboard.foundation.metrics.normal'), 'value' => (string) $foundation['priority_normal']],
                    ['key' => 'low', 'label' => __('dashboard.foundation.metrics.low'), 'value' => (string) $foundation['priority_low']],
                ],
            ],
            [
                'key' => 'reminders',
                'label' => __('dashboard.foundation.widgets.reminders.label'),
                'description' => __('dashboard.foundation.widgets.reminders.description'),
                'value' => (string) $foundation['reminders_due'],
                'badge' => __('dashboard.foundation.badges.due_now'),
                'color' => 'amber',
                'href' => route('todos.reminders'),
                'action' => __('dashboard.foundation.actions.process_reminders'),
                'chart_value' => $foundation['reminders_due'],
                'metrics' => [
                    ['key' => 'pending', 'label' => __('dashboard.foundation.metrics.pending'), 'value' => (string) $foundation['reminders_pending']],
                ],
            ],
            [
                'key' => 'recurrence',
                'label' => __('dashboard.foundation.widgets.recurrence.label'),
                'description' => __('dashboard.foundation.widgets.recurrence.description'),
                'value' => (string) $foundation['recurrence_enabled'],
                'badge' => __('dashboard.foundation.badges.enabled'),
                'color' => 'purple',
                'href' => route('todos.recurring'),
                'action' => __('dashboard.foundation.actions.open_recurrence'),
                'chart_value' => $foundation['recurrence_enabled'],
                'metrics' => [
                    ['key' => 'paused', 'label' => __('dashboard.foundation.metrics.paused'), 'value' => (string) $foundation['recurrence_paused']],
                    ['key' => 'generated', 'label' => __('dashboard.foundation.metrics.generated'), 'value' => (string) $foundation['recurrence_generated']],
                ],
            ],
            [
                'key' => 'goals',
                'label' => __('dashboard.foundation.widgets.goals.label'),
                'description' => __('dashboard.foundation.widgets.goals.description'),
                'value' => (string) $foundation['goals_open'],
                'badge' => __('dashboard.foundation.badges.open'),
                'color' => 'green',
                'href' => route('goals.index'),
                'action' => __('dashboard.foundation.actions.open_goals'),
                'chart_value' => $foundation['goals_open'],
                'metrics' => [
                    ['key' => 'due_soon', 'label' => __('dashboard.foundation.metrics.due_soon'), 'value' => (string) $foundation['goals_due_soon']],
                    ['key' => 'milestones', 'label' => __('dashboard.foundation.metrics.milestones'), 'value' => (string) $foundation['milestones_open']],
                ],
            ],
            [
                'key' => 'habits',
                'label' => __('dashboard.foundation.widgets.habits.label'),
                'description' => __('dashboard.foundation.widgets.habits.description'),
                'value' => (string) $foundation['habits_active'],
                'badge' => __('dashboard.foundation.badges.active'),
                'color' => 'lime',
                'href' => route('habits.index'),
                'action' => __('dashboard.foundation.actions.open_habits'),
                'chart_value' => $foundation['habits_active'],
                'metrics' => [
                    ['key' => 'checked_today', 'label' => __('dashboard.foundation.metrics.checked_today'), 'value' => (string) $foundation['habits_checked_today']],
                ],
            ],
            [
                'key' => 'projects',
                'label' => __('dashboard.foundation.widgets.projects.label'),
                'description' => __('dashboard.foundation.widgets.projects.description'),
                'value' => (string) $foundation['projects_active'],
                'badge' => __('dashboard.foundation.badges.active'),
                'color' => 'indigo',
                'href' => route('todos.index', ['sort' => 'project']),
                'action' => __('dashboard.foundation.actions.open_projects'),
                'chart_value' => $foundation['projects_active'],
                'metrics' => [
                    ['key' => 'with_tasks', 'label' => __('dashboard.foundation.metrics.with_active_tasks'), 'value' => (string) $foundation['projects_with_active_tasks']],
                ],
            ],
            [
                'key' => 'time',
                'label' => __('dashboard.foundation.widgets.time.label'),
                'description' => __('dashboard.foundation.widgets.time.description'),
                'value' => $this->formatDashboardSeconds($foundation['time_today_seconds']),
                'badge' => __('dashboard.foundation.badges.today'),
                'color' => 'teal',
                'href' => route('todos.time'),
                'action' => __('dashboard.foundation.actions.open_time'),
                'chart_value' => max($foundation['active_timers'], intdiv($foundation['time_today_seconds'] + 3599, 3600)),
                'metrics' => [
                    ['key' => 'week', 'label' => __('dashboard.foundation.metrics.this_week'), 'value' => $this->formatDashboardSeconds($foundation['time_week_seconds'])],
                    ['key' => 'timers', 'label' => __('dashboard.foundation.metrics.active_timers'), 'value' => (string) $foundation['active_timers']],
                ],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: int, display_value: string, percent: int, summary: string}>
     */
    #[Computed]
    public function foundationChart(): array
    {
        $widgets = $this->foundationWidgets;

        return BrowserBarChart::rows(array_map(fn (array $widget): array => [
            'key' => $widget['key'],
            'label' => $widget['label'],
            'value' => $widget['chart_value'],
            'display_value' => (string) $widget['chart_value'],
        ], $widgets), 'dashboard.foundation.chart.item_summary');
    }

    public function formatDailySummarySeconds(int $seconds): string
    {
        return $this->formatDashboardSeconds($seconds);
    }

    public function formatDashboardSeconds(int $seconds): string
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

    #[Computed]
    public function foundationChartAria(): string
    {
        return __('dashboard.foundation.chart.aria', [
            'count' => count($this->foundationChart),
        ]);
    }

    #[Computed]
    public function projectProgressAria(): string
    {
        return __('dashboard.projects.aria', [
            'projects' => $this->projectProgress['totals']['active_projects'],
            'active' => $this->projectProgress['totals']['active_tasks'],
            'completed' => $this->projectProgress['totals']['completed_tasks'],
            'cleanup' => $this->projectProgress['totals']['cleanup_signals'],
        ]);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @param  list<array{key: string, label: string, description: string, value: string, badge: string, color: string, href: string, action: string, chart_value: int, metrics: list<array{key: string, label: string, value: string}>}>  $widgets
     * @return list<array{key: string, label: string, description: string, value: string, badge: string, color: string, href: string, action: string, chart_value: int, metrics: list<array{key: string, label: string, value: string}>}>
     */
    private function orderedFoundationWidgets(array $widgets): array
    {
        $widgetsByKey = [];

        foreach ($widgets as $widget) {
            $widgetsByKey[$widget['key']] = $widget;
        }

        return array_values(array_map(
            fn (string $key): array => $widgetsByKey[$key],
            $this->normalizedFoundationWidgetOrder(),
        ));
    }

    private function validatedFoundationWidgetKey(string $key, string $errorBagKey): string
    {
        if (! in_array($key, self::FOUNDATION_WIDGET_KEYS, true)) {
            throw ValidationException::withMessages([
                $errorBagKey => __('dashboard.foundation.customize.validation.invalid_widget'),
            ]);
        }

        return $key;
    }

    private function validatedFoundationWidgetDirection(string $direction): string
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            throw ValidationException::withMessages([
                'foundationWidgetOrder' => __('dashboard.foundation.customize.validation.invalid_direction'),
            ]);
        }

        return $direction;
    }

    private function normalizeFoundationWidgetPreferences(): void
    {
        $this->foundationWidgetOrder = $this->normalizedFoundationWidgetOrder();
        $this->hiddenFoundationWidgets = $this->normalizedHiddenFoundationWidgets();
    }

    /**
     * @return list<string>
     */
    private function normalizedFoundationWidgetOrder(): array
    {
        $order = [];

        foreach ($this->foundationWidgetOrder as $key) {
            if (is_string($key) && in_array($key, self::FOUNDATION_WIDGET_KEYS, true) && ! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        foreach (self::FOUNDATION_WIDGET_KEYS as $key) {
            if (! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        return $order;
    }

    /**
     * @return list<string>
     */
    private function normalizedHiddenFoundationWidgets(): array
    {
        $hiddenWidgets = [];

        foreach ($this->hiddenFoundationWidgets as $key) {
            if (is_string($key) && in_array($key, self::FOUNDATION_WIDGET_KEYS, true) && ! in_array($key, $hiddenWidgets, true)) {
                $hiddenWidgets[] = $key;
            }
        }

        return $hiddenWidgets;
    }
}
