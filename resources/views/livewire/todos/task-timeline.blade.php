<flux:card class="space-y-5" data-test="task-timeline">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <flux:subheading>{{ __('activity.task_timeline.label') }}</flux:subheading>
            <flux:heading size="lg">{{ __('activity.task_timeline.heading') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('activity.task_timeline.description') }}
            </flux:text>
        </div>

        <div class="flex flex-wrap gap-2 sm:justify-end">
            <flux:badge size="sm" color="zinc" icon="clock">
                {{ __('activity.timeline.loaded', ['count' => $this->activities->count()]) }}
            </flux:badge>

            <flux:button :href="route('activity.index')" wire:navigate variant="ghost" size="sm" icon="arrow-top-right-on-square">
                {{ __('activity.actions.open_full_history') }}
            </flux:button>
        </div>
    </div>

    @if ($this->activities->isEmpty())
        <x-ui.empty-state
            icon="clock"
            :title="__('activity.task_timeline.empty.title')"
            :description="__('activity.task_timeline.empty.description')"
        />
    @else
        <div class="relative space-y-4" data-test="task-timeline-list">
            <div class="absolute bottom-4 start-4 top-4 w-px bg-zinc-200 dark:bg-white/10" aria-hidden="true"></div>

            @foreach ($this->activities as $activity)
                <article
                    wire:key="task-timeline-activity-{{ $activity->id }}"
                    class="relative grid gap-3 ps-11 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start"
                    data-test="task-timeline-record-{{ $activity->id }}"
                >
                    <div class="absolute start-0 top-1">
                        <flux:badge
                            size="sm"
                            :color="$this->eventColor($activity)"
                            :icon="$this->eventIcon($activity)"
                            class="relative z-10"
                        >
                            <span class="sr-only">{{ $this->eventLabel($activity) }}</span>
                        </flux:badge>
                    </div>

                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="sm">{{ $this->eventLabel($activity) }}</flux:heading>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('activity.timeline.actor_time', [
                                    'actor' => $this->actorName($activity),
                                    'time' => $activity->occurred_at->diffForHumans(),
                                ]) }}
                            </flux:text>
                        </div>

                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $this->eventDescription($activity) }}
                        </flux:text>
                    </div>

                    <time
                        class="text-xs text-zinc-500 dark:text-zinc-400 sm:text-end"
                        datetime="{{ $activity->occurred_at->toIso8601String() }}"
                    >
                        {{ $activity->occurred_at->isoFormat('MMM D, YYYY h:mm A') }}
                    </time>
                </article>
            @endforeach
        </div>

        @if ($this->hasMore)
            <div class="flex justify-center">
                <flux:button
                    type="button"
                    variant="ghost"
                    icon="arrow-down"
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    data-test="task-timeline-load-more"
                >
                    {{ __('activity.actions.load_more') }}
                </flux:button>
            </div>
        @endif
    @endif
</flux:card>
