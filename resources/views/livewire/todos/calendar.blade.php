<section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.calendar.title')" :description="__('todos.pages.calendar.description')">
        <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-5 sm:min-w-[36rem]">
            <x-ui.stat :label="__('todos.calendar.stats.month')" :value="$this->calendar['summary']['month']" />
            <x-ui.stat :label="__('todos.calendar.stats.today')" :value="$this->calendar['summary']['today']" />
            <x-ui.stat :label="__('todos.calendar.stats.overdue')" :value="$this->calendar['summary']['overdue']" tone="danger" />
            <x-ui.stat :label="__('todos.calendar.stats.upcoming')" :value="$this->calendar['summary']['upcoming']" />
            <x-ui.stat :label="__('todos.calendar.stats.no_due_date')" :value="$this->calendar['summary']['no_due_date']" tone="muted" />
        </div>
    </x-ui.page-header>

    @if ($monthWasInvalid)
        <flux:callout icon="exclamation-triangle" variant="secondary" data-test="calendar-invalid-month">
            <flux:callout.heading>{{ __('todos.calendar.invalid_month.heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('todos.calendar.invalid_month.text') }}</flux:callout.text>
        </flux:callout>
    @endif

    <flux:card class="space-y-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-1">
                <flux:subheading>{{ __('todos.calendar.selected_month') }}</flux:subheading>
                <flux:heading size="lg">{{ $this->monthLabel() }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->rangeLabel() }}</flux:text>
            </div>

            <div class="flex flex-col gap-3 lg:min-w-[26rem]">
                <form wire:submit="changeMonth" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]">
                    <div>
                        <flux:input type="month" wire:model="monthInput" :label="__('todos.calendar.month')" />
                        <flux:error name="monthInput" />
                    </div>

                    <flux:button type="submit" variant="subtle" icon="calendar-days" class="sm:mt-6" wire:loading.attr="disabled">
                        {{ __('todos.calendar.change_month') }}
                    </flux:button>
                </form>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" size="sm" variant="ghost" icon="chevron-left" wire:click="previousMonth" wire:loading.attr="disabled">
                        {{ __('todos.calendar.previous_month') }}
                    </flux:button>
                    <flux:button type="button" size="sm" variant="ghost" icon="calendar" wire:click="currentMonth" wire:loading.attr="disabled">
                        {{ __('todos.calendar.current_month') }}
                    </flux:button>
                    <flux:button type="button" size="sm" variant="ghost" icon="chevron-right" wire:click="nextMonth" wire:loading.attr="disabled">
                        {{ __('todos.calendar.next_month') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" icon="list-bullet" :href="route('todos.index')" wire:navigate>
                        {{ __('todos.calendar.open_list') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="hidden grid-cols-7 gap-2 md:grid">
            @foreach ($this->calendar['weeks'][0] as $weekday)
                <div class="px-2 text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">
                    {{ $weekday['date']->isoFormat('ddd') }}
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-7">
            @foreach ($this->calendar['weeks'] as $week)
                @foreach ($week as $day)
                    <div
                        wire:key="calendar-day-{{ $day['key'] }}"
                        data-test="calendar-day-{{ $day['key'] }}"
                        @class([
                            'min-h-36 rounded-lg border p-2',
                            'border-zinc-200 bg-white dark:border-white/10 dark:bg-white/5' => $day['in_month'],
                            'border-dashed border-zinc-200 bg-zinc-50 text-zinc-400 dark:border-white/10 dark:bg-zinc-900/50 dark:text-zinc-500' => ! $day['in_month'],
                            'ring-2 ring-amber-400 ring-offset-2 ring-offset-white dark:ring-amber-300 dark:ring-offset-zinc-950' => $day['is_today'],
                        ])
                    >
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400 md:hidden">{{ $day['weekday'] }}</div>
                                <div @class(['text-sm font-semibold', 'text-zinc-950 dark:text-white' => $day['in_month']])>{{ $day['day_number'] }}</div>
                            </div>

                            @if ($day['is_today'])
                                <flux:badge size="sm" color="amber">{{ __('todos.calendar.today_badge') }}</flux:badge>
                            @endif
                        </div>

                        @if ($day['in_month'])
                            <div class="space-y-1.5">
                                @forelse ($day['todos']->take(3) as $todo)
                                    <a
                                        wire:key="calendar-todo-{{ $day['key'] }}-{{ $todo->id }}"
                                        href="{{ route('todos.show', $todo) }}"
                                        wire:navigate
                                        class="block rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1.5 text-xs text-zinc-950 hover:border-zinc-300 dark:border-white/10 dark:bg-zinc-900 dark:text-white dark:hover:border-white/20"
                                    >
                                        <span class="block truncate font-medium">{{ $todo->title }}</span>
                                        <span class="mt-1 flex flex-wrap items-center gap-1">
                                            @if ($todo->priority->value !== 'normal')
                                                <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                                            @endif

                                            <flux:badge size="sm" :color="$this->dateTone($todo)" icon="calendar">{{ $todo->due_date->isoFormat('MMM D') }}</flux:badge>

                                            @if ($todo->project)
                                                <flux:badge size="sm" :color="$todo->project->color" icon="folder">{{ $todo->project->name }}</flux:badge>
                                            @endif
                                        </span>
                                    </a>
                                @empty
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('todos.calendar.empty_day') }}</flux:text>
                                @endforelse

                                @if ($day['todos']->count() > 3)
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('todos.calendar.more_tasks', ['count' => $day['todos']->count() - 3]) }}
                                    </flux:text>
                                @endif
                            </div>
                        @else
                            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('todos.calendar.outside_month') }}</flux:text>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </flux:card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <flux:card class="space-y-4">
            <div>
                <flux:subheading>{{ __('todos.calendar.no_due_date.heading') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.fields.no_due_date') }}</flux:heading>
            </div>

            <div class="space-y-2">
                @forelse ($this->calendar['no_due_tasks'] as $todo)
                    <a
                        wire:key="calendar-no-due-{{ $todo->id }}"
                        href="{{ route('todos.show', $todo) }}"
                        wire:navigate
                        class="block rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-950 hover:border-zinc-300 dark:border-white/10 dark:bg-zinc-900 dark:text-white dark:hover:border-white/20"
                    >
                        <span class="block truncate font-medium">{{ $todo->title }}</span>
                        <span class="mt-1 flex flex-wrap items-center gap-1.5">
                            @if ($todo->priority->value !== 'normal')
                                <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                            @endif

                            @if ($todo->project)
                                <flux:badge size="sm" :color="$todo->project->color" icon="folder">{{ $todo->project->name }}</flux:badge>
                            @endif
                        </span>
                    </a>
                @empty
                    <x-ui.empty-state
                        :title="__('todos.calendar.no_due_date.empty_title')"
                        :description="__('todos.calendar.no_due_date.empty_description')"
                    />
                @endforelse
            </div>
        </flux:card>

        <flux:callout icon="bell" variant="secondary">
            <flux:callout.heading>{{ __('todos.calendar.reminders.heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('todos.calendar.reminders.description') }}</flux:callout.text>
        </flux:callout>

        <flux:callout icon="arrow-path" variant="secondary">
            <flux:callout.heading>{{ __('todos.calendar.recurrence.heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('todos.calendar.recurrence.description') }}</flux:callout.text>
        </flux:callout>
    </div>
</section>
