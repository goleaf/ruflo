<x-ui.page-container gap="gap-8">
    <x-ui.page-header :title="__('dashboard.heading')" :description="__('dashboard.description')" />

    @if ($reminderRunReport !== null)
        <flux:callout icon="bell" variant="secondary" data-test="dashboard-reminder-run-report">
            <flux:callout.heading>{{ __('reminders.processing.report_heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('reminders.processing.report', $reminderRunReport) }}</flux:callout.text>
        </flux:callout>
    @endif

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
