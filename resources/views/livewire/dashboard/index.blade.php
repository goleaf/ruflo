<x-ui.page-container gap="gap-8">
    <x-ui.page-header :title="__('dashboard.heading')" :description="__('dashboard.description')" />

    @if ($reminderRunReport !== null)
        <flux:callout icon="bell" variant="secondary" data-test="dashboard-reminder-run-report">
            <flux:callout.heading>{{ __('reminders.processing.report_heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('reminders.processing.report', $reminderRunReport) }}</flux:callout.text>
        </flux:callout>
    @endif

    <flux:card class="space-y-6" data-test="dashboard-daily-summary" aria-labelledby="dashboard-daily-heading" wire:loading.class="opacity-70" wire:loading.attr="aria-busy">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="max-w-2xl">
                <flux:subheading>{{ __('dashboard.daily.label') }}</flux:subheading>
                <flux:heading id="dashboard-daily-heading" size="xl" class="mt-1">{{ __('dashboard.daily.heading') }}</flux:heading>
                <flux:text class="mt-2">{{ __('dashboard.daily.description') }}</flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:badge color="{{ $this->dailySummary['attention_total'] > 0 ? 'amber' : 'lime' }}" class="w-fit">
                    {{ __('dashboard.daily.date_label', ['date' => $this->dailySummary['date']]) }}
                </flux:badge>

                <flux:button type="button" size="sm" variant="ghost" :icon="$showDailyDetails ? 'eye-slash' : 'eye'" wire:click="toggleDailyDetails" wire:loading.attr="disabled" data-test="dashboard-daily-settings">
                    {{ $showDailyDetails ? __('dashboard.daily.settings.compact') : __('dashboard.daily.settings.details') }}
                </flux:button>
            </div>
        </div>

        @if (! $this->hasDailyWork)
            <flux:callout icon="check-circle" variant="secondary" data-test="dashboard-daily-clear-state">
                <flux:callout.heading>{{ __('dashboard.daily.clear_heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('dashboard.daily.clear_description') }}</flux:callout.text>
            </flux:callout>
        @elseif ($this->dailySummary['attention_total'] > 0)
            <flux:callout icon="exclamation-triangle" variant="secondary" data-test="dashboard-daily-attention-state">
                <flux:callout.heading>{{ __('dashboard.daily.attention_heading', ['count' => $this->dailySummary['attention_total']]) }}</flux:callout.heading>
                <flux:callout.text>{{ __('dashboard.daily.attention_description') }}</flux:callout.text>
            </flux:callout>
        @else
            <flux:callout icon="calendar" variant="secondary" data-test="dashboard-daily-planned-state">
                <flux:callout.heading>{{ __('dashboard.daily.planned_heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('dashboard.daily.planned_description') }}</flux:callout.text>
            </flux:callout>
        @endif

        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-zinc-200 bg-zinc-200 dark:border-white/10 dark:bg-white/10 sm:grid-cols-4" aria-label="{{ __('dashboard.daily.stats_label') }}">
            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-due-today">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.due_today') }}</div>
                <div class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $this->dailySummary['due_today'] }}</div>
            </div>

            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-overdue">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.overdue') }}</div>
                <div class="mt-3 text-2xl font-semibold text-red-700 dark:text-red-300">{{ $this->dailySummary['overdue'] }}</div>
            </div>

            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-due-soon">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.due_soon') }}</div>
                <div class="mt-3 text-2xl font-semibold text-sky-700 dark:text-sky-300">{{ $this->dailySummary['due_soon'] }}</div>
            </div>

            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-unplanned">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.unplanned') }}</div>
                <div class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $this->dailySummary['unplanned'] }}</div>
            </div>

            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-blocked">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.blocked') }}</div>
                <div class="mt-3 text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $this->dailySummary['blocked'] }}</div>
            </div>

            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-reminders">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.due_reminders') }}</div>
                <div class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $this->dailySummary['due_reminders'] }}</div>
            </div>

            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-notifications">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.unread_notifications') }}</div>
                <div class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $this->dailySummary['unread_notifications'] }}</div>
            </div>

            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-time">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.time_today') }}</div>
                <div class="mt-3 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->formatDailySummarySeconds($this->dailySummary['time_today_seconds']) }}</div>
            </div>

            <div class="min-h-24 bg-white p-3 dark:bg-zinc-950" data-test="dashboard-daily-stat-active-timers">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.daily.stats.active_timers') }}</div>
                <div class="mt-3 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->dailySummary['active_timer_count'] }}</div>
            </div>
        </div>

        <div class="space-y-2" role="group" aria-label="{{ $this->dailySummaryAria }}" data-test="dashboard-daily-schedule-coverage">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:subheading>{{ __('dashboard.daily.schedule_coverage.label') }}</flux:subheading>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('dashboard.daily.schedule_coverage.percent', ['percent' => $this->dailySummary['schedule_coverage_percent']]) }}
                </span>
            </div>
            <flux:progress :value="$this->dailySummary['schedule_coverage_percent']" color="blue" aria-label="{{ $this->dailySummaryAria }}" />
            <flux:text size="sm">{{ __('dashboard.daily.schedule_coverage.description', ['scheduled' => $this->dailySummary['scheduled_total'], 'active' => $this->dailySummary['active_total']]) }}</flux:text>
        </div>

        @if ($showDailyDetails)
            <div class="grid gap-3 md:grid-cols-3" data-test="dashboard-daily-details">
                <div class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <flux:subheading>{{ __('dashboard.daily.details.planning') }}</flux:subheading>
                    <flux:text>{{ __('dashboard.daily.details.planning_summary', ['unplanned' => $this->dailySummary['unplanned'], 'blocked' => $this->dailySummary['blocked']]) }}</flux:text>
                </div>

                <div class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <flux:subheading>{{ __('dashboard.daily.details.reminders') }}</flux:subheading>
                    <flux:text>{{ __('dashboard.daily.details.reminders_summary', ['due' => $this->dailySummary['due_reminders'], 'pending' => $this->dailySummary['pending_reminders']]) }}</flux:text>
                </div>

                <div class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <flux:subheading>{{ __('dashboard.daily.details.signals') }}</flux:subheading>
                    <flux:text>{{ __('dashboard.daily.details.signals_summary', ['unread' => $this->dailySummary['unread_notifications'], 'timers' => $this->dailySummary['active_timer_count']]) }}</flux:text>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap gap-2" aria-label="{{ __('dashboard.daily.actions_label') }}">
            <flux:button href="{{ route('todos.today') }}" wire:navigate icon="calendar" size="sm" variant="primary" data-test="dashboard-daily-action-today">{{ __('dashboard.daily.actions.today') }}</flux:button>
            <flux:button href="{{ route('todos.overdue') }}" wire:navigate icon="exclamation-triangle" size="sm" data-test="dashboard-daily-action-overdue">{{ __('dashboard.daily.actions.overdue') }}</flux:button>
            <flux:button href="{{ route('todos.blocked') }}" wire:navigate icon="exclamation-triangle" size="sm" data-test="dashboard-daily-action-blocked">{{ __('dashboard.daily.actions.blocked') }}</flux:button>
            <flux:button href="{{ route('todos.reminders') }}" wire:navigate icon="bell" size="sm" data-test="dashboard-daily-action-reminders">{{ __('dashboard.daily.actions.reminders') }}</flux:button>
            <flux:button href="{{ route('notifications.inbox') }}" wire:navigate icon="inbox" size="sm" data-test="dashboard-daily-action-notifications">{{ __('dashboard.daily.actions.notifications') }}</flux:button>
            <flux:button href="{{ route('todos.time') }}" wire:navigate icon="clock" size="sm" data-test="dashboard-daily-action-time">{{ __('dashboard.daily.actions.time') }}</flux:button>
        </div>
    </flux:card>

    <flux:card class="space-y-5" data-test="dashboard-foundation-widgets" aria-labelledby="dashboard-foundation-heading" wire:loading.class="opacity-70" wire:loading.attr="aria-busy">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 max-w-2xl space-y-1">
                <flux:subheading>{{ __('dashboard.foundation.label') }}</flux:subheading>
                <flux:heading id="dashboard-foundation-heading" size="lg">{{ __('dashboard.foundation.heading') }}</flux:heading>
                <flux:text>{{ __('dashboard.foundation.description') }}</flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                <flux:button type="button" size="sm" variant="ghost" :icon="$showFoundationDetails ? 'eye-slash' : 'eye'" wire:click="toggleFoundationDetails" wire:loading.attr="disabled" data-test="dashboard-foundation-settings">
                    {{ $showFoundationDetails ? __('dashboard.foundation.settings.compact') : __('dashboard.foundation.settings.details') }}
                </flux:button>

                <flux:button type="button" size="sm" variant="ghost" icon="adjustments-horizontal" wire:click="toggleFoundationCustomizer" wire:loading.attr="disabled" data-test="dashboard-foundation-customizer-toggle">
                    {{ $showFoundationCustomizer ? __('dashboard.foundation.customize.close') : __('dashboard.foundation.customize.open') }}
                </flux:button>
            </div>
        </div>

        @if ($showFoundationCustomizer)
            <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-white/10" data-test="dashboard-foundation-customizer">
                <div class="space-y-1">
                    <flux:subheading>{{ __('dashboard.foundation.customize.label') }}</flux:subheading>
                    <flux:text size="sm">{{ __('dashboard.foundation.customize.description') }}</flux:text>
                </div>

                <div class="space-y-3" aria-label="{{ __('dashboard.foundation.customize.list_label') }}">
                    @foreach ($this->foundationWidgetSettings as $setting)
                        <div wire:key="dashboard-foundation-setting-{{ $setting['key'] }}" class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-3 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between" data-test="dashboard-foundation-setting-{{ $setting['key'] }}">
                            <flux:checkbox
                                :checked="$setting['visible']"
                                :label="$setting['label']"
                                :description="$setting['description']"
                                wire:click="toggleFoundationWidget('{{ $setting['key'] }}')"
                                data-test="dashboard-foundation-setting-visible-{{ $setting['key'] }}"
                            />

                            <div class="flex items-center gap-2 sm:justify-end">
                                <flux:badge size="sm">{{ __('dashboard.foundation.customize.position', ['position' => $setting['position']]) }}</flux:badge>

                                <flux:button.group>
                                    <flux:button type="button" size="sm" variant="ghost" icon="arrow-up" square :disabled="! $setting['can_move_up']" :aria-label="__('dashboard.foundation.customize.move_up', ['label' => $setting['label']])" wire:click="moveFoundationWidget('{{ $setting['key'] }}', 'up')" wire:loading.attr="disabled" data-test="dashboard-foundation-setting-move-up-{{ $setting['key'] }}" />
                                    <flux:button type="button" size="sm" variant="ghost" icon="arrow-down" square :disabled="! $setting['can_move_down']" :aria-label="__('dashboard.foundation.customize.move_down', ['label' => $setting['label']])" wire:click="moveFoundationWidget('{{ $setting['key'] }}', 'down')" wire:loading.attr="disabled" data-test="dashboard-foundation-setting-move-down-{{ $setting['key'] }}" />
                                </flux:button.group>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <flux:error name="foundationWidgetVisibility" />
                    <flux:error name="foundationWidgetOrder" />

                    <flux:button type="button" size="sm" variant="ghost" icon="arrow-path" wire:click="resetFoundationWidgets" wire:loading.attr="disabled" data-test="dashboard-foundation-reset">
                        {{ __('dashboard.foundation.customize.reset') }}
                    </flux:button>
                </div>
            </div>
        @endif

        @if ($this->hasVisibleFoundationWidgets)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('dashboard.foundation.widgets_label') }}">
                @foreach ($this->foundationWidgets as $widget)
                    <div wire:key="dashboard-foundation-widget-{{ $widget['key'] }}" class="flex min-h-56 min-w-0 flex-col justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-950" data-test="dashboard-foundation-widget-{{ $widget['key'] }}">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <flux:badge size="sm" :color="$widget['color']">{{ $widget['badge'] }}</flux:badge>
                                <span class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ $widget['label'] }}</span>
                            </div>

                            <div class="space-y-2">
                                <div class="break-words text-3xl font-semibold text-zinc-950 dark:text-white">{{ $widget['value'] }}</div>
                                <flux:text size="sm">{{ $widget['description'] }}</flux:text>
                            </div>

                            @if ($showFoundationDetails)
                                <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2" data-test="dashboard-foundation-widget-metrics-{{ $widget['key'] }}">
                                    @foreach ($widget['metrics'] as $metric)
                                        <div wire:key="dashboard-foundation-widget-{{ $widget['key'] }}-metric-{{ $metric['key'] }}" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                            <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ $metric['label'] }}</dt>
                                            <dd class="mt-1 break-words text-sm font-semibold text-zinc-950 dark:text-white">{{ $metric['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </div>

                        <flux:button href="{{ $widget['href'] }}" wire:navigate size="sm" align="start" class="mt-5 w-full" data-test="dashboard-foundation-widget-action-{{ $widget['key'] }}">
                            {{ $widget['action'] }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @else
            <flux:callout icon="eye-slash" variant="secondary" data-test="dashboard-foundation-empty-customization">
                <flux:callout.heading>{{ __('dashboard.foundation.customize.empty_heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('dashboard.foundation.customize.empty_description') }}</flux:callout.text>
            </flux:callout>
        @endif

        @if ($this->hasVisibleFoundationWidgets)
            <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-white/10" data-test="dashboard-foundation-chart" role="img" aria-label="{{ $this->foundationChartAria }}">
                <div class="space-y-1">
                    <flux:subheading>{{ __('dashboard.foundation.chart.label') }}</flux:subheading>
                    <flux:text size="sm">{{ __('dashboard.foundation.chart.description') }}</flux:text>
                </div>

                <div class="space-y-3">
                    @foreach ($this->foundationChart as $bar)
                        <div wire:key="dashboard-foundation-chart-{{ $bar['key'] }}" class="space-y-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $bar['label'] }}</span>
                                <span class="text-sm tabular-nums text-zinc-500 dark:text-zinc-400">{{ $bar['value'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                                <span class="block h-full rounded-full bg-blue-600 dark:bg-blue-400" style="width: {{ $bar['percent'] }}%"></span>
                            </div>
                            <span class="sr-only">{{ $bar['summary'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <flux:callout icon="lock-closed" variant="secondary" data-test="dashboard-foundation-privacy-note">
            <flux:callout.heading>{{ __('dashboard.foundation.privacy.heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('dashboard.foundation.privacy.description') }}</flux:callout.text>
        </flux:callout>
    </flux:card>

    <flux:card class="space-y-5" data-test="dashboard-project-progress" aria-labelledby="dashboard-project-progress-heading" role="region" wire:loading.class="opacity-70" wire:loading.attr="aria-busy">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 max-w-2xl space-y-1">
                <flux:subheading>{{ __('dashboard.projects.label') }}</flux:subheading>
                <flux:heading id="dashboard-project-progress-heading" size="lg">{{ __('dashboard.projects.heading') }}</flux:heading>
                <flux:text>{{ __('dashboard.projects.description') }}</flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end" aria-label="{{ $this->projectProgressAria }}">
                <flux:badge color="indigo" icon="folder" class="w-fit">
                    {{ __('dashboard.projects.badges.active_projects', ['count' => $this->projectProgress['totals']['active_projects']]) }}
                </flux:badge>

                <flux:badge color="{{ $this->projectProgress['totals']['cleanup_signals'] > 0 ? 'amber' : 'lime' }}" icon="sparkles" class="w-fit">
                    {{ __('dashboard.projects.badges.cleanup_signals', ['count' => $this->projectProgress['totals']['cleanup_signals']]) }}
                </flux:badge>

                <flux:badge color="zinc" class="w-fit">
                    {{ __('dashboard.projects.generated', ['date' => $this->projectProgress['generated_on']]) }}
                </flux:badge>
            </div>
        </div>

        @if ($this->projectProgress['totals']['cleanup_signals'] > 0)
            <flux:callout icon="sparkles" variant="secondary" data-test="dashboard-project-progress-cleanup">
                <flux:callout.heading>{{ __('dashboard.projects.cleanup.heading', ['count' => $this->projectProgress['totals']['cleanup_signals']]) }}</flux:callout.heading>
                <flux:callout.text>{{ __('dashboard.projects.cleanup.description', ['overdue' => $this->projectProgress['totals']['overdue'], 'undated' => $this->projectProgress['totals']['undated'], 'stale' => $this->projectProgress['totals']['stale'], 'no_project' => $this->projectProgress['totals']['no_project_active']]) }}</flux:callout.text>
            </flux:callout>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <flux:button href="{{ route('todos.cleanup') }}" wire:navigate size="sm" variant="primary" icon="sparkles" align="start" data-test="dashboard-project-progress-cleanup-action">
                    {{ __('dashboard.projects.actions.open_cleanup') }}
                </flux:button>

                <flux:button href="{{ route('todos.index') }}" wire:navigate size="sm" variant="ghost" icon="list-bullet" align="start">
                    {{ __('dashboard.projects.actions.open_tasks') }}
                </flux:button>
            </div>
        @endif

        @if (! $this->hasProjectProgress)
            <flux:callout icon="folder-open" variant="secondary" data-test="dashboard-project-progress-empty">
                <flux:callout.heading>{{ __('dashboard.projects.empty.heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('dashboard.projects.empty.description') }}</flux:callout.text>
            </flux:callout>
        @else
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-3" aria-label="{{ __('dashboard.projects.cards_label') }}" data-test="dashboard-project-progress-grid">
                @foreach ($this->projectProgress['projects'] as $project)
                    <div wire:key="dashboard-project-progress-card-{{ $project['id'] }}" class="flex min-w-0 flex-col justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-950" data-test="dashboard-project-progress-card-{{ $project['id'] }}">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <flux:badge size="sm" :color="$project['color']" icon="folder">{{ $project['name'] }}</flux:badge>
                                <flux:badge size="sm" color="{{ $project['attention'] > 0 ? 'amber' : 'lime' }}">
                                    {{ __('dashboard.projects.badges.attention', ['count' => $project['attention']]) }}
                                </flux:badge>
                            </div>

                            <div class="space-y-2" data-test="dashboard-project-progress-card-progress-{{ $project['id'] }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('dashboard.projects.metrics.progress') }}</span>
                                    <span class="text-sm tabular-nums text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.percent', ['percent' => $project['completion_percent']]) }}</span>
                                </div>

                                <flux:progress :value="$project['completion_percent']" color="blue" aria-label="{{ __('dashboard.projects.progress_aria', ['project' => $project['name'], 'percent' => $project['completion_percent']]) }}" />
                            </div>

                            <dl class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.active') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $project['active'] }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.completed') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $project['completed'] }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.overdue') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-red-700 dark:text-red-300">{{ $project['overdue'] }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.due_soon') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-sky-700 dark:text-sky-300">{{ $project['due_soon'] }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.undated') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ $project['undated'] }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.stale') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $project['stale'] }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <flux:button href="{{ route('projects.show', $project['id']) }}" wire:navigate size="sm" align="start" icon="folder-open" data-test="dashboard-project-progress-open-{{ $project['id'] }}">
                                {{ __('dashboard.projects.actions.open_project') }}
                            </flux:button>

                            <flux:button href="{{ route('todos.index', ['project' => $project['id']]) }}" wire:navigate size="sm" align="start" variant="ghost" icon="funnel" data-test="dashboard-project-progress-filter-{{ $project['id'] }}">
                                {{ __('dashboard.projects.actions.filter_project') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach

                @if ($this->projectProgress['totals']['no_project_active'] > 0)
                    <div class="flex min-w-0 flex-col justify-between rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-4 dark:border-white/15 dark:bg-zinc-900" data-test="dashboard-project-progress-no-project">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <flux:badge size="sm" color="zinc" icon="folder">{{ __('dashboard.projects.no_project.label') }}</flux:badge>
                                <flux:badge size="sm" color="{{ $this->projectProgress['no_project']['attention'] > 0 ? 'amber' : 'lime' }}">
                                    {{ __('dashboard.projects.badges.attention', ['count' => $this->projectProgress['no_project']['attention']]) }}
                                </flux:badge>
                            </div>

                            <flux:text>{{ __('dashboard.projects.no_project.description') }}</flux:text>

                            <dl class="grid grid-cols-2 gap-2">
                                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-950">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.active') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $this->projectProgress['no_project']['active'] }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-950">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.overdue') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-red-700 dark:text-red-300">{{ $this->projectProgress['no_project']['overdue'] }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-950">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.undated') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ $this->projectProgress['no_project']['undated'] }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-950">
                                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.projects.metrics.stale') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $this->projectProgress['no_project']['stale'] }}</dd>
                                </div>
                            </dl>
                        </div>

                        <flux:button href="{{ route('todos.index', ['project' => 'none']) }}" wire:navigate size="sm" align="start" variant="ghost" icon="funnel" class="mt-5 w-full" data-test="dashboard-project-progress-review-no-project">
                            {{ __('dashboard.projects.actions.review_no_project') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif
    </flux:card>

    <div class="overflow-x-auto pb-2" data-test="dashboard-summary-widgets">
        <div class="grid min-w-[78rem] grid-cols-11 gap-2">
            <x-ui.stat :label="__('dashboard.summary.active')" :value="$this->summary['active']" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.overdue')" :value="$this->summary['overdue']" tone="danger" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.completed')" :value="$this->summary['completed']" tone="success" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.archived')" :value="$this->summary['archived']" tone="violet" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.trash')" :value="$this->summary['trash']" tone="rose" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.projects')" :value="$this->summary['projects']" tone="indigo" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.tags')" :value="$this->summary['tags']" tone="fuchsia" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.goals')" :value="$this->summary['goals']" tone="amber" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.milestones')" :value="$this->summary['milestones']" tone="cyan" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.habits')" :value="$this->summary['habits']" tone="lime" variant="colored" />
            <x-ui.stat :label="__('dashboard.summary.habit_check_ins')" :value="$this->summary['habit_check_ins']" tone="teal" variant="colored" />
        </div>
    </div>

    <flux:card class="space-y-4" data-test="dashboard-workspace-tabs">
        <flux:subheading>{{ __('dashboard.workspace.label') }}</flux:subheading>

        <div class="overflow-x-auto pb-1">
            <div role="tablist" aria-label="{{ __('dashboard.workspace.tabs_label') }}" class="flex min-w-max gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-900">
                <button type="button" role="tab" aria-selected="true" class="inline-flex h-9 items-center gap-2 rounded-md bg-white px-3 text-sm font-medium text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white" data-test="dashboard-owner-tab">
                    <flux:icon.lock-closed variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.heading') }}</span>
                </button>

                <a href="{{ route('todos.today') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.calendar variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.today_action') }}</span>
                </a>

                <a href="{{ route('todos.overdue') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.exclamation-triangle variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.overdue_action') }}</span>
                </a>

                <a href="{{ route('todos.upcoming') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.calendar variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.upcoming_action') }}</span>
                </a>

                <a href="{{ route('todos.focus') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.bolt variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.focus_action') }}</span>
                </a>

                <a href="{{ route('todos.time') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.clock variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.time_action') }}</span>
                </a>

                <a href="{{ route('todos.blocked') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.exclamation-triangle variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.blocked_action') }}</span>
                </a>

                <a href="{{ route('todos.cleanup') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.sparkles variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.cleanup_action') }}</span>
                </a>

                <a href="{{ route('todos.automations') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.bolt variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.automations_action') }}</span>
                </a>

                <a href="{{ route('todos.reminders') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.bell variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.reminders_action') }}</span>
                </a>

                <a href="{{ route('goals.index') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.flag variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.goals_action') }}</span>
                </a>

                <a href="{{ route('habits.index') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.arrow-path variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.habits_action') }}</span>
                </a>

                <a href="{{ route('todos.index') }}" wire:navigate role="tab" aria-selected="false" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white" data-test="dashboard-workspace-tab-link">
                    <flux:icon.list-bullet variant="micro" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ __('dashboard.workspace.action') }}</span>
                </a>
            </div>
        </div>

        <div role="tabpanel" class="rounded-lg border border-sky-200 bg-sky-50 p-4 dark:border-sky-400/20 dark:bg-sky-400/10" data-test="dashboard-owner-boundary-panel">
            <flux:heading size="lg">{{ __('dashboard.workspace.heading') }}</flux:heading>
            <flux:text class="mt-1">{{ __('dashboard.workspace.description') }}</flux:text>
        </div>
    </flux:card>
</x-ui.page-container>
