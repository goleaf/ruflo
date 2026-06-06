<x-ui.page-container gap="gap-8">
    <x-ui.page-header :title="__('dashboard.heading')" :description="__('dashboard.description')" />

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

    <flux:card class="overflow-x-auto">
        <div class="flex min-w-max items-center gap-4" data-test="dashboard-workspace-row">
            <div class="flex shrink-0 items-center gap-3 whitespace-nowrap">
                <flux:subheading>{{ __('dashboard.workspace.label') }}</flux:subheading>
                <span class="h-4 w-px bg-zinc-200 dark:bg-white/10" aria-hidden="true"></span>
                <flux:heading size="lg">{{ __('dashboard.workspace.heading') }}</flux:heading>
                <flux:text>{{ __('dashboard.workspace.description') }}</flux:text>
            </div>

            <div class="flex shrink-0 items-center gap-2 whitespace-nowrap" data-test="dashboard-workspace-actions">
                <flux:button :href="route('todos.today')" wire:navigate variant="primary" icon="calendar" class="shrink-0">
                    {{ __('dashboard.workspace.today_action') }}
                </flux:button>

                <flux:button :href="route('todos.overdue')" wire:navigate variant="subtle" icon="exclamation-triangle" class="shrink-0">
                    {{ __('dashboard.workspace.overdue_action') }}
                </flux:button>

                <flux:button :href="route('todos.upcoming')" wire:navigate variant="subtle" icon="calendar" class="shrink-0">
                    {{ __('dashboard.workspace.upcoming_action') }}
                </flux:button>

                <flux:button :href="route('todos.focus')" wire:navigate variant="subtle" icon="bolt" class="shrink-0">
                    {{ __('dashboard.workspace.focus_action') }}
                </flux:button>

                <flux:button :href="route('todos.time')" wire:navigate variant="subtle" icon="clock" class="shrink-0">
                    {{ __('dashboard.workspace.time_action') }}
                </flux:button>

                <flux:button :href="route('todos.blocked')" wire:navigate variant="subtle" icon="exclamation-triangle" class="shrink-0">
                    {{ __('dashboard.workspace.blocked_action') }}
                </flux:button>

                <flux:button :href="route('todos.cleanup')" wire:navigate variant="subtle" icon="sparkles" class="shrink-0">
                    {{ __('dashboard.workspace.cleanup_action') }}
                </flux:button>

                <flux:button :href="route('todos.automations')" wire:navigate variant="subtle" icon="bolt" class="shrink-0">
                    {{ __('dashboard.workspace.automations_action') }}
                </flux:button>

                <flux:button :href="route('goals.index')" wire:navigate variant="subtle" icon="flag" class="shrink-0">
                    {{ __('dashboard.workspace.goals_action') }}
                </flux:button>

                <flux:button :href="route('habits.index')" wire:navigate variant="subtle" icon="arrow-path" class="shrink-0">
                    {{ __('dashboard.workspace.habits_action') }}
                </flux:button>

                <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="list-bullet" class="shrink-0">
                    {{ __('dashboard.workspace.action') }}
                </flux:button>
            </div>
        </div>
    </flux:card>
</x-ui.page-container>
