<x-ui.page-container>
    <x-ui.page-header :title="__('goals.pages.index.title')" :description="__('goals.pages.index.description')">
        <div class="flex flex-col gap-3 sm:min-w-80">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <x-ui.stat :label="__('goals.summary.goals')" :value="count($this->goalCards)" />
                <x-ui.stat :label="__('goals.summary.linkable_tasks')" :value="$this->availableTodos->count()" />
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('dashboard')" wire:navigate variant="ghost" icon="arrow-left">
                    {{ __('goals.actions.back_to_dashboard') }}
                </flux:button>

                <flux:button :href="route('todos.index')" wire:navigate variant="subtle" icon="list-bullet">
                    {{ __('goals.actions.open_tasks') }}
                </flux:button>

                <flux:button :href="route('goals.create')" wire:navigate variant="primary" icon="flag">
                    {{ __('goals.actions.create') }}
                </flux:button>

                <flux:button :href="route('goals.milestones.create')" wire:navigate variant="subtle" icon="plus">
                    {{ __('goals.actions.add_milestone') }}
                </flux:button>
            </div>
        </div>
    </x-ui.page-header>

    <div class="space-y-4" data-test="goals-tabs">
        <div role="tablist" class="flex flex-wrap gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-900">
            <button type="button" role="tab" aria-selected="true" class="rounded-md bg-white px-3 py-1.5 text-sm font-medium text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white">
                {{ __('goals.tabs.goals') }}
            </button>

            <a href="{{ route('goals.create') }}" wire:navigate role="tab" aria-selected="false" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                {{ __('goals.tabs.create') }}
            </a>

            <a href="{{ route('goals.milestones.create') }}" wire:navigate role="tab" aria-selected="false" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                {{ __('goals.tabs.milestones') }}
            </a>
        </div>

        <div role="tabpanel" data-test="goal-list-panel">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2" data-test="goal-list">
                @forelse ($this->goalCards as $card)
                    <flux:card wire:key="goal-card-{{ $card['goal']->id }}" class="space-y-5" data-test="goal-card">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge size="sm" color="blue" icon="flag">{{ __('goals.badges.goal') }}</flux:badge>

                                    @if ($card['goal']->project)
                                        <flux:badge size="sm" :color="$card['goal']->project->color" icon="folder">{{ $card['goal']->project->name }}</flux:badge>
                                    @endif

                                    @if ($card['goal']->target_date)
                                        <flux:badge size="sm" color="amber" icon="calendar">
                                            {{ __('goals.target_date', ['date' => $card['goal']->target_date->isoFormat('MMM D')]) }}
                                        </flux:badge>
                                    @endif
                                </div>

                                <flux:heading size="lg" class="break-words">{{ $card['goal']->title }}</flux:heading>

                                @if ($card['goal']->description)
                                    <flux:text class="break-words">{{ $card['goal']->description }}</flux:text>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <flux:subheading>{{ __('goals.progress.label') }}</flux:subheading>
                                <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-300">
                                    {{ __('goals.progress.text', ['completed' => $card['progress']->completedUnits, 'total' => $card['progress']->totalUnits, 'percent' => $card['progress']->percent]) }}
                                </span>
                            </div>

                            <flux:progress :value="$card['progress']->percent" color="green" aria-label="{{ __('goals.progress.aria', ['percent' => $card['progress']->percent]) }}" />

                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                    <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('goals.summary.tasks') }}</div>
                                    <div class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ __('goals.progress.counts', ['completed' => $card['progress']->completedTasks, 'total' => $card['progress']->totalTasks]) }}</div>
                                </div>

                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                                    <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('goals.summary.milestones') }}</div>
                                    <div class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ __('goals.progress.counts', ['completed' => $card['progress']->completedMilestones, 'total' => $card['progress']->totalMilestones]) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <flux:subheading>{{ __('goals.milestones.heading') }}</flux:subheading>

                            <div class="space-y-2">
                                @forelse ($card['goal']->milestones as $milestone)
                                    <div wire:key="goal-{{ $card['goal']->id }}-milestone-{{ $milestone->id }}" class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/10 dark:bg-zinc-900">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-medium break-words text-zinc-950 dark:text-white">{{ $milestone->title }}</span>

                                                @if ($milestone->isCompleted())
                                                    <flux:badge size="sm" color="green" icon="check-circle">{{ __('goals.milestones.checked_in') }}</flux:badge>
                                                @endif

                                                @if ($milestone->target_date)
                                                    <flux:badge size="sm" color="amber" icon="calendar">{{ $milestone->target_date->isoFormat('MMM D') }}</flux:badge>
                                                @endif
                                            </div>

                                            @if ($milestone->todos->isNotEmpty())
                                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ __('goals.milestones.linked_tasks', ['count' => $milestone->todos->count()]) }}
                                                </div>
                                            @endif
                                        </div>

                                        <flux:button type="button" size="sm" :variant="$milestone->isCompleted() ? 'ghost' : 'primary'" :icon="$milestone->isCompleted() ? 'arrow-path' : 'check-circle'" wire:click="checkInMilestone({{ $milestone->id }})" wire:loading.attr="disabled" wire:target="checkInMilestone({{ $milestone->id }})">
                                            {{ $milestone->isCompleted() ? __('goals.actions.reopen_milestone') : __('goals.actions.check_in') }}
                                        </flux:button>
                                    </div>
                                @empty
                                    <x-ui.empty-state
                                        :title="__('goals.milestones.empty.title')"
                                        :description="__('goals.milestones.empty.description')"
                                    />
                                @endforelse
                            </div>
                        </div>

                        <form wire:submit="linkTodo({{ $card['goal']->id }})" class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                            <div>
                                <flux:subheading>{{ __('goals.link.heading') }}</flux:subheading>
                                <flux:text class="mt-1 text-sm">{{ __('goals.link.description') }}</flux:text>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start">
                                <div>
                                    <flux:select wire:model="linkTodoIds.{{ $card['goal']->id }}" :label="__('goals.fields.task')" size="sm">
                                        <flux:select.option value="">{{ __('goals.fields.choose_task') }}</flux:select.option>
                                        @foreach ($this->availableTodos as $todo)
                                            <flux:select.option value="{{ $todo->id }}">{{ $todo->title }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="linkTodoIds.{{ $card['goal']->id }}" />
                                </div>

                                <div>
                                    <flux:select wire:model="linkMilestoneIds.{{ $card['goal']->id }}" :label="__('goals.fields.milestone')" size="sm">
                                        <flux:select.option value="">{{ __('goals.fields.whole_goal') }}</flux:select.option>
                                        @foreach ($card['goal']->milestones as $milestone)
                                            <flux:select.option value="{{ $milestone->id }}">{{ $milestone->title }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="linkMilestoneIds.{{ $card['goal']->id }}" />
                                </div>

                                <flux:button type="submit" variant="primary" size="sm" icon="link" class="sm:mt-6" wire:loading.attr="disabled" wire:target="linkTodo({{ $card['goal']->id }})">
                                    {{ __('goals.actions.link_task') }}
                                </flux:button>
                            </div>
                        </form>
                    </flux:card>
                @empty
                    <div class="lg:col-span-2">
                        <x-ui.empty-state
                            :title="__('goals.empty.title')"
                            :description="__('goals.empty.description')"
                        />
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-ui.page-container>
