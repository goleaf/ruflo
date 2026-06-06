<section class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <x-ui.page-header :title="__('habits.pages.index.title')" :description="__('habits.pages.index.description')">
        <div class="flex flex-col gap-3 sm:min-w-80">
            <div class="grid grid-cols-3 gap-3 text-sm">
                <x-ui.stat :label="__('habits.summary.habits')" :value="count($this->habitCards)" />
                <x-ui.stat :label="__('habits.summary.checked_today')" :value="$this->checkedTodayCount" tone="success" />
                <x-ui.stat :label="__('habits.summary.streaks')" :value="$this->currentStreakTotal" />
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('dashboard')" wire:navigate variant="ghost" icon="arrow-left">
                    {{ __('habits.actions.back_to_dashboard') }}
                </flux:button>

                <flux:button :href="route('goals.index')" wire:navigate variant="subtle" icon="flag">
                    {{ __('habits.actions.open_goals') }}
                </flux:button>
            </div>
        </div>
    </x-ui.page-header>

    <flux:card class="space-y-5" data-test="habit-create">
        <div>
            <flux:heading size="lg">{{ __('habits.create.heading') }}</flux:heading>
            <flux:text class="mt-1">{{ __('habits.create.description') }}</flux:text>
        </div>

        <form wire:submit="createHabit" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1fr]">
                <div>
                    <flux:input wire:model="title" :label="__('habits.fields.title')" :placeholder="__('habits.placeholders.title')" maxlength="120" autocomplete="off" />
                    <flux:error name="title" />
                </div>

                <div>
                    <flux:select wire:model.live="frequency" :label="__('habits.fields.frequency')">
                        <flux:select.option value="daily">{{ __('habits.frequency.daily') }}</flux:select.option>
                        <flux:select.option value="weekly">{{ __('habits.frequency.weekly') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="frequency" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_12rem]">
                <div>
                    <flux:textarea wire:model="description" :label="__('habits.fields.description')" :placeholder="__('habits.placeholders.description')" rows="3" maxlength="2000" />
                    <flux:error name="description" />
                </div>

                <div>
                    <flux:input type="number" min="1" max="7" wire:model="targetCount" :label="__('habits.fields.target_count')" />
                    <flux:error name="target_count" />
                </div>
            </div>

            <div>
                <flux:select wire:model="goalId" :label="__('habits.fields.goal')">
                    <flux:select.option value="">{{ __('habits.fields.no_goal') }}</flux:select.option>
                    @foreach ($this->goals as $goal)
                        <flux:select.option value="{{ $goal->id }}">{{ $goal->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="goal_id" />
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="createHabit">
                    {{ __('habits.actions.create') }}
                </flux:button>
            </div>
        </form>
    </flux:card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2" data-test="habit-list">
        @forelse ($this->habitCards as $card)
            <flux:card wire:key="habit-card-{{ $card['habit']->id }}" class="space-y-5" data-test="habit-card">
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge size="sm" color="green" icon="calendar">{{ __('habits.badges.habit') }}</flux:badge>
                            <flux:badge size="sm" color="blue" icon="arrow-path">{{ __('habits.frequency.'.$card['habit']->frequency->value) }}</flux:badge>

                            @if ($card['habit']->goal)
                                <flux:badge size="sm" color="purple" icon="flag">{{ $card['habit']->goal->title }}</flux:badge>
                            @endif
                        </div>

                        <flux:heading size="lg" class="break-words">{{ $card['habit']->title }}</flux:heading>

                        @if ($card['habit']->description)
                            <flux:text class="break-words">{{ $card['habit']->description }}</flux:text>
                        @endif
                    </div>

                    <flux:button type="button" :variant="$card['progress']->checkedInToday ? 'ghost' : 'primary'" :icon="$card['progress']->checkedInToday ? 'arrow-path' : 'check-circle'" wire:click="toggleCheckIn({{ $card['habit']->id }})" wire:loading.attr="disabled" wire:target="toggleCheckIn({{ $card['habit']->id }})">
                        {{ $card['progress']->checkedInToday ? __('habits.actions.undo_today') : __('habits.actions.check_in_today') }}
                    </flux:button>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <flux:subheading>{{ __('habits.progress.label') }}</flux:subheading>
                        <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-300">
                            {{ __('habits.progress.text', ['completed' => $card['progress']->completedInPeriod, 'target' => $card['progress']->targetInPeriod, 'period' => __($card['progress']->periodLabelKey), 'percent' => $card['progress']->percent]) }}
                        </span>
                    </div>

                    <flux:progress :value="$card['progress']->percent" color="green" aria-label="{{ __('habits.progress.aria', ['percent' => $card['progress']->percent]) }}" />

                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                            <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('habits.summary.current_streak') }}</div>
                            <div class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ __('habits.progress.streak', ['count' => $card['progress']->currentStreak]) }}</div>
                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                            <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('habits.summary.best_streak') }}</div>
                            <div class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ __('habits.progress.streak', ['count' => $card['progress']->bestStreak]) }}</div>
                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                            <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('habits.summary.linked_tasks') }}</div>
                            <div class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ $card['habit']->todos->count() }}</div>
                        </div>
                    </div>
                </div>

                @if ($card['habit']->todos->isNotEmpty())
                    <div class="space-y-2">
                        <flux:subheading>{{ __('habits.linked_tasks.heading') }}</flux:subheading>

                        <div class="space-y-2">
                            @foreach ($card['habit']->todos as $todo)
                                <div wire:key="habit-{{ $card['habit']->id }}-task-{{ $todo->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/10 dark:bg-zinc-900">
                                    <span class="min-w-0 flex-1 break-words text-sm font-medium text-zinc-950 dark:text-white">{{ $todo->title }}</span>

                                    @if ($todo->is_completed)
                                        <flux:badge size="sm" color="green" icon="check-circle">{{ __('habits.linked_tasks.completed') }}</flux:badge>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form wire:submit="linkTodo({{ $card['habit']->id }})" class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                    <div>
                        <flux:subheading>{{ __('habits.link.heading') }}</flux:subheading>
                        <flux:text class="mt-1 text-sm">{{ __('habits.link.description') }}</flux:text>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto] sm:items-start">
                        <div>
                            <flux:select wire:model="linkTodoIds.{{ $card['habit']->id }}" :label="__('habits.fields.task')" size="sm">
                                <flux:select.option value="">{{ __('habits.fields.choose_task') }}</flux:select.option>
                                @foreach ($this->availableTodos as $todo)
                                    <flux:select.option value="{{ $todo->id }}">{{ $todo->title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="linkTodoIds.{{ $card['habit']->id }}" />
                        </div>

                        <flux:button type="submit" variant="primary" size="sm" icon="link" class="sm:mt-6" wire:loading.attr="disabled" wire:target="linkTodo({{ $card['habit']->id }})">
                            {{ __('habits.actions.link_task') }}
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        @empty
            <div class="lg:col-span-2">
                <x-ui.empty-state
                    :title="__('habits.empty.title')"
                    :description="__('habits.empty.description')"
                />
            </div>
        @endforelse
    </div>
</section>
