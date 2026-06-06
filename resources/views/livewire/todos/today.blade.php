<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.today.title')" :description="__('todos.pages.today.description')">
        <div class="flex flex-col gap-3 sm:min-w-72">
            <x-ui.stat :label="__('todos.today.count')" :value="$this->todos->total()" />

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('todos.index', ['due' => 'today'])" wire:navigate variant="subtle" icon="funnel">
                    {{ __('todos.today.open_filtered') }}
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
                <flux:subheading>{{ __('todos.today.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.today.date', ['date' => $this->todayLabel()]) }}</flux:heading>
            </div>

            <flux:badge color="amber" icon="calendar">{{ __('todos.filters.due_today') }}</flux:badge>
        </div>

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div wire:key="today-todo-{{ $todo->id }}" class="flex min-h-14 items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/10 dark:bg-zinc-900">
                    <flux:button
                        size="sm"
                        variant="ghost"
                        square
                        icon="check"
                        wire:click="completeTodo({{ $todo->id }})"
                        :aria-label="__('todos.actions.complete')"
                    />

                    <div class="min-w-0 flex-1 space-y-1">
                        <a href="{{ route('todos.show', $todo) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 dark:text-white">
                            {{ $todo->title }}
                        </a>

                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($todo->priority->value !== 'normal')
                                <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                            @endif

                            <flux:badge size="sm" color="amber" icon="calendar">{{ $todo->due_date->isoFormat('MMM D') }}</flux:badge>

                            @if ($todo->project)
                                <a href="{{ route('projects.show', $todo->project) }}" wire:navigate>
                                    <flux:badge size="sm" :color="$todo->project->color" icon="folder">{{ $todo->project->name }}</flux:badge>
                                </a>
                            @endif

                            @foreach ($todo->tags as $tagBadge)
                                <a href="{{ route('todos.index', ['tag' => $tagBadge->id]) }}" wire:navigate>
                                    <flux:badge wire:key="today-tag-{{ $todo->id }}-{{ $tagBadge->id }}" size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.empty.due.today.title')"
                    :description="__('todos.today.empty_description')"
                />
            @endforelse
        </div>

        @if ($this->todos->hasPages())
            <div>{{ $this->todos->links() }}</div>
        @endif
    </flux:card>
</section>
