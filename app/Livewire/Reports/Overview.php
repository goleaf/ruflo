<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Queries\Reports\ReportsOverviewQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('reports.pages.overview.title')]
class Overview extends Component
{
    #[Session(key: 'reports-overview-details-open')]
    public bool $showDetails = true;

    #[Session(key: 'reports-overview-trends-open')]
    public bool $showTrends = true;

    public function render(): View
    {
        return view('livewire.reports.overview');
    }

    public function toggleDetails(): void
    {
        $this->showDetails = ! $this->showDetails;
    }

    public function toggleTrends(): void
    {
        $this->showTrends = ! $this->showTrends;
    }

    /**
     * @return array{
     *     generated_on: string,
     *     productivity: array{active: int, completed_this_week: int, completed_previous_week: int, completion_delta: int, due_today: int, due_next_7_days: int, inbox: int, completion_percent: int},
     *     overdue: array{total: int, high_priority: int, urgent_priority: int, oldest_age_days: int, one_to_three_days: int, four_to_seven_days: int, eight_plus_days: int},
     *     habits: array{active: int, checked_today: int, check_ins_this_week: int, check_ins_previous_week: int, weekly_delta: int, weekly_distinct_habits: int, adherence_percent: int},
     *     projects: array{active: int, with_active_tasks: int, completed_tasks_this_week: int, overdue_tasks: int, no_project_active: int, top: list<array{id: int, name: string, color: string, active: int, completed: int, overdue: int, completion_percent: int}>},
     *     time: array{today_seconds: int, week_seconds: int, previous_week_seconds: int, delta_seconds: int, active_timers: int},
     *     charts: array{
     *         productivity: list<array{key: string, label: string, value: int, display_value: string, percent: int, summary: string}>,
     *         overdue: list<array{key: string, label: string, value: int, display_value: string, percent: int, summary: string}>,
     *         habits: list<array{key: string, label: string, value: int, display_value: string, percent: int, summary: string}>,
     *         time: list<array{key: string, label: string, value: int, display_value: string, percent: int, summary: string}>
     *     }
     * }
     */
    #[Computed]
    public function report(): array
    {
        return app(ReportsOverviewQuery::class)->for($this->currentUser());
    }

    /**
     * @return list<array{key: string, label: string, description: string, value: string, badge: string, color: string, href: string, action: string, metrics: list<array{key: string, label: string, value: string}>}>
     */
    #[Computed]
    public function widgets(): array
    {
        $report = $this->report;

        return [
            [
                'key' => 'productivity',
                'label' => __('reports.widgets.productivity.label'),
                'description' => __('reports.widgets.productivity.description'),
                'value' => (string) $report['productivity']['completed_this_week'],
                'badge' => __('reports.badges.this_week'),
                'color' => 'blue',
                'href' => route('todos.index'),
                'action' => __('reports.actions.open_tasks'),
                'metrics' => [
                    ['key' => 'active', 'label' => __('reports.metrics.active'), 'value' => (string) $report['productivity']['active']],
                    ['key' => 'due_today', 'label' => __('reports.metrics.due_today'), 'value' => (string) $report['productivity']['due_today']],
                    ['key' => 'due_next_7_days', 'label' => __('reports.metrics.due_next_7_days'), 'value' => (string) $report['productivity']['due_next_7_days']],
                    ['key' => 'completion_percent', 'label' => __('reports.metrics.completion_percent'), 'value' => __('reports.values.percent', ['percent' => $report['productivity']['completion_percent']])],
                ],
            ],
            [
                'key' => 'habits',
                'label' => __('reports.widgets.habits.label'),
                'description' => __('reports.widgets.habits.description'),
                'value' => __('reports.values.percent', ['percent' => $report['habits']['adherence_percent']]),
                'badge' => __('reports.badges.adherence'),
                'color' => 'lime',
                'href' => route('habits.index'),
                'action' => __('reports.actions.open_habits'),
                'metrics' => [
                    ['key' => 'active', 'label' => __('reports.metrics.active'), 'value' => (string) $report['habits']['active']],
                    ['key' => 'checked_today', 'label' => __('reports.metrics.checked_today'), 'value' => (string) $report['habits']['checked_today']],
                    ['key' => 'this_week', 'label' => __('reports.metrics.this_week'), 'value' => (string) $report['habits']['check_ins_this_week']],
                    ['key' => 'previous_week', 'label' => __('reports.metrics.previous_week'), 'value' => (string) $report['habits']['check_ins_previous_week']],
                ],
            ],
            [
                'key' => 'projects',
                'label' => __('reports.widgets.projects.label'),
                'description' => __('reports.widgets.projects.description'),
                'value' => (string) $report['projects']['active'],
                'badge' => __('reports.badges.active'),
                'color' => 'indigo',
                'href' => route('todos.index', ['sort' => 'project']),
                'action' => __('reports.actions.open_projects'),
                'metrics' => [
                    ['key' => 'with_tasks', 'label' => __('reports.metrics.with_active_tasks'), 'value' => (string) $report['projects']['with_active_tasks']],
                    ['key' => 'completed', 'label' => __('reports.metrics.completed_this_week'), 'value' => (string) $report['projects']['completed_tasks_this_week']],
                    ['key' => 'overdue', 'label' => __('reports.metrics.overdue'), 'value' => (string) $report['projects']['overdue_tasks']],
                    ['key' => 'no_project', 'label' => __('reports.metrics.no_project'), 'value' => (string) $report['projects']['no_project_active']],
                ],
            ],
            [
                'key' => 'time',
                'label' => __('reports.widgets.time.label'),
                'description' => __('reports.widgets.time.description'),
                'value' => $this->formatSeconds($report['time']['week_seconds']),
                'badge' => __('reports.badges.this_week'),
                'color' => 'teal',
                'href' => route('todos.time'),
                'action' => __('reports.actions.open_time'),
                'metrics' => [
                    ['key' => 'today', 'label' => __('reports.metrics.today'), 'value' => $this->formatSeconds($report['time']['today_seconds'])],
                    ['key' => 'previous_week', 'label' => __('reports.metrics.previous_week'), 'value' => $this->formatSeconds($report['time']['previous_week_seconds'])],
                    ['key' => 'delta', 'label' => __('reports.metrics.delta'), 'value' => $this->formatSignedSeconds($report['time']['delta_seconds'])],
                    ['key' => 'active_timers', 'label' => __('reports.metrics.active_timers'), 'value' => (string) $report['time']['active_timers']],
                ],
            ],
            [
                'key' => 'overdue',
                'label' => __('reports.widgets.overdue.label'),
                'description' => __('reports.widgets.overdue.description'),
                'value' => (string) $report['overdue']['total'],
                'badge' => __('reports.badges.needs_review'),
                'color' => $report['overdue']['total'] > 0 ? 'rose' : 'lime',
                'href' => route('todos.overdue'),
                'action' => __('reports.actions.open_overdue'),
                'metrics' => [
                    ['key' => 'urgent', 'label' => __('reports.metrics.urgent'), 'value' => (string) $report['overdue']['urgent_priority']],
                    ['key' => 'high', 'label' => __('reports.metrics.high'), 'value' => (string) $report['overdue']['high_priority']],
                    ['key' => 'oldest', 'label' => __('reports.metrics.oldest_age_days'), 'value' => __('reports.values.days', ['days' => $report['overdue']['oldest_age_days']])],
                    ['key' => 'eight_plus', 'label' => __('reports.metrics.eight_plus_days'), 'value' => (string) $report['overdue']['eight_plus_days']],
                ],
            ],
        ];
    }

