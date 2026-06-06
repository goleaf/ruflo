<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.overdue.title')" :description="__('todos.pages.overdue.description')">
        <div class="flex flex-col gap-3 sm:min-w-72">
            <x-ui.stat :label="__('todos.overdue.count')" :value="$this->todos->total()" tone="danger" />

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('todos.index', ['due' => 'overdue'])" wire:navigate variant="subtle" icon="funnel">
                    {{ __('todos.overdue.open_filtered') }}
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
                <flux:subheading>{{ __('todos.overdue.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.overdue.date', ['date' => $this->todayLabel()]) }}</flux:heading>
            </div>

            <flux:badge color="red" icon="exclamation-triangle">{{ __('todos.filters.overdue') }}</flux:badge>
        </div>

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div wire:key="overdue-todo-{{ $todo->id }}" class="flex min-h-14 items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 dark:border-red-500/30 dark:bg-red-500/10">
                    @can('complete', $todo)
                        <flux:button
                            size="sm"
                            variant="ghost"
                            square
                            icon="check"
                            wire:click="completeTodo({{ $todo->id }})"
                            :aria-label="__('todos.actions.complete')"
                        />
                    @else
                        <flux:icon.eye variant="micro" class="mt-1 text-zinc-400" />
                    @endcan

                    <div class="min-w-0 flex-1 space-y-1">
                        <a href="{{ route('todos.show', $todo) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 dark:text-white">
                            {{ $todo->title }}
                        </a>

                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($todo->priority->value !== 'normal')
                                <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                            @endif

                            <flux:badge size="sm" color="red" icon="calendar">{{ $todo->due_date->isoFormat('MMM D') }}</flux:badge>

                            @if ((int) $todo->user_id !== (int) auth()->id())
                                <flux:badge size="sm" color="blue" icon="users">
                                    {{ __('todos.collaboration.scope.shared') }}
                                </flux:badge>
                            @endif

                            @if ($todo->project)
                                <a href="{{ route('projects.show', $todo->project) }}" wire:navigate>
                                    <flux:badge size="sm" :color="$todo->project->color" icon="folder">{{ $todo->project->name }}</flux:badge>
                                </a>
                            @endif

                            @foreach ($todo->tags as $tagBadge)
                                <a href="{{ route('todos.index', ['tag' => $tagBadge->id]) }}" wire:navigate>
                                    <flux:badge wire:key="overdue-tag-{{ $todo->id }}-{{ $tagBadge->id }}" size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.empty.due.overdue.title')"
                    :description="__('todos.overdue.empty_description')"
                />
            @endforelse
        </div>

        @if ($this->todos->hasPages())
            <div>{{ $this->todos->links() }}</div>
        @endif
    </flux:card>
</section>
