<section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.board.title')" :description="__('todos.pages.board.description')">
        <div class="flex flex-col gap-3 sm:min-w-[28rem]">
            <div class="grid grid-cols-3 gap-3 text-sm">
                <x-ui.stat :label="__('todos.summary.active')" :value="$this->summary['active']" />
                <x-ui.stat :label="__('todos.summary.completed')" :value="$this->summary['completed']" tone="success" />
                <x-ui.stat :label="__('todos.summary.archived')" :value="$this->summary['archived']" tone="muted" />
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('todos.index')" wire:navigate variant="subtle" icon="list-bullet">
                    {{ __('todos.board.open_list') }}
                </flux:button>
            </div>
        </div>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        @foreach ($columns as $columnKey => $column)
            <flux:card wire:key="board-column-{{ $columnKey }}" class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <flux:subheading>{{ __('todos.board.column') }}</flux:subheading>
                        <flux:heading size="lg">{{ __('todos.tabs.'.$column['status']->value) }}</flux:heading>
                    </div>

                    <flux:badge :color="$this->columnColor($column['status'])" icon="queue-list">
                        {{ $this->summary[$column['status']->value] }}
                    </flux:badge>
                </div>

                <div
                    class="space-y-3"
                    wire:sort="moveCardByDrag"
                    wire:sort:group="todo-board-cards"
                    wire:sort:group-id="{{ $column['status']->value }}"
                    data-test="board-card-list-{{ $column['status']->value }}"
                >
                    @forelse ($column['todos'] as $todo)
                        <div
                            wire:key="board-card-{{ $columnKey }}-{{ $todo->id }}"
                            wire:sort:item="{{ $todo->id }}"
                            class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 touch-manipulation cursor-grab select-none transition hover:border-zinc-300 hover:bg-white active:cursor-grabbing dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20 dark:hover:bg-zinc-800"
                            data-test="board-card"
                        >
                            <div class="space-y-1">
                                <a href="{{ route('todos.show', $todo) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 dark:text-white">
                                    {{ $todo->title }}
                                </a>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($todo->priority->value !== 'normal')
                                        <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                                    @endif

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

                                    @foreach ($todo->tags as $tagBadge)
                                        <a href="{{ route('todos.index', ['tag' => $tagBadge->id]) }}" wire:navigate>
                                            <flux:badge wire:key="board-tag-{{ $todo->id }}-{{ $tagBadge->id }}" size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="space-y-1" data-test="board-card-project-row">
                                <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2">
                                    <span class="whitespace-nowrap text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                        {{ __('todos.board.project_label') }}
                                    </span>

                                    <flux:select wire:model="projectMoves.{{ $todo->id }}" size="sm" aria-label="{{ __('todos.board.project_label') }}">
                                        <flux:select.option value="">{{ __('todos.fields.no_project') }}</flux:select.option>
                                        @foreach ($this->projects as $project)
                                            <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="folder-arrow-down"
                                        class="whitespace-nowrap"
                                        wire:click="moveProject({{ $todo->id }})"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ __('todos.board.move_project') }}
                                    </flux:button>
                                </div>

                                <flux:error name="projectMoves.{{ $todo->id }}" />
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->boardStatuses() as $targetStatus)
                                    @continue($targetStatus === $column['status'])

                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        :icon="$this->statusIcon($targetStatus)"
                                        wire:click="moveToStatus({{ $todo->id }}, '{{ $targetStatus->value }}')"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ __('todos.board.move_to.'.$targetStatus->value) }}
                                    </flux:button>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state
                            :title="__('todos.board.empty.'.$column['status']->value.'.title')"
                            :description="__('todos.board.empty.'.$column['status']->value.'.description')"
                        />
                    @endforelse
                </div>

                @if ($this->summary[$column['status']->value] > $column['todos']->count())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('todos.board.column_limit', ['shown' => $column['todos']->count(), 'total' => $this->summary[$column['status']->value]]) }}
                    </flux:text>
                @endif
            </flux:card>
        @endforeach
    </div>
</section>
