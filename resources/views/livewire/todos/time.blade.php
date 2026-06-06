<section
    wire:key="time-page-{{ $this->activeEntry?->id ?? 'idle' }}-{{ $this->activeEntry?->updated_at?->timestamp ?? 0 }}"
    class="mx-auto flex w-full max-w-6xl flex-col gap-6"
    x-data="{
        seconds: @js($this->activeElapsedSeconds()),
        timer: null,
        running: @js($this->activeEntry !== null),
        init() {
            if (this.running) {
                this.startInterval();
            }
        },
        format() {
            const hours = Math.floor(this.seconds / 3600);
            const minutes = String(Math.floor((this.seconds % 3600) / 60)).padStart(2, '0');
            const seconds = String(this.seconds % 60).padStart(2, '0');

            return `${String(hours).padStart(2, '0')}:${minutes}:${seconds}`;
        },
        startInterval() {
            if (this.timer) {
                return;
            }

            this.timer = setInterval(() => {
                this.seconds++;
            }, 1000);
        },
        stopInterval() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        startOrStop() {
            if (this.running) {
                this.stopInterval();
                this.running = false;
                this.$wire.stopTimer();

                return;
            }

            this.$wire.startTimer();
        },
        discard() {
            if (! this.running) {
                return;
            }

            this.stopInterval();
            this.running = false;
            this.$wire.discardTimer();
        },
    }"
    x-on:keydown.window="
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName)) return;

        if ($event.key.toLowerCase() === 't') {
            $event.preventDefault();
            startOrStop();
        }

        if ($event.key.toLowerCase() === 'x') {
            $event.preventDefault();
            discard();
        }
    "