    #[Computed]
    public function hasReportData(): bool
    {
        return collect($this->widgets)
            ->contains(fn (array $widget): bool => $widget['value'] !== '0' && $widget['value'] !== __('reports.values.percent', ['percent' => 0]) && $widget['value'] !== $this->formatSeconds(0));
    }

    /**
     * @return list<array{key: string, label: string, description: string, aria: string, rows: list<array{key: string, label: string, value: int, display_value: string, percent: int, summary: string}>}>
     */
    #[Computed]
    public function chartSections(): array
    {
        return [
            [
                'key' => 'productivity',
                'label' => __('reports.charts.productivity.label'),
                'description' => __('reports.charts.productivity.description'),
                'aria' => $this->productivityChartAria,
                'rows' => $this->report['charts']['productivity'],
            ],
            [
                'key' => 'overdue',
                'label' => __('reports.charts.overdue.label'),
                'description' => __('reports.charts.overdue.description'),
                'aria' => $this->overdueChartAria,
                'rows' => $this->report['charts']['overdue'],
            ],
            [
                'key' => 'habits',
                'label' => __('reports.charts.habits.label'),
                'description' => __('reports.charts.habits.description'),
                'aria' => $this->habitChartAria,
                'rows' => $this->report['charts']['habits'],
            ],
            [
                'key' => 'time',
                'label' => __('reports.charts.time.label'),
                'description' => __('reports.charts.time.description'),
                'aria' => $this->timeChartAria,
                'rows' => $this->report['charts']['time'],
            ],
        ];
    }

    #[Computed]
    public function productivityChartAria(): string
    {
        return __('reports.charts.aria.productivity', [
            'active' => $this->report['productivity']['active'],
            'completed' => $this->report['productivity']['completed_this_week'],
        ]);
    }

    #[Computed]
    public function overdueChartAria(): string
    {
        return __('reports.charts.aria.overdue', [
            'total' => $this->report['overdue']['total'],
            'oldest' => $this->report['overdue']['oldest_age_days'],
        ]);
    }

    #[Computed]
    public function habitChartAria(): string
    {
        return __('reports.charts.aria.habits', [
            'active' => $this->report['habits']['active'],
            'check_ins' => $this->report['habits']['check_ins_this_week'],
        ]);
    }

    #[Computed]
    public function timeChartAria(): string
    {
        return __('reports.charts.aria.time', [
            'week' => $this->formatSeconds($this->report['time']['week_seconds']),
            'previous' => $this->formatSeconds($this->report['time']['previous_week_seconds']),
        ]);
    }

    public function formatSeconds(int $seconds): string
    {
        $normalizedSeconds = max(0, $seconds);
        $hours = intdiv($normalizedSeconds, 3600);
        $minutes = intdiv($normalizedSeconds % 3600, 60);

        if ($hours > 0) {
            return __('reports.values.hours_minutes', [
                'hours' => $hours,
                'minutes' => $minutes,
            ]);
        }

        if ($minutes < 1) {
            return __('reports.values.zero_minutes');
        }

        return __('reports.values.minutes', ['minutes' => $minutes]);
    }

    public function formatSignedSeconds(int $seconds): string
    {
        if ($seconds === 0) {
            return $this->formatSeconds(0);
        }

        $prefix = $seconds > 0 ? '+' : '-';

        return $prefix.$this->formatSeconds(abs($seconds));
    }

    public function signedCount(int $count): string
    {
        if ($count === 0) {
            return '0';
        }

        return ($count > 0 ? '+' : '').(string) $count;
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
