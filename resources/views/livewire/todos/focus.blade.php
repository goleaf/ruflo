<section
    wire:key="focus-page-{{ $this->activeSession?->id ?? 'idle' }}-{{ $this->activeSession?->status?->value ?? 'none' }}-{{ $this->activeSession?->updated_at?->timestamp ?? 0 }}-{{ $durationMinutes }}"
    class="mx-auto flex w-full max-w-5xl flex-col gap-6"
    x-data="{
        seconds: @js($this->activeSessionRemainingSeconds()),
        timer: null,
        running: @js($this->activeSession?->isRunning() ?? false),
        hasSession: @js($this->activeSession !== null),
        finishing: false,
        init() {
            if (this.running) {
                this.startInterval();
            }
        },
        format() {
            const minutes = String(Math.floor(this.seconds / 60)).padStart(2, '0');
            const seconds = String(this.seconds % 60).padStart(2, '0');

            return `${minutes}:${seconds}`;
        },
        startInterval() {
            if (this.timer) {
                return;
            }

            this.timer = setInterval(() => {
                if (this.seconds > 0) {
                    this.seconds--;

                    return;
                }

                this.finishSession();
            }, 1000);
        },
        stopInterval() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        toggleSession() {
            if (! this.hasSession) {
                this.$wire.startFocusSession();

                return;
            }

            if (this.running) {
                this.pauseSession();

                return;
            }

            this.resumeSession();
        },
        pauseSession() {
            this.stopInterval();
            this.running = false;
            this.$wire.pauseFocusSession();
        },
        resumeSession() {
            this.running = true;
            this.startInterval();
            this.$wire.resumeFocusSession();
        },
        finishSession() {
            if (this.finishing || ! this.hasSession) {
                return;
            }

            this.finishing = true;
            this.stopInterval();
            this.running = false;
            this.seconds = 0;
            this.$wire.completeFocusSession();
        },
        abandonSession() {
            if (! this.hasSession) {
                return;
            }

            this.stopInterval();
            this.running = false;
            this.$wire.abandonFocusSession();
        },
    }"
    x-on:keydown.window="
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName)) return;

        if ($event.key.toLowerCase() === 'c') {
            $event.preventDefault();
            $wire.completeSelected();
        }

        if ($event.key.toLowerCase() === 'd') {
            $event.preventDefault();
            $wire.deferSelected();
        }

        if ($event.key.toLowerCase() === 's') {
            $event.preventDefault();
            $wire.snoozeSelected();
        }

        if ($event.key.toLowerCase() === 'p' || $event.code === 'Space') {
            $event.preventDefault();
            toggleSession();
        }
    "
