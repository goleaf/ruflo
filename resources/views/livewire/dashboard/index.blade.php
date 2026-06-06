<section class="space-y-8">
    <x-ui.page-header :title="__('dashboard.heading')" :description="__('dashboard.description')">
        <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4 sm:min-w-[34rem]">
            <x-ui.stat :label="__('dashboard.summary.active')" :value="$this->summary['active']" />
            <x-ui.stat :label="__('dashboard.summary.overdue')" :value="$this->summary['overdue']" tone="danger" />
            <x-ui.stat :label="__('dashboard.summary.completed')" :value="$this->summary['completed']" tone="success" />
            <x-ui.stat :label="__('dashboard.summary.archived')" :value="$this->summary['archived']" tone="muted" />
            <x-ui.stat :label="__('dashboard.summary.trash')" :value="$this->summary['trash']" tone="danger" />
            <x-ui.stat :label="__('dashboard.summary.projects')" :value="$this->summary['projects']" />
            <x-ui.stat :label="__('dashboard.summary.tags')" :value="$this->summary['tags']" />
        </div>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-[1.05fr_0.95fr]">
        <flux:card class="space-y-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <flux:subheading>{{ __('dashboard.workspace.label') }}</flux:subheading>
                    <flux:heading size="lg">{{ __('dashboard.workspace.heading') }}</flux:heading>
                    <flux:text>{{ __('dashboard.workspace.description') }}</flux:text>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button :href="route('todos.today')" wire:navigate variant="primary" icon="calendar">
                        {{ __('dashboard.workspace.today_action') }}
                    </flux:button>

                    <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="list-bullet">
                        {{ __('dashboard.workspace.action') }}
                    </flux:button>
                </div>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div class="space-y-2">
                <flux:subheading>{{ __('dashboard.install.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('dashboard.install.heading') }}</flux:heading>
                <flux:text>{{ __('dashboard.install.description') }}</flux:text>
            </div>

            <div class="grid gap-3">
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                    <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.cards.mcp.label') }}</div>
                    <div class="mt-2 overflow-x-auto font-mono text-sm text-zinc-950 dark:text-zinc-100">npx ruflo@latest mcp start</div>
                    <flux:text class="mt-2 text-sm">{{ __('dashboard.cards.mcp.description') }}</flux:text>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                    <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('dashboard.cards.plugin.label') }}</div>
                    <div class="mt-2 overflow-x-auto font-mono text-sm text-zinc-950 dark:text-zinc-100">/plugin marketplace add ruvnet/ruflo</div>
                    <flux:text class="mt-2 text-sm">{{ __('dashboard.cards.plugin.description') }}</flux:text>
                </div>
            </div>
        </flux:card>
    </div>
</section>
