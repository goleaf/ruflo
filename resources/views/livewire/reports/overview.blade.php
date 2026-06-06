<x-ui.page-container gap="gap-8">
    <x-ui.page-header :title="__('reports.pages.overview.title')" :description="__('reports.pages.overview.description')" />

    <flux:card class="space-y-5" data-test="reports-overview" aria-labelledby="reports-overview-heading" wire:loading.class="opacity-70" wire:loading.attr="aria-busy">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 max-w-2xl space-y-1">
                <flux:subheading>{{ __('reports.overview.label') }}</flux:subheading>
                <flux:heading id="reports-overview-heading" size="lg">{{ __('reports.overview.heading') }}</flux:heading>
                <flux:text>{{ __('reports.overview.description') }}</flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                <flux:badge color="zinc" icon="calendar" class="w-fit">
                    {{ __('reports.overview.generated', ['date' => $this->report['generated_on']]) }}
                </flux:badge>

                <flux:button type="button" size="sm" variant="ghost" :icon="$showDetails ? 'eye-slash' : 'eye'" wire:click="toggleDetails" wire:loading.attr="disabled" data-test="reports-details-toggle">
                    {{ $showDetails ? __('reports.settings.compact') : __('reports.settings.details') }}
                </flux:button>

                <flux:button type="button" size="sm" variant="ghost" icon="chart-bar" wire:click="toggleTrends" wire:loading.attr="disabled" data-test="reports-trends-toggle">
                    {{ $showTrends ? __('reports.settings.hide_trends') : __('reports.settings.show_trends') }}
                </flux:button>
            </div>
        </div>

        @if (! $this->hasReportData)
            <flux:callout icon="chart-bar" variant="secondary" data-test="reports-empty-state">
                <flux:callout.heading>{{ __('reports.empty.heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('reports.empty.description') }}</flux:callout.text>
            </flux:callout>
        @endif

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5" aria-label="{{ __('reports.widgets.label') }}" data-test="reports-widget-grid">
            @foreach ($this->widgets as $widget)
                <div wire:key="reports-widget-{{ $widget['key'] }}" class="flex min-h-64 min-w-0 flex-col justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-950" data-test="reports-widget-{{ $widget['key'] }}">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <flux:badge size="sm" :color="$widget['color']">{{ $widget['badge'] }}</flux:badge>
                            <span class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ $widget['label'] }}</span>
                        </div>

                        <div class="space-y-2">
                            <div class="break-words text-3xl font-semibold text-zinc-950 dark:text-white">{{ $widget['value'] }}</div>
                            <flux:text size="sm">{{ $widget['description'] }}</flux:text>
                        </div>

                        @if ($showDetails)
                            <dl class="grid grid-cols-1 gap-2" data-test="reports-widget-metrics-{{ $widget['key'] }}">
                                @foreach ($widget['metrics'] as $metric)
                                    <div wire:key="reports-widget-{{ $widget['key'] }}-metric-{{ $metric['key'] }}" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                        <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ $metric['label'] }}</dt>
                                        <dd class="mt-1 break-words text-sm font-semibold text-zinc-950 dark:text-white">{{ $metric['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </div>

                    <flux:button href="{{ $widget['href'] }}" wire:navigate size="sm" align="start" class="mt-5 w-full" data-test="reports-widget-action-{{ $widget['key'] }}">
                        {{ $widget['action'] }}
                    </flux:button>
                </div>
            @endforeach
        </div>
    </flux:card>

    <flux:card class="space-y-5" data-test="reports-projects" aria-labelledby="reports-projects-heading">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 max-w-2xl space-y-1">
                <flux:subheading>{{ __('reports.projects.label') }}</flux:subheading>
                <flux:heading id="reports-projects-heading" size="lg">{{ __('reports.projects.heading') }}</flux:heading>
                <flux:text>{{ __('reports.projects.description') }}</flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                <flux:badge color="indigo" icon="folder">{{ __('reports.projects.badges.active', ['count' => $this->report['projects']['active']]) }}</flux:badge>
                <flux:badge color="{{ $this->report['projects']['overdue_tasks'] > 0 ? 'rose' : 'lime' }}" icon="exclamation-triangle">{{ __('reports.projects.badges.overdue', ['count' => $this->report['projects']['overdue_tasks']]) }}</flux:badge>
            </div>
        </div>

        @if ($this->report['projects']['top'] === [])
            <flux:callout icon="folder-open" variant="secondary" data-test="reports-projects-empty">
                <flux:callout.heading>{{ __('reports.projects.empty.heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('reports.projects.empty.description') }}</flux:callout.text>
            </flux:callout>
        @else
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-3" aria-label="{{ __('reports.projects.cards_label') }}" data-test="reports-projects-grid">
                @foreach ($this->report['projects']['top'] as $project)
                    <div wire:key="reports-project-{{ $project['id'] }}" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-950" data-test="reports-project-card-{{ $project['id'] }}">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <flux:badge size="sm" :color="$project['color']" icon="folder">{{ $project['name'] }}</flux:badge>
                            <flux:badge size="sm" color="{{ $project['overdue'] > 0 ? 'rose' : 'lime' }}">{{ __('reports.projects.badges.project_overdue', ['count' => $project['overdue']]) }}</flux:badge>
                        </div>

                        <div class="space-y-2" role="group" aria-label="{{ __('reports.projects.progress_aria', ['project' => $project['name'], 'percent' => $project['completion_percent']]) }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('reports.projects.metrics.progress') }}</span>
                                <span class="text-sm tabular-nums text-zinc-500 dark:text-zinc-400">{{ __('reports.values.percent', ['percent' => $project['completion_percent']]) }}</span>
                            </div>
                            <flux:progress :value="$project['completion_percent']" color="blue" aria-label="{{ __('reports.projects.progress_aria', ['project' => $project['name'], 'percent' => $project['completion_percent']]) }}" />
                        </div>

                        <dl class="grid grid-cols-3 gap-2">
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('reports.projects.metrics.active') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $project['active'] }}</dd>
                            </div>

                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('reports.projects.metrics.completed') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $project['completed'] }}</dd>
                            </div>

                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('reports.projects.metrics.overdue') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-rose-700 dark:text-rose-300">{{ $project['overdue'] }}</dd>
                            </div>
                        </dl>

                        <flux:button href="{{ route('todos.index', ['project' => $project['id']]) }}" wire:navigate size="sm" align="start" class="w-full" data-test="reports-project-action-{{ $project['id'] }}">
                            {{ __('reports.projects.actions.filter_project') }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    @if ($showTrends)
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2" data-test="reports-trend-grid">
            @foreach ($this->chartSections as $chart)
                <flux:card wire:key="reports-chart-{{ $chart['key'] }}" class="space-y-4" data-test="reports-chart-{{ $chart['key'] }}" role="img" aria-label="{{ $chart['aria'] }}">
                    <div class="space-y-1">
                        <flux:subheading>{{ __('reports.charts.label') }}</flux:subheading>
                        <flux:heading size="lg">{{ $chart['label'] }}</flux:heading>
                        <flux:text size="sm">{{ $chart['description'] }}</flux:text>
                    </div>

                    <div class="space-y-3">
                        @foreach ($chart['rows'] as $bar)
                            <div wire:key="reports-chart-{{ $chart['key'] }}-{{ $bar['key'] }}" class="space-y-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $bar['label'] }}</span>
                                    <span class="text-sm tabular-nums text-zinc-500 dark:text-zinc-400">
                                        @if ($chart['key'] === 'time')
                                            {{ __('reports.values.minutes', ['minutes' => $bar['value']]) }}
                                        @else
                                            {{ $bar['value'] }}
                                        @endif
                                    </span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                                    <span class="block h-full rounded-full bg-blue-600 dark:bg-blue-400" style="width: {{ $bar['percent'] }}%"></span>
                                </div>
                                <span class="sr-only">{{ $bar['summary'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    @if ($showDetails)
        <flux:card class="space-y-5" data-test="reports-detail-summary" aria-labelledby="reports-detail-heading">
            <div class="space-y-1">
                <flux:subheading>{{ __('reports.details.label') }}</flux:subheading>
                <flux:heading id="reports-detail-heading" size="lg">{{ __('reports.details.heading') }}</flux:heading>
                <flux:text>{{ __('reports.details.description') }}</flux:text>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-white/10" data-test="reports-detail-productivity">
                    <flux:subheading>{{ __('reports.details.productivity') }}</flux:subheading>
                    <flux:text>{{ __('reports.details.productivity_summary', ['delta' => $this->signedCount($this->report['productivity']['completion_delta']), 'inbox' => $this->report['productivity']['inbox']]) }}</flux:text>
                </div>

                <div class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-white/10" data-test="reports-detail-habits">
                    <flux:subheading>{{ __('reports.details.habits') }}</flux:subheading>
                    <flux:text>{{ __('reports.details.habits_summary', ['delta' => $this->signedCount($this->report['habits']['weekly_delta']), 'habits' => $this->report['habits']['weekly_distinct_habits']]) }}</flux:text>
                </div>

                <div class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-white/10" data-test="reports-detail-projects">
                    <flux:subheading>{{ __('reports.details.projects') }}</flux:subheading>
                    <flux:text>{{ __('reports.details.projects_summary', ['projects' => $this->report['projects']['with_active_tasks'], 'unassigned' => $this->report['projects']['no_project_active']]) }}</flux:text>
                </div>

                <div class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-white/10" data-test="reports-detail-time">
                    <flux:subheading>{{ __('reports.details.time') }}</flux:subheading>
                    <flux:text>{{ __('reports.details.time_summary', ['delta' => $this->formatSignedSeconds($this->report['time']['delta_seconds']), 'timers' => $this->report['time']['active_timers']]) }}</flux:text>
                </div>
            </div>

            <flux:callout icon="lock-closed" variant="secondary" data-test="reports-privacy-note">
                <flux:callout.heading>{{ __('reports.privacy.heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('reports.privacy.description') }}</flux:callout.text>
            </flux:callout>
        </flux:card>
    @endif
</x-ui.page-container>