>
    <x-ui.page-header :title="__('todos.pages.time.title')" :description="__('todos.pages.time.description')">
        <div class="flex flex-col gap-3 sm:min-w-[34rem]">
            <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <x-ui.stat :label="__('todos.time.summary.today')" :value="$this->formatSeconds($this->summary['today_seconds'])" tone="success" />
                <x-ui.stat :label="__('todos.time.summary.week')" :value="$this->formatSeconds($this->summary['week_seconds'])" />
                <x-ui.stat :label="__('todos.time.summary.total')" :value="$this->formatSeconds($this->summary['total_seconds'])" />
                <x-ui.stat :label="__('todos.time.summary.active')" :value="$this->formatSeconds($this->summary['active_seconds'])" tone="muted" />
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                    {{ __('todos.actions.back_to_list') }}
                </flux:button>

                <flux:button :href="route('todos.focus')" wire:navigate variant="subtle" icon="bolt">
                    {{ __('todos.focus.actions.open_focus') }}
                </flux:button>
            </div>
        </div>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
        <flux:card class="space-y-5" data-test="time-timer">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <flux:subheading>{{ __('todos.time.timer.label') }}</flux:subheading>
                    <flux:heading size="lg">{{ __('todos.time.timer.heading') }}</flux:heading>
                    <flux:text>{{ __('todos.time.timer.description') }}</flux:text>
                </div>

                @if ($this->activeEntry)
                    <flux:badge size="sm" :color="$this->activeEntry->status->color()" icon="clock">
                        {{ __('todos.time.status.'.$this->activeEntry->status->value) }}
                    </flux:badge>
                @endif
            </div>

            <div class="font-mono text-5xl font-semibold tabular-nums text-zinc-950 dark:text-white" x-text="format()"></div>

            @if ($this->activeEntry)
                <div class="space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                    <flux:subheading>{{ __('todos.time.timer.current_context') }}</flux:subheading>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($this->activeEntry->todo)
                            <a href="{{ route('todos.show', $this->activeEntry->todo) }}" wire:navigate>
                                <flux:badge size="sm" color="blue" icon="check-circle">{{ $this->activeEntry->todo->title }}</flux:badge>
                            </a>
                        @endif

                        @if ($this->activeEntry->project)
                            <a href="{{ route('projects.show', $this->activeEntry->project) }}" wire:navigate>
                                <flux:badge size="sm" :color="$this->activeEntry->project->color" icon="folder">{{ $this->activeEntry->project->name }}</flux:badge>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" variant="primary" icon="stop" x-on:click="startOrStop" wire:loading.attr="disabled" kbd="T">
                        {{ __('todos.time.actions.stop_timer') }}
                    </flux:button>

                    <flux:button type="button" variant="ghost" icon="x-mark" x-on:click="discard" wire:loading.attr="disabled" kbd="X">
                        {{ __('todos.time.actions.discard_timer') }}
                    </flux:button>
                </div>
            @else
                <form wire:submit="startTimer" class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <flux:select wire:model="timerTodoId" :label="__('todos.time.fields.task')">
                                <flux:select.option value="">{{ __('todos.time.fields.no_task') }}</flux:select.option>
                                @foreach ($this->taskOptions as $todo)
                                    <flux:select.option value="{{ $todo->id }}">{{ $todo->title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="timerTodoId" />
                        </div>

                        <div>
                            <flux:select wire:model="timerProjectId" :label="__('todos.time.fields.project')">
                                <flux:select.option value="">{{ __('todos.time.fields.no_project') }}</flux:select.option>
                                @foreach ($this->projectOptions as $project)
                                    <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="timerProjectId" />
                            <flux:error name="project_id" />
                        </div>
                    </div>

                    <flux:error name="timer" />
                    <flux:error name="context" />

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary" icon="play" wire:loading.attr="disabled" kbd="T">
                            {{ __('todos.time.actions.start_timer') }}
                        </flux:button>
                    </div>
                </form>
            @endif

            <flux:text class="text-sm">{{ __('todos.time.timer.hosting_note') }}</flux:text>
        </flux:card>

        <flux:card class="space-y-5" data-test="manual-time-entry">
            <div class="space-y-1">
                <flux:subheading>{{ __('todos.time.manual.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.time.manual.heading') }}</flux:heading>
                <flux:text>{{ __('todos.time.manual.description') }}</flux:text>
            </div>

            <form wire:submit="createManualEntry" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <flux:select wire:model="manualTodoId" :label="__('todos.time.fields.task')">
                            <flux:select.option value="">{{ __('todos.time.fields.no_task') }}</flux:select.option>
                            @foreach ($this->taskOptions as $todo)
                                <flux:select.option value="{{ $todo->id }}">{{ $todo->title }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="manualTodoId" />
                    </div>

                    <div>
                        <flux:select wire:model="manualProjectId" :label="__('todos.time.fields.project')">
                            <flux:select.option value="">{{ __('todos.time.fields.no_project') }}</flux:select.option>
                            @foreach ($this->projectOptions as $project)
                                <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="manualProjectId" />
                        <flux:error name="project_id" />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-[10rem_1fr]">
                    <div>
                        <flux:input type="number" min="1" max="1440" wire:model="manualMinutes" :label="__('todos.time.fields.minutes')" />
                        <flux:error name="manualMinutes" />
                    </div>

                    <div>
                        <flux:input type="date" wire:model="manualEntryDate" :label="__('todos.time.fields.entry_date')" />
                        <flux:error name="manualEntryDate" />
                    </div>
                </div>

                <div>
                    <flux:textarea wire:model="manualNotes" :label="__('todos.time.fields.notes')" :placeholder="__('todos.time.placeholders.notes')" rows="3" maxlength="500" />
                    <flux:error name="manualNotes" />
                </div>

                <flux:error name="context" />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled">
                        {{ __('todos.time.actions.log_manual') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>

    <flux:card class="space-y-5" data-test="time-entry-list">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
                <flux:subheading>{{ __('todos.time.entries.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.time.entries.heading') }}</flux:heading>
            </div>

            <flux:badge color="blue" icon="clock">{{ __('todos.time.entries.count', ['count' => $this->recentEntries->count()]) }}</flux:badge>
        </div>

        <div class="space-y-2">
            @forelse ($this->recentEntries as $entry)
                <div wire:key="time-entry-{{ $entry->id }}" class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-white/10 dark:bg-zinc-900 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <flux:badge size="sm" :color="$entry->source->color()" :icon="$entry->source->icon()">{{ __('todos.time.source.'.$entry->source->value) }}</flux:badge>
                            <flux:badge size="sm" color="zinc" icon="calendar">{{ $this->entryDateLabel($entry) }}</flux:badge>

                            @if ($entry->todo)
                                <a href="{{ route('todos.show', $entry->todo) }}" wire:navigate>
                                    <flux:badge size="sm" color="blue" icon="check-circle">{{ $entry->todo->title }}</flux:badge>
                                </a>
                            @endif

                            @if ($entry->project)
                                <a href="{{ route('projects.show', $entry->project) }}" wire:navigate>
                                    <flux:badge size="sm" :color="$entry->project->color" icon="folder">{{ $entry->project->name }}</flux:badge>
                                </a>
                            @endif
                        </div>

                        @if ($entry->notes)
                            <flux:text class="break-words text-sm">{{ $entry->notes }}</flux:text>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <div class="font-mono text-sm font-semibold tabular-nums text-zinc-950 dark:text-white">
                            {{ $this->formatSeconds($entry->duration_seconds) }}
                        </div>

                        <flux:button
                            type="button"
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            square
                            wire:click="deleteEntry({{ $entry->id }})"
                            wire:confirm="{{ __('todos.confirmations.delete_time_entry') }}"
                            :aria-label="__('todos.time.actions.delete_entry')"
                        />
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.time.empty.title')"
                    :description="__('todos.time.empty.description')"
                />
            @endforelse
        </div>
    </flux:card>
</section>
