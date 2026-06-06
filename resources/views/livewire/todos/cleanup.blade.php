<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.cleanup.title')" :description="__('todos.pages.cleanup.description')">
        <div class="grid grid-cols-2 gap-3 text-sm sm:min-w-[32rem] sm:grid-cols-4">
            @foreach ($this->viewOptions() as $cleanupView)
                <x-ui.stat
                    :label="$this->viewLabel($cleanupView)"
                    :value="$this->summary[$cleanupView]"
                    :tone="in_array($cleanupView, ['blocked', 'risky'], true) ? 'danger' : ($cleanupView === 'stale' ? 'muted' : 'default')"
                />
            @endforeach
        </div>
    </x-ui.page-header>

    <flux:card class="space-y-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-1">
                <flux:subheading>{{ __('todos.cleanup.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.cleanup.heading') }}</flux:heading>
                <flux:text>{{ $this->viewDescription($view) }}</flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                    {{ __('todos.actions.back_to_list') }}
                </flux:button>
            </div>
        </div>

        <div role="tablist" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->viewOptions() as $cleanupView)
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('view', '{{ $cleanupView }}')"
                    @class([
                        'flex min-h-20 items-start gap-3 rounded-lg border px-3 py-3 text-left transition',
                        'border-blue-300 bg-blue-50 text-blue-950 dark:border-blue-400/40 dark:bg-blue-400/10 dark:text-blue-100' => $view === $cleanupView,
                        'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200' => $view !== $cleanupView,
                    ])
                >
                    <flux:icon :name="$this->viewIcon($cleanupView)" class="mt-0.5 size-4 shrink-0" />

                    <span class="min-w-0 space-y-1">
                        <span class="flex items-center gap-2 font-medium">
                            {{ $this->viewLabel($cleanupView) }}
                            <flux:badge size="sm" :color="$view === $cleanupView ? 'blue' : 'zinc'">{{ $this->summary[$cleanupView] }}</flux:badge>
                        </span>
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $this->viewDescription($cleanupView) }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                :placeholder="__('todos.cleanup.filters.search_placeholder')"
                :label="__('todos.filters.search')"
                class="lg:col-span-2"
            />

            <flux:select wire:model.live="sort" :label="__('todos.filters.sort')">
                @foreach ($this->sortOptions() as $sortOption)
                    <flux:select.option value="{{ $sortOption }}">{{ $this->sortLabel($sortOption) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="direction" :label="__('todos.filters.direction')">
                <flux:select.option value="desc">{{ __('todos.sort.desc') }}</flux:select.option>
                <flux:select.option value="asc">{{ __('todos.sort.asc') }}</flux:select.option>
            </flux:select>
        </div>

        <div class="flex flex-wrap items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-white/10 dark:bg-zinc-900">
            <flux:text class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.filters.active') }}</flux:text>

            @foreach ($this->activeFilterChips() as $chip)
                <flux:badge wire:key="cleanup-filter-{{ $chip['key'] }}" size="sm" :color="$chip['color']" :icon="$chip['icon']">
                    {{ $chip['label'] }}
                </flux:badge>
            @endforeach

            <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="resetFilters">
                {{ __('todos.actions.clear_filters') }}
            </flux:button>
        </div>

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div wire:key="cleanup-todo-{{ $todo->id }}" class="flex min-h-16 items-start gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-3 dark:border-white/10 dark:bg-white/5">
                    <flux:icon :name="$this->viewIcon($view)" class="mt-1 size-4 shrink-0 text-zinc-500 dark:text-zinc-400" />

                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <a href="{{ route('todos.show', $todo) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 hover:underline dark:text-white">
                                {{ $todo->title }}
                            </a>

                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('todos.cleanup.updated_at', ['time' => $todo->updated_at?->diffForHumans()]) }}
                            </flux:text>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($this->cleanupBadges($todo) as $badge)
                                <flux:badge wire:key="cleanup-badge-{{ $todo->id }}-{{ $badge['key'] }}" size="sm" :color="$badge['color']" :icon="$badge['icon']">
                                    {{ $badge['label'] }}
                                </flux:badge>
                            @endforeach

                            <flux:badge size="sm" :color="$todo->priority->color()" icon="flag">
                                {{ $todo->priority->label() }}
                            </flux:badge>

                            @if ($todo->due_date)
                                <flux:badge size="sm" :color="$todo->isOverdue() ? 'red' : ($todo->isDueToday() ? 'amber' : 'zinc')" icon="calendar">
                                    {{ $todo->due_date->isoFormat('MMM D') }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" icon="calendar">
                                    {{ __('todos.fields.no_due_date') }}
                                </flux:badge>
                            @endif

                            @if ($todo->project)
                                <a href="{{ route('projects.show', $todo->project) }}" wire:navigate>
                                    <flux:badge size="sm" :color="$todo->project->color" icon="folder">{{ $todo->project->name }}</flux:badge>
                                </a>
                            @else
                                <flux:badge size="sm" color="zinc" icon="folder">{{ __('todos.fields.no_project') }}</flux:badge>
                            @endif

                            @if ($todo->openBlockerCount() > 0)
                                <flux:badge size="sm" color="amber" icon="exclamation-triangle">
                                    {{ __('todos.dependencies.blocked_badge', ['count' => $todo->openBlockerCount()]) }}
                                </flux:badge>
                            @endif
                        </div>
                    </div>

                    <flux:button :href="route('todos.show', $todo)" wire:navigate size="sm" variant="ghost" icon="arrow-right">
                        {{ __('todos.cleanup.actions.review') }}
                    </flux:button>
                </div>
            @empty
                <x-ui.empty-state
                    :title="$this->emptyStateTitle()"
                    :description="$this->emptyStateDescription()"
                />
            @endforelse
        </div>

        <flux:pagination :paginator="$this->todos" />
    </flux:card>
</section>
