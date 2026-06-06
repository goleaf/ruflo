<section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <x-ui.page-header :title="$this->project->name" :description="__('todos.projects.show.description')">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge size="sm" :color="$this->project->isArchived() ? 'zinc' : $this->project->color">
                {{ $this->project->isArchived() ? __('todos.status.archived') : __('todos.status.active') }}
            </flux:badge>

            @unless ($this->project->isArchived())
                <flux:button :href="route('todos.index', ['project' => $this->project->id])" wire:navigate variant="subtle" icon="funnel">
                    {{ __('todos.projects.actions.filter_tasks') }}
                </flux:button>
            @endunless

            <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                {{ __('todos.actions.back_to_list') }}
            </flux:button>
        </div>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
        <x-ui.stat :label="__('todos.summary.active')" :value="$this->summary['active']" />
        <x-ui.stat :label="__('todos.summary.completed')" :value="$this->summary['completed']" tone="success" />
        <x-ui.stat :label="__('todos.summary.archived')" :value="$this->summary['archived']" tone="muted" />
        <x-ui.stat :label="__('todos.summary.trash')" :value="$this->summary['trash']" tone="danger" />
    </div>

    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">{{ __('todos.projects.show.tasks_heading') }}</flux:heading>
            <flux:text>{{ __('todos.projects.show.task_count', ['count' => $this->todos->total()]) }}</flux:text>
        </div>

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div
                    wire:key="project-task-{{ $todo->id }}"
                    class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-ui.status-badge :status="$todo->status()" />

                            @if ($todo->priority->value !== 'normal')
                                <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                            @endif

                            @if ($todo->due_date)
                                <flux:badge size="sm" :color="$todo->isOverdue() ? 'red' : ($todo->isDueToday() ? 'amber' : 'zinc')" icon="calendar">
                                    {{ $todo->due_date->isoFormat('MMM D') }}
                                </flux:badge>
                            @endif
                        </div>

                        <a href="{{ route('todos.show', $todo) }}" wire:navigate class="block text-sm font-medium break-words text-zinc-950 hover:underline dark:text-white">
                            {{ $todo->title }}
                        </a>

                        @if ($todo->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($todo->tags as $tagBadge)
                                    <flux:badge wire:key="project-task-{{ $todo->id }}-tag-{{ $tagBadge->id }}" size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.empty.project_detail.title')"
                    :description="__('todos.empty.project_detail.description')"
                />
            @endforelse
        </div>

        @if ($this->todos->hasPages())
            <div>{{ $this->todos->links() }}</div>
        @endif
    </flux:card>
</section>