>
    <x-ui.page-header :title="__('todos.pages.focus.title')" :description="__('todos.pages.focus.description')">
        <div class="flex flex-col gap-3 sm:min-w-72">
            <x-ui.stat :label="__('todos.focus.count')" :value="$this->focusTodos->count()" />

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                    {{ __('todos.actions.back_to_list') }}
                </flux:button>
            </div>
        </div>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-[0.75fr_1.25fr]">
        <flux:card class="space-y-5" data-test="pomodoro-timer">
            <div class="space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:subheading>{{ __('todos.focus.timer.label') }}</flux:subheading>
                        <flux:heading size="lg">{{ __('todos.focus.timer.heading') }}</flux:heading>
                    </div>

                    @if ($this->activeSession)
                        <flux:badge size="sm" :color="$this->sessionStatusColor($this->activeSession)" icon="clock">
                            {{ __('todos.focus.timer.status.'.$this->activeSession->status->value) }}
                        </flux:badge>
                    @endif
                </div>

                <div class="font-mono text-5xl font-semibold tabular-nums text-zinc-950 dark:text-white" x-text="format()"></div>

                @if ($this->activeSession)
                    <flux:progress :value="$this->activeSessionProgress()" color="green" aria-label="{{ __('todos.focus.timer.progress_aria', ['percent' => $this->activeSessionProgress()]) }}" />

                    <div class="space-y-1 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900">
                        <flux:subheading>{{ __('todos.focus.timer.current_task') }}</flux:subheading>
                        <a href="{{ route('todos.show', $this->activeSession->todo) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 dark:text-white">
                            {{ $this->activeSession->todo->title }}
                        </a>
                    </div>
                @else
                    <div>
                        <flux:select wire:model.live="durationMinutes" :label="__('todos.focus.timer.duration')">
                            @foreach ($this->durationOptions() as $minutes)
                                <flux:select.option value="{{ $minutes }}">{{ __('todos.focus.timer.duration_option', ['minutes' => $minutes]) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="durationMinutes" />
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($this->activeSession)
                    <flux:button type="button" variant="primary" icon="pause" x-on:click="pauseSession" x-show="running" kbd="P">
                        {{ __('todos.focus.timer.pause') }}
                    </flux:button>

                    <flux:button type="button" variant="primary" icon="play" x-on:click="resumeSession" x-show="! running" kbd="P">
                        {{ __('todos.focus.timer.resume') }}
                    </flux:button>

                    <flux:button type="button" variant="subtle" icon="check-circle" x-on:click="finishSession" wire:loading.attr="disabled">
                        {{ __('todos.focus.timer.complete') }}
                    </flux:button>

                    <flux:button type="button" variant="ghost" icon="x-mark" x-on:click="abandonSession" wire:loading.attr="disabled">
                        {{ __('todos.focus.timer.abandon') }}
                    </flux:button>
                @else
                    <flux:button type="button" variant="primary" icon="play" x-on:click="toggleSession" wire:loading.attr="disabled" :disabled="$this->focusTodos->isEmpty()" kbd="P">
                        {{ __('todos.focus.timer.start') }}
                    </flux:button>
                @endif
            </div>

            <flux:text class="text-sm">{{ __('todos.focus.timer.hosting_note') }}</flux:text>
        </flux:card>

        <flux:card class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <flux:subheading>{{ __('todos.focus.label') }}</flux:subheading>
                    <flux:heading size="lg">{{ __('todos.focus.heading') }}</flux:heading>
                    <flux:text>{{ __('todos.focus.description') }}</flux:text>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" size="sm" variant="primary" icon="check" wire:click="completeSelected" wire:loading.attr="disabled" kbd="C" :disabled="$this->focusTodos->isEmpty()">
                        {{ __('todos.actions.complete') }}
                    </flux:button>

                    <flux:button type="button" size="sm" variant="subtle" icon="arrow-uturn-right" wire:click="deferSelected" wire:loading.attr="disabled" kbd="D" :disabled="$this->focusTodos->isEmpty()">
                        {{ __('todos.focus.actions.defer') }}
                    </flux:button>

                    <flux:button type="button" size="sm" variant="ghost" icon="clock" wire:click="snoozeSelected" wire:loading.attr="disabled" kbd="S" :disabled="$this->focusTodos->isEmpty()">
                        {{ __('todos.focus.actions.snooze') }}
                    </flux:button>
                </div>
            </div>

            @if ($this->focusTodos->isNotEmpty())
                <flux:callout icon="exclamation-triangle" variant="secondary">
                    <flux:callout.text>{{ __('todos.focus.urgent_note') }}</flux:callout.text>
                </flux:callout>
            @endif

            <div class="space-y-2">
                @forelse ($this->focusTodos as $todo)
                    <div
                        wire:key="focus-todo-{{ $todo->id }}"
                        @class([
                            'flex min-h-20 items-start gap-3 rounded-lg border px-3 py-3 transition',
                            'border-blue-300 bg-blue-50 dark:border-blue-500/40 dark:bg-blue-500/10' => $this->isSelected($todo),
                            'border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-zinc-900' => ! $this->isSelected($todo),
                        ])
                    >
                        <flux:button
                            size="sm"
                            variant="ghost"
                            square
                            icon="cursor-arrow-rays"
                            wire:click="selectTask({{ $todo->id }})"
                            :aria-label="__('todos.focus.actions.select')"
                        />

                        <div class="min-w-0 flex-1 space-y-1.5">
                            <a href="{{ route('todos.show', $todo) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 dark:text-white">
                                {{ $todo->title }}
                            </a>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <flux:badge size="sm" :color="$todo->priority->color()" icon="flag">{{ $todo->priority->label() }}</flux:badge>
                                <flux:badge size="sm" :color="$this->dueBadgeColor($todo)" icon="calendar">{{ $this->dueBadgeLabel($todo) }}</flux:badge>

                                @if ($todo->project)
                                    <a href="{{ route('projects.show', $todo->project) }}" wire:navigate>
                                        <flux:badge size="sm" :color="$todo->project->color" icon="folder">{{ $todo->project->name }}</flux:badge>
                                    </a>
                                @endif

                                @foreach ($todo->tags as $tagBadge)
                                    <a href="{{ route('todos.index', ['tag' => $tagBadge->id]) }}" wire:navigate>
                                        <flux:badge wire:key="focus-tag-{{ $todo->id }}-{{ $tagBadge->id }}" size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5 sm:flex-row">
                            <flux:button type="button" size="sm" variant="ghost" icon="check" square wire:click="completeTodo({{ $todo->id }})" :aria-label="__('todos.actions.complete')" />
                            <flux:button type="button" size="sm" variant="ghost" icon="arrow-uturn-right" square wire:click="deferTodo({{ $todo->id }})" :aria-label="__('todos.focus.actions.defer')" />
                            <flux:button type="button" size="sm" variant="ghost" icon="clock" square wire:click="snoozeTodo({{ $todo->id }})" :aria-label="__('todos.focus.actions.snooze')" />
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state
                        :title="__('todos.focus.empty.title')"
                        :description="__('todos.focus.empty.description')"
                    />
                @endforelse
            </div>
        </flux:card>
    </div>
</section>
