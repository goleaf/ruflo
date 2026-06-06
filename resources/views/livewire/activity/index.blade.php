<x-ui.page-container>
    <x-ui.page-header :title="__('activity.pages.index.title')" :description="__('activity.pages.index.description')">
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('dashboard')" wire:navigate variant="ghost" icon="arrow-left">
                {{ __('activity.actions.back_to_dashboard') }}
            </flux:button>

            <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="list-bullet">
                {{ __('activity.actions.open_tasks') }}
            </flux:button>
        </div>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 text-sm lg:grid-cols-4" data-test="activity-summary">
        <x-ui.stat :label="__('activity.summary.total')" :value="$this->summary['total']" tone="muted" />
        <x-ui.stat :label="__('activity.summary.today')" :value="$this->summary['today']" tone="success" />
        <x-ui.stat :label="__('activity.summary.tasks')" :value="$this->summary['tasks']" tone="cyan" />
        <x-ui.stat :label="__('activity.summary.checklist')" :value="$this->summary['checklist']" tone="warning" />
    </div>

    <flux:card class="space-y-5" data-test="activity-history" aria-labelledby="activity-history-heading">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <flux:subheading>{{ __('activity.timeline.label') }}</flux:subheading>
                <flux:heading id="activity-history-heading" size="lg">{{ __('activity.timeline.heading') }}</flux:heading>
                <flux:text>{{ __('activity.timeline.description') }}</flux:text>
            </div>

            <flux:badge color="blue" icon="clock" class="w-fit">
                {{ __('activity.timeline.loaded', ['count' => $this->activities->count()]) }}
            </flux:badge>
        </div>

        @if ($this->activities->isEmpty())
            <x-ui.empty-state :title="__('activity.empty.title')" :description="__('activity.empty.description')" />
        @else
            <ol class="relative space-y-4 border-s border-zinc-200 ps-5 dark:border-white/10" data-test="activity-list">
                @foreach ($this->activities as $activity)
                    <li wire:key="activity-{{ $activity->id }}" class="relative rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-zinc-950" data-test="activity-record-{{ $activity->id }}">
                        <span class="absolute -start-[1.65rem] top-5 flex size-3 rounded-full bg-blue-600 ring-4 ring-white dark:bg-blue-400 dark:ring-zinc-950" aria-hidden="true"></span>

                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge size="sm" :color="$this->eventColor($activity)" :icon="$this->eventIcon($activity)">{{ $this->eventLabel($activity) }}</flux:badge>

                                    <time class="text-xs text-zinc-500 dark:text-zinc-400" datetime="{{ $activity->occurred_at?->toIso8601String() }}">
                                        {{ __('activity.timeline.actor_time', ['actor' => $this->actorName($activity), 'time' => $activity->occurred_at?->diffForHumans()]) }}
                                    </time>
                                </div>

                                <div class="space-y-1">
                                    <h2 class="break-words text-base font-semibold text-zinc-950 dark:text-white">
                                        {{ $activity->subject_title ?? __('activity.subjects.deleted') }}
                                    </h2>

                                    <p class="break-words text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $this->eventDescription($activity) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                @if ($subjectUrl = $this->subjectUrl($activity))
                                    <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="$subjectUrl" wire:navigate data-test="activity-subject-link-{{ $activity->id }}">
                                        {{ __('activity.actions.open_subject') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($this->hasMore)
            <div class="flex justify-center">
                <flux:button type="button" variant="ghost" icon="arrow-down" wire:click="loadMore" data-test="activity-load-more">
                    {{ __('activity.actions.load_more') }}
                </flux:button>
            </div>
        @endif
    </flux:card>
</x-ui.page-container>
