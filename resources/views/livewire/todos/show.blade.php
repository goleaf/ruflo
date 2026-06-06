<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.show.title')" :description="__('todos.pages.show.description')">
        <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
            {{ __('todos.actions.back_to_list') }}
        </flux:button>
    </x-ui.page-header>

    <flux:card class="space-y-6">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.status-badge :status="$this->todo->status()" />
                <flux:badge size="sm" :color="$this->todo->priority->color()">{{ $this->todo->priority->label() }}</flux:badge>

                @if ($this->openDependencies->isNotEmpty())
                    <flux:badge size="sm" color="amber" icon="exclamation-triangle">
                        {{ __('todos.dependencies.blocked_badge', ['count' => $this->openDependencies->count()]) }}
                    </flux:badge>
                @endif

                @if ($this->todo->due_date)
                    <flux:badge size="sm" :color="$this->todo->isOverdue() ? 'red' : ($this->todo->isDueToday() ? 'amber' : 'zinc')" icon="calendar">
                        {{ $this->todo->due_date->isoFormat('MMM D, YYYY') }}
                    </flux:badge>
                @endif
            </div>

            <flux:heading size="xl">{{ $this->todo->title }}</flux:heading>
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.status') }}</dt>
                <dd class="mt-1 text-sm font-medium text-zinc-950 dark:text-white">{{ $this->todo->status()->label() }}</dd>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.priority') }}</dt>
                <dd class="mt-1 text-sm font-medium text-zinc-950 dark:text-white">{{ $this->todo->priority->label() }}</dd>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.due_date') }}</dt>
                <dd class="mt-1 text-sm font-medium text-zinc-950 dark:text-white">
                    {{ $this->todo->due_date?->isoFormat('MMM D, YYYY') ?? __('todos.fields.no_due_date') }}
                </dd>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.project') }}</dt>
                <dd class="mt-1 text-sm font-medium text-zinc-950 dark:text-white">
                    @if ($this->todo->project)
                        <a href="{{ route('projects.show', $this->todo->project) }}" wire:navigate class="hover:underline">
                            {{ $this->todo->project->name }}
                        </a>
                    @else
                        {{ __('todos.fields.no_project') }}
                    @endif
                </dd>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.created_at') }}</dt>
                <dd class="mt-1 text-sm font-medium text-zinc-950 dark:text-white">{{ $this->todo->created_at->isoFormat('MMM D, YYYY h:mm A') }}</dd>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.updated_at') }}</dt>
                <dd class="mt-1 text-sm font-medium text-zinc-950 dark:text-white">{{ $this->todo->updated_at->isoFormat('MMM D, YYYY h:mm A') }}</dd>
            </div>
        </dl>

        <div class="space-y-2">
            <flux:subheading>{{ __('todos.fields.tags') }}</flux:subheading>

            <div class="flex flex-wrap gap-1.5">
                @forelse ($this->todo->tags as $tagBadge)
                    <a href="{{ route('todos.index', ['tag' => $tagBadge->id]) }}" wire:navigate>
                        <flux:badge wire:key="detail-tag-{{ $tagBadge->id }}" size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                    </a>
                @empty
                    <flux:text class="text-sm">{{ __('todos.fields.no_tags') }}</flux:text>
                @endforelse
            </div>
        </div>
    </flux:card>

    <livewire:todos.task-timeline :todo-id="$this->todo->id" :key="'task-timeline-'.$this->todo->id" />

    <flux:card class="space-y-5" data-test="task-recurrence">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <flux:subheading>{{ __('todos.recurrence.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.recurrence.heading') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('todos.recurrence.description') }}</flux:text>
            </div>

            @if ($this->recurrenceRule)
                <div class="flex flex-wrap gap-2 sm:justify-end">
                    <flux:badge size="sm" :color="$this->recurrenceRule->frequency->color()" icon="arrow-path">
                        {{ $this->recurrenceRule->frequency->label() }}
                    </flux:badge>

                    <flux:badge size="sm" :color="$this->recurrenceRule->statusColor()" icon="bolt">
                        {{ $this->recurrenceRule->statusLabel() }}
                    </flux:badge>
                </div>
            @else
                <flux:badge size="sm" color="zinc" icon="arrow-path">
                    {{ __('todos.recurrence.status.not_set') }}
                </flux:badge>
            @endif
        </div>

        @if ($this->recurrenceRule)
            <flux:callout icon="calendar-days" variant="secondary" data-test="recurrence-summary">
                <flux:callout.heading>{{ __('todos.recurrence.summary_heading') }}</flux:callout.heading>
                <flux:callout.text>{{ $this->recurrenceRule->summary() }}</flux:callout.text>
            </flux:callout>
        @else
            <x-ui.empty-state
                :title="__('todos.recurrence.empty.title')"
                :description="__('todos.recurrence.empty.description')"
                data-test="recurrence-empty"
            />
        @endif

        @if (! $this->canManageRecurrence())
            <flux:callout icon="archive-box" variant="secondary" data-test="recurrence-locked">
                <flux:callout.heading>{{ __('todos.recurrence.locked.heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('todos.recurrence.locked.description') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form wire:submit="saveRecurrenceRule" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-3">
                <flux:select variant="combobox" wire:model.live="recurrenceFrequency" :label="__('todos.recurrence.fields.frequency')" :disabled="! $this->canManageRecurrence()">
                    @foreach ($this->recurrenceFrequencyOptions as $frequencyOption)
                        <flux:select.option value="{{ $frequencyOption->value }}">{{ $frequencyOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    type="number"
                    min="1"
                    max="30"
                    wire:model="recurrenceInterval"
                    :label="__('todos.recurrence.fields.interval')"
                    :disabled="! $this->canManageRecurrence()"
                />

                <flux:input
                    type="date"
                    wire:model="recurrenceStartsOn"
                    :label="__('todos.recurrence.fields.starts_on')"
                    :disabled="! $this->canManageRecurrence()"
                />
            </div>

            <flux:error name="recurrenceFrequency" />
            <flux:error name="recurrenceInterval" />
            <flux:error name="recurrenceStartsOn" />

            @if ($recurrenceFrequency === 'weekly')
                <flux:checkbox.group wire:model="recurrenceWeekdays" :label="__('todos.recurrence.fields.weekdays')" variant="cards" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($this->recurrenceWeekdayOptions as $weekdayOption)
                        <flux:checkbox
                            wire:key="recurrence-weekday-{{ $weekdayOption->value }}"
                            value="{{ $weekdayOption->value }}"
                            :label="$weekdayOption->label()"
                            :disabled="! $this->canManageRecurrence()"
                        />
                    @endforeach

                    <flux:error name="recurrenceWeekdays" />
                </flux:checkbox.group>
            @endif

            @if ($recurrenceFrequency === 'monthly')
                <flux:input
                    type="number"
                    min="1"
                    max="31"
                    wire:model="recurrenceMonthDay"
                    :label="__('todos.recurrence.fields.month_day')"
                    :disabled="! $this->canManageRecurrence()"
                />

                <flux:error name="recurrenceMonthDay" />
            @endif

            <div class="grid gap-4 lg:grid-cols-3">
                <flux:select variant="combobox" wire:model.live="recurrenceEndType" :label="__('todos.recurrence.fields.end_type')" :disabled="! $this->canManageRecurrence()">
                    @foreach ($this->recurrenceEndTypeOptions as $endTypeOption)
                        <flux:select.option value="{{ $endTypeOption->value }}">{{ $endTypeOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($recurrenceEndType === 'on_date')
                    <flux:input
                        type="date"
                        wire:model="recurrenceEndsOn"
                        :label="__('todos.recurrence.fields.ends_on')"
                        :disabled="! $this->canManageRecurrence()"
                    />
                @endif

                @if ($recurrenceEndType === 'after_occurrences')
                    <flux:input
                        type="number"
                        min="1"
                        max="365"
                        wire:model="recurrenceMaxOccurrences"
                        :label="__('todos.recurrence.fields.max_occurrences')"
                        :disabled="! $this->canManageRecurrence()"
                    />
                @endif
            </div>

            <flux:error name="recurrenceEndType" />
            <flux:error name="recurrenceEndsOn" />
            <flux:error name="recurrenceMaxOccurrences" />
            <flux:error name="recurrenceRule" />

            <label class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 text-sm dark:border-white/10">
                <flux:checkbox wire:model="recurrenceEnabled" :disabled="! $this->canManageRecurrence()" />
                <span class="space-y-1">
                    <span class="block font-medium text-zinc-950 dark:text-white">{{ __('todos.recurrence.fields.enabled') }}</span>
                    <span class="block text-zinc-500 dark:text-zinc-400">{{ __('todos.recurrence.fields.enabled_help') }}</span>
                </span>
            </label>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                @if ($this->recurrenceRule)
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        wire:click="clearRecurrenceRule"
                        wire:confirm="{{ __('todos.confirmations.clear_recurrence') }}"
                        wire:loading.attr="disabled"
                        :disabled="! $this->canManageRecurrence()"
                    >
                        {{ __('todos.recurrence.actions.clear') }}
                    </flux:button>
                @endif

                <flux:button
                    type="submit"
                    variant="primary"
                    icon="arrow-path"
                    wire:loading.attr="disabled"
                    :disabled="! $this->canManageRecurrence()"
                >
                    {{ $this->recurrenceRule ? __('todos.recurrence.actions.update') : __('todos.recurrence.actions.save') }}
                </flux:button>
            </div>
        </form>
    </flux:card>

    <flux:card class="space-y-5" data-test="task-dependencies">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <flux:subheading>{{ __('todos.dependencies.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.dependencies.heading') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('todos.dependencies.description') }}</flux:text>
            </div>

            <flux:badge size="sm" :color="$this->openDependencies->isNotEmpty() ? 'amber' : 'green'" icon="link">
                {{ __('todos.dependencies.open_count', ['count' => $this->openDependencies->count()]) }}
            </flux:badge>
        </div>

        @if ($this->openDependencies->isNotEmpty())
            <flux:callout icon="exclamation-triangle" variant="secondary" data-test="task-blocked-callout">
                <flux:callout.heading>{{ __('todos.dependencies.waiting_heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('todos.dependencies.waiting_description') }}</flux:callout.text>
            </flux:callout>
        @endif

        @if (! $this->canManageDependencies())
            <flux:callout icon="archive-box" variant="secondary" data-test="dependencies-locked">
                <flux:callout.heading>{{ __('todos.dependencies.locked.heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('todos.dependencies.locked.description') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form wire:submit="addDependency" class="space-y-2">
            <flux:field>
                <flux:label>{{ __('todos.dependencies.fields.blocker') }}</flux:label>

                <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <flux:select wire:model="dependencyTodoId" :disabled="! $this->canManageDependencies()">
                        <flux:select.option value="">{{ __('todos.dependencies.fields.choose_blocker') }}</flux:select.option>
                        @foreach ($this->dependencyOptions as $dependencyOption)
                            <flux:select.option value="{{ $dependencyOption->id }}">{{ $dependencyOption->title }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="link"
                        wire:loading.attr="disabled"
                        :disabled="! $this->canManageDependencies()"
                    >
                        {{ __('todos.dependencies.actions.add') }}
                    </flux:button>
                </div>

                <flux:error name="dependencyTodoId" />
            </flux:field>
        </form>

        <div class="space-y-2">
            @forelse ($this->dependencies as $dependency)
                <div wire:key="dependency-{{ $dependency->id }}" class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-3 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 space-y-1">
                        @if ($dependency->blocker)
                            <a href="{{ route('todos.show', $dependency->blocker) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 hover:underline dark:text-white">
                                {{ $dependency->blocker->title }}
                            </a>
                        @else
                            <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('todos.dependencies.missing_blocker') }}</span>
                        @endif

                        <div>
                            <flux:badge size="sm" :color="$dependency->isOpen() ? 'amber' : 'green'" :icon="$dependency->isOpen() ? 'exclamation-triangle' : 'check-circle'">
                                {{ $dependency->isOpen() ? __('todos.dependencies.status.open') : __('todos.dependencies.status.resolved') }}
                            </flux:badge>
                        </div>
                    </div>

                    <flux:button
                        type="button"
                        size="sm"
                        variant="ghost"
                        icon="x-mark"
                        wire:click="removeDependency({{ $dependency->id }})"
                        wire:loading.attr="disabled"
                        :disabled="! $this->canManageDependencies()"
                    >
                        {{ __('todos.dependencies.actions.remove') }}
                    </flux:button>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.dependencies.empty.title')"
                    :description="__('todos.dependencies.empty.description')"
                />
            @endforelse
        </div>

        @if ($this->blockingTasks->isNotEmpty())
            <flux:separator />

            <div class="space-y-2">
                <flux:subheading>{{ __('todos.dependencies.blocking_label') }}</flux:subheading>

                <div class="flex flex-wrap gap-2">
                    @foreach ($this->blockingTasks as $blockedTask)
                        <a href="{{ route('todos.show', $blockedTask) }}" wire:navigate>
                            <flux:badge wire:key="blocking-task-{{ $blockedTask->id }}" size="sm" color="amber" icon="exclamation-triangle">
                                {{ $blockedTask->title }}
                            </flux:badge>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </flux:card>

    <flux:card class="space-y-5" data-test="task-checklist">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <flux:subheading>{{ __('todos.checklist.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.checklist.heading') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('todos.checklist.description') }}</flux:text>
            </div>

            <flux:badge size="sm" color="green" icon="check-circle" data-test="checklist-progress-badge">
                {{ __('todos.checklist.progress', ['completed' => $this->checklistProgress['completed'], 'total' => $this->checklistProgress['total']]) }}
            </flux:badge>
        </div>

        <flux:field>
            <flux:label>
                {{ __('todos.checklist.progress_label') }}
                <x-slot name="trailing">
                    <span class="tabular-nums">{{ $this->checklistProgress['percent'] }}%</span>
                </x-slot>
            </flux:label>
            <flux:progress :value="$this->checklistProgress['percent']" color="green" />
        </flux:field>

        @if (! $this->canManageChecklist())
            <flux:callout icon="archive-box" variant="secondary" data-test="checklist-locked">
                <flux:callout.heading>{{ __('todos.checklist.locked.heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('todos.checklist.locked.description') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form wire:submit="createChecklistItem" class="space-y-2">
            <flux:field>
                <flux:label>{{ __('todos.checklist.fields.item_title') }}</flux:label>

                <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <flux:input
                        wire:model="newChecklistItemTitle"
                        :placeholder="__('todos.checklist.fields.item_placeholder')"
                        maxlength="120"
                        autocomplete="off"
                        :disabled="! $this->canManageChecklist()"
                    />

                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="plus"
                        wire:loading.attr="disabled"
                        :disabled="! $this->canManageChecklist()"
                    >
                        {{ __('todos.checklist.actions.add') }}
                    </flux:button>
                </div>

                <flux:error name="newChecklistItemTitle" />
            </flux:field>
        </form>

        <div class="space-y-2">
            @forelse ($this->checklistItems as $item)
                <div wire:key="checklist-item-{{ $item->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-white/10">
                    @if ($this->editingChecklistItemId === $item->id)
                        <form wire:submit="saveChecklistItem" class="space-y-2">
                            <div class="grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                                <flux:input
                                    wire:model="editingChecklistItemTitle"
                                    :label="__('todos.checklist.fields.item_title')"
                                    maxlength="120"
                                    autocomplete="off"
                                />

                                <flux:button type="submit" variant="primary" icon="check" class="sm:mt-6" wire:loading.attr="disabled">
                                    {{ __('todos.checklist.actions.save') }}
                                </flux:button>

                                <flux:button type="button" variant="ghost" icon="x-mark" class="sm:mt-6" wire:click="cancelChecklistEdit">
                                    {{ __('todos.checklist.actions.cancel') }}
                                </flux:button>
                            </div>

                            <flux:error name="editingChecklistItemTitle" />
                        </form>
                    @else
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <flux:checkbox
                                    :checked="$item->is_completed"
                                    wire:click="toggleChecklistItem({{ $item->id }})"
                                    :disabled="! $this->canManageChecklist()"
                                    :aria-label="$item->is_completed ? __('todos.checklist.actions.mark_incomplete') : __('todos.checklist.actions.mark_complete')"
                                />

                                <div class="min-w-0 space-y-1">
                                    <div @class([
                                        'break-words text-sm font-medium',
                                        'text-zinc-950 dark:text-white' => ! $item->is_completed,
                                        'text-zinc-500 line-through dark:text-zinc-400' => $item->is_completed,
                                    ])>
                                        {{ $item->title }}
                                    </div>

                                    @if ($item->completed_at)
                                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('todos.checklist.completed_at', ['date' => $item->completed_at->isoFormat('MMM D, YYYY h:mm A')]) }}
                                        </flux:text>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1 sm:justify-end">
                                <flux:button
                                    type="button"
                                    size="xs"
                                    variant="ghost"
                                    icon="chevron-up"
                                    square
                                    tooltip="{{ __('todos.checklist.actions.move_up') }}"
                                    wire:click="moveChecklistItem({{ $item->id }}, 'up')"
                                    wire:loading.attr="disabled"
                                    :disabled="$loop->first || ! $this->canManageChecklist()"
                                    :aria-label="__('todos.checklist.actions.move_up')"
                                />

                                <flux:button
                                    type="button"
                                    size="xs"
                                    variant="ghost"
                                    icon="chevron-down"
                                    square
                                    tooltip="{{ __('todos.checklist.actions.move_down') }}"
                                    wire:click="moveChecklistItem({{ $item->id }}, 'down')"
                                    wire:loading.attr="disabled"
                                    :disabled="$loop->last || ! $this->canManageChecklist()"
                                    :aria-label="__('todos.checklist.actions.move_down')"
                                />

                                <flux:button
                                    type="button"
                                    size="xs"
                                    variant="ghost"
                                    icon="pencil-square"
                                    square
                                    tooltip="{{ __('todos.checklist.actions.edit') }}"
                                    wire:click="startEditChecklistItem({{ $item->id }})"
                                    :disabled="! $this->canManageChecklist()"
                                    :aria-label="__('todos.checklist.actions.edit')"
                                />

                                <flux:button
                                    type="button"
                                    size="xs"
                                    variant="danger"
                                    icon="trash"
                                    square
                                    tooltip="{{ __('todos.checklist.actions.delete') }}"
                                    wire:click="deleteChecklistItem({{ $item->id }})"
                                    wire:confirm="{{ __('todos.confirmations.delete_checklist_item') }}"
                                    :disabled="! $this->canManageChecklist()"
                                    :aria-label="__('todos.checklist.actions.delete')"
                                />
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.checklist.empty.title')"
                    :description="__('todos.checklist.empty.description')"
                    data-test="checklist-empty"
                />
            @endforelse
        </div>
    </flux:card>
</section>
