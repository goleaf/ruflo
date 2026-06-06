<x-ui.page-container>
    <x-ui.page-header :title="__('todos.pages.recurring.title')" :description="__('todos.pages.recurring.description')">
        <div class="flex flex-wrap gap-2">
            <flux:button variant="ghost" icon="calendar" :href="route('todos.calendar')" wire:navigate>
                {{ __('todos.calendar.open_calendar') }}
            </flux:button>

            <flux:button variant="ghost" icon="list-bullet" :href="route('todos.index')" wire:navigate>
                {{ __('todos.actions.back_to_list') }}
            </flux:button>
        </div>
    </x-ui.page-header>

    <flux:card class="space-y-5" data-test="recurrence-rule-form">
        <div>
            <flux:heading size="lg">
                {{ $editingRuleId ? __('todos.recurrence.edit.heading') : __('todos.recurrence.create.heading') }}
            </flux:heading>
            <flux:text class="mt-1">
                {{ $editingRuleId ? __('todos.recurrence.edit.description') : __('todos.recurrence.create.description') }}
            </flux:text>
        </div>

        <form wire:submit="save" class="space-y-5">
            <flux:error name="recurrenceRule" />

            @if ($editingRuleId)
                <flux:input :label="__('todos.recurrence.fields.task')" :value="$editingTaskTitle" disabled />
            @else
                <div>
                    <flux:select variant="combobox" wire:model="todoId" :label="__('todos.recurrence.fields.task')">
                        <flux:select.option value="">{{ __('todos.recurrence.placeholders.task') }}</flux:select.option>

                        @foreach ($this->taskOptions as $task)
                            <flux:select.option wire:key="recurrence-task-option-{{ $task->id }}" value="{{ $task->id }}">
                                {{ $task->title }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="todoId" />

                    @if ($this->taskOptions->isEmpty())
                        <flux:callout color="amber" icon="information-circle" class="mt-3">
                            <flux:callout.heading>{{ __('todos.recurrence.empty.no_task_options_title') }}</flux:callout.heading>
                            <flux:callout.text>{{ __('todos.recurrence.empty.no_task_options_description') }}</flux:callout.text>
                        </flux:callout>
                    @endif
                </div>
            @endif

            <flux:radio.group wire:model.live="frequency" :label="__('todos.recurrence.fields.frequency')" variant="cards" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach ($this->frequencyOptions() as $frequencyOption)
                    <flux:radio
                        wire:key="recurrence-frequency-{{ $frequencyOption->value }}"
                        value="{{ $frequencyOption->value }}"
                        :icon="$frequencyOption->icon()"
                        :label="$frequencyOption->label()"
                        :description="__('todos.recurrence.frequency_descriptions.'.$frequencyOption->value)"
                    />
                @endforeach
            </flux:radio.group>
            <flux:error name="frequency" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:input wire:model="interval" type="number" min="1" max="30" :label="__('todos.recurrence.fields.interval')" />
                    <flux:error name="interval" />
                </div>

                <div>
                    <flux:input type="date" wire:model="startsOn" :label="__('todos.recurrence.fields.starts_on')" />
                    <flux:error name="startsOn" />
                </div>
            </div>

            @if ($this->showWeekdays())
                <div>
                    <flux:checkbox.group wire:model="weekdays" :label="__('todos.recurrence.fields.weekdays')" variant="cards" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                        @foreach ($this->weekdayOptions() as $weekdayOption)
                            <flux:checkbox
                                wire:key="recurrence-weekday-{{ $weekdayOption->value }}"
                                value="{{ $weekdayOption->value }}"
                                :label="$weekdayOption->shortLabel()"
                                :description="$weekdayOption->label()"
                            />
                        @endforeach
                    </flux:checkbox.group>
                    <flux:error name="weekdays" />
                    <flux:error name="weekdays.*" />
                </div>
            @endif

            @if ($this->showMonthDay())
                <div>
                    <flux:input wire:model="monthDay" type="number" min="1" max="31" :label="__('todos.recurrence.fields.month_day')" />
                    <flux:error name="monthDay" />
                </div>
            @endif

            <flux:radio.group wire:model.live="endType" :label="__('todos.recurrence.fields.end_type')" variant="cards" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach ($this->endTypeOptions() as $endTypeOption)
                    <flux:radio
                        wire:key="recurrence-end-type-{{ $endTypeOption->value }}"
                        value="{{ $endTypeOption->value }}"
                        :label="$endTypeOption->label()"
                        :description="__('todos.recurrence.end_type_descriptions.'.$endTypeOption->value)"
                    />
                @endforeach
            </flux:radio.group>
            <flux:error name="endType" />

            @if ($this->showEndDate())
                <div>
                    <flux:input type="date" wire:model="endsOn" :label="__('todos.recurrence.fields.ends_on')" />
                    <flux:error name="endsOn" />
                </div>
            @endif

            @if ($this->showMaxOccurrences())
                <div>
                    <flux:input wire:model="maxOccurrences" type="number" min="1" max="365" :label="__('todos.recurrence.fields.max_occurrences')" />
                    <flux:error name="maxOccurrences" />
                </div>
            @endif

            <flux:checkbox wire:model="isEnabled" :label="__('todos.recurrence.fields.is_enabled')" :description="__('todos.recurrence.fields.is_enabled_description')" />
            <flux:error name="isEnabled" />

            <div class="flex flex-wrap justify-end gap-2">
                @if ($editingRuleId)
                    <flux:button type="button" variant="ghost" wire:click="cancelEdit">
                        {{ __('todos.actions.cancel') }}
                    </flux:button>
                @endif

                <flux:button type="submit" variant="primary" icon="arrow-path" wire:loading.attr="disabled" wire:target="save">
                    {{ $editingRuleId ? __('todos.recurrence.actions.update') : __('todos.recurrence.actions.create') }}
                </flux:button>
            </div>
        </form>
    </flux:card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2" data-test="recurrence-rule-list">
        @forelse ($this->recurrenceRules as $rule)
            <flux:card wire:key="recurrence-rule-{{ $rule->id }}" class="space-y-4" data-test="recurrence-rule-card">
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge size="sm" :color="$rule->frequency->color()" :icon="$rule->frequency->icon()">
                                {{ $rule->frequency->label() }}
                            </flux:badge>
                            <flux:badge size="sm" :color="$rule->statusColor()">
                                {{ $rule->statusLabel() }}
                            </flux:badge>
                        </div>

                        <flux:heading size="lg" class="mt-2 break-words">
                            {{ $rule->todo?->title ?? __('todos.recurrence.missing_task') }}
                        </flux:heading>

                        <flux:text class="mt-1 break-words">{{ $rule->summary() }}</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-white/10 dark:bg-zinc-900">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-500">{{ __('todos.recurrence.fields.starts_on') }}</flux:text>
                        <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $rule->starts_on->format('Y-m-d') }}</div>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-white/10 dark:bg-zinc-900">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-500">{{ __('todos.recurrence.fields.last_generated_until') }}</flux:text>
                        <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $rule->last_generated_until?->format('Y-m-d') ?? __('todos.recurrence.none') }}</div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <flux:button type="button" variant="ghost" size="sm" icon="pause-circle" wire:click="toggleRule({{ $rule->id }})">
                        {{ $this->toggleActionLabel($rule) }}
                    </flux:button>

                    <flux:button type="button" variant="ghost" size="sm" icon="pencil-square" wire:click="startEditRule({{ $rule->id }})">
                        {{ __('todos.actions.edit') }}
                    </flux:button>

                    <flux:button type="button" variant="danger" size="sm" icon="trash" wire:click="deleteRule({{ $rule->id }})" wire:confirm="{{ __('todos.recurrence.confirmations.delete') }}">
                        {{ __('todos.actions.delete') }}
                    </flux:button>
                </div>
            </flux:card>
        @empty
            <div class="lg:col-span-2">
                <x-ui.empty-state
                    :title="__('todos.recurrence.empty.title')"
                    :description="__('todos.recurrence.empty.description')"
                />
            </div>
        @endforelse
    </div>
</x-ui.page-container>
