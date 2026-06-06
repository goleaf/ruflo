<section
    class="mx-auto flex w-full max-w-5xl flex-col gap-6"
    x-data="{
        seconds: 1500,
        timer: null,
        format() {
            const minutes = String(Math.floor(this.seconds / 60)).padStart(2, '0');
            const seconds = String(this.seconds % 60).padStart(2, '0');

            return `${minutes}:${seconds}`;
        },
        toggle() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;

                return;
            }

            this.timer = setInterval(() => {
                if (this.seconds > 0) {
                    this.seconds--;

                    return;
                }

                clearInterval(this.timer);
                this.timer = null;
            }, 1000);
        },
        reset() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }

            this.seconds = 1500;
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
        <flux:card class="space-y-5">
            <div>
                <flux:subheading>{{ __('todos.focus.timer.label') }}</flux:subheading>
                <div class="mt-2 font-mono text-5xl font-semibold tabular-nums text-zinc-950 dark:text-white" x-text="format()"></div>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button type="button" variant="primary" icon="play" x-on:click="toggle" x-show="!timer" kbd="Space">
                    {{ __('todos.focus.timer.start') }}
                </flux:button>

                <flux:button type="button" variant="primary" icon="pause" x-on:click="toggle" x-show="timer" kbd="Space">
                    {{ __('todos.focus.timer.pause') }}
                </flux:button>

                <flux:button type="button" variant="ghost" icon="arrow-path" x-on:click="reset">
                    {{ __('todos.focus.timer.reset') }}
                </flux:button>
            </div>
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
