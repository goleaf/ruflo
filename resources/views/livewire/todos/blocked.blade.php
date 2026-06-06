<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.blocked.title')" :description="__('todos.pages.blocked.description')">
        <div class="flex flex-col gap-3 sm:min-w-72">
            <x-ui.stat :label="__('todos.blocked.count')" :value="$this->todos->total()" tone="danger" />

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('todos.index', ['due' => 'blocked'])" wire:navigate variant="subtle" icon="funnel">
                    {{ __('todos.blocked.open_filtered') }}
                </flux:button>
                <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                    {{ __('todos.actions.back_to_list') }}
                </flux:button>
            </div>
        </div>
    </x-ui.page-header>

    <flux:card class="space-y-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:subheading>{{ __('todos.blocked.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.blocked.heading') }}</flux:heading>
            </div>

            <flux:badge color="amber" icon="exclamation-triangle">{{ __('todos.dependencies.waiting_badge') }}</flux:badge>
        </div>

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div wire:key="blocked-todo-{{ $todo->id }}" class="flex min-h-14 items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <flux:icon.exclamation-triangle variant="micro" class="mt-1 text-amber-500" />

                    <div class="min-w-0 flex-1 space-y-1">
                        <a href="{{ route('todos.show', $todo) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 dark:text-white">
                            {{ $todo->title }}
                        </a>

                        <div class="flex flex-wrap items-center gap-1.5">
                            <flux:badge size="sm" color="amber" icon="exclamation-triangle">
                                {{ __('todos.dependencies.blocked_badge', ['count' => $todo->openBlockerCount()]) }}
                            </flux:badge>

                            @foreach ($todo->dependencies as $dependency)
                                @if ($dependency->isOpen() && $dependency->blocker)
                                    <a href="{{ route('todos.show', $dependency->blocker) }}" wire:navigate>
                                        <flux:badge wire:key="blocked-dependency-{{ $todo->id }}-{{ $dependency->id }}" size="sm" color="zinc" icon="link">
                                            {{ __('todos.dependencies.blocked_by', ['title' => $dependency->blocker->title]) }}
                                        </flux:badge>
                                    </a>
                                @endif
                            @endforeach

                            @if ($todo->due_date)
                                <flux:badge size="sm" :color="$todo->isOverdue() ? 'red' : ($todo->isDueToday() ? 'amber' : 'zinc')" icon="calendar">
                                    {{ $todo->due_date->isoFormat('MMM D') }}
                                </flux:badge>
                            @endif

                            @if ($todo->project)
                                <a href="{{ route('projects.show', $todo->project) }}" wire:navigate>
                                    <flux:badge size="sm" :color="$todo->project->color" icon="folder">{{ $todo->project->name }}</flux:badge>
                                </a>
                            @endif
                        </div>
                    </div>

                    <flux:button :href="route('todos.show', $todo)" wire:navigate size="sm" variant="ghost" icon="arrow-right">
                        {{ __('todos.blocked.review') }}
                    </flux:button>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.blocked.empty.title')"
                    :description="__('todos.blocked.empty.description')"
                />
            @endforelse
        </div>

        @if ($this->todos->hasPages())
            <div>{{ $this->todos->links() }}</div>
        @endif
    </flux:card>
</section>
