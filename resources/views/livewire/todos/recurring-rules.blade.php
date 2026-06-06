<x-ui.page-container>
    <x-ui.page-header :title="__('todos.pages.recurring.title')" :description="__('todos.pages.recurring.description')">
        <div class="flex flex-wrap gap-2">
            <flux:button type="button" variant="primary" icon="arrow-path" wire:click="generateOccurrences" wire:loading.attr="disabled" wire:target="generateOccurrences">
                {{ __('todos.recurrence.generation.actions.process') }}
            </flux:button>

            <flux:button variant="ghost" icon="calendar" :href="route('todos.calendar')" wire:navigate>
                {{ __('todos.calendar.open_calendar') }}
            </flux:button>

            <flux:button variant="ghost" icon="list-bullet" :href="route('todos.index')" wire:navigate>
                {{ __('todos.actions.back_to_list') }}
            </flux:button>
        </div>
    </x-ui.page-header>

    <flux:callout icon="arrow-path" variant="secondary" data-test="recurrence-generation-web-mode-note">
        <flux:callout.heading>{{ __('todos.recurrence.generation.web_mode.heading') }}</flux:callout.heading>
        <flux:callout.text>{{ __('todos.recurrence.generation.web_mode.description') }}</flux:callout.text>
    </flux:callout>

    @if ($lastGenerationReport !== null)
        <flux:callout icon="check-circle" variant="secondary" data-test="recurrence-generation-run-report">
            <flux:callout.heading>{{ __('todos.recurrence.generation.report_heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('todos.recurrence.generation.report', $lastGenerationReport) }}</flux:callout.text>
        </flux:callout>
    @endif

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
                            <flux:badge size="sm" color="zinc" icon="rectangle-stack">
                                {{ trans_choice('todos.recurrence.generation.generated_badge', (int) $rule->occurrences_count, ['count' => (int) $rule->occurrences_count]) }}
                            </flux:badge>
                            <flux:badge size="sm" color="amber" icon="adjustments-horizontal">
                                {{ trans_choice('todos.recurrence.exceptions.badge', (int) $rule->exceptions_count, ['count' => (int) $rule->exceptions_count]) }}
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


                @if ($rule->occurrences->isNotEmpty())
                    <div class="space-y-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-950">
                        <div>
                            <flux:heading size="sm">{{ __('todos.recurrence.exceptions.occurrences_heading') }}</flux:heading>
                            <flux:text class="text-sm">{{ __('todos.recurrence.exceptions.occurrences_description') }}</flux:text>
                        </div>

                        <div class="space-y-2">
                            @foreach ($rule->occurrences->take(5) as $occurrence)
                                <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between" data-test="recurrence-occurrence-row">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">{{ $occurrence->title }}</flux:text>

                                            @foreach ($rule->exceptions->where('todo_id', $occurrence->id) as $exception)
                                                <flux:badge size="sm" :color="$exception->typeColor()" :icon="$exception->typeIcon()">
                                                    {{ $exception->typeLabel() }}
                                                </flux:badge>
                                            @endforeach
                                        </div>
                                        <flux:text class="mt-1 text-sm">
                                            {{ __('todos.recurrence.exceptions.occurrence_dates', [
                                                'original' => $occurrence->recurrence_occurs_on?->format('Y-m-d') ?? __('todos.recurrence.none'),
                                                'current' => $occurrence->due_date?->format('Y-m-d') ?? __('todos.recurrence.none'),
                                            ]) }}
                                        </flux:text>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <flux:button type="button" size="sm" variant="ghost" icon="pencil-square" wire:click="startEditOccurrence({{ $occurrence->id }})">
                                            {{ __('todos.recurrence.edit_scope.actions.edit') }}
                                        </flux:button>
                                        <flux:button type="button" size="sm" variant="ghost" icon="arrow-right-circle" wire:click="startMoveOccurrence({{ $occurrence->id }})">
                                            {{ __('todos.recurrence.exceptions.actions.move') }}
                                        </flux:button>
                                        <flux:button type="button" size="sm" variant="danger" icon="no-symbol" wire:click="skipOccurrence({{ $occurrence->id }})" wire:confirm="{{ __('todos.recurrence.exceptions.confirmations.skip') }}">
                                            {{ __('todos.recurrence.exceptions.actions.skip') }}
                                        </flux:button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($rule->exceptions->isNotEmpty())
                    <div class="space-y-2 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-500/30 dark:bg-amber-950/20" data-test="recurrence-exception-list">
                        <flux:heading size="sm">{{ __('todos.recurrence.exceptions.heading') }}</flux:heading>

                        @foreach ($rule->exceptions->take(5) as $exception)
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <flux:badge size="sm" :color="$exception->typeColor()" :icon="$exception->typeIcon()">
                                    {{ $exception->typeLabel() }}
                                </flux:badge>
                                <span class="text-zinc-700 dark:text-zinc-200">
                                    {{ __('todos.recurrence.exceptions.summary', [
                                        'original' => $exception->original_occurs_on?->format('Y-m-d') ?? __('todos.recurrence.none'),
                                        'adjusted' => $exception->adjusted_occurs_on?->format('Y-m-d') ?? __('todos.recurrence.none'),
                                    ]) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

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

    <flux:modal name="move-recurring-occurrence" class="md:w-md">
        <form wire:submit="moveOccurrence" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('todos.recurrence.exceptions.move_modal.heading') }}</flux:heading>
                <flux:text class="mt-2">{{ __('todos.recurrence.exceptions.move_modal.description') }}</flux:text>
            </div>

            <flux:error name="recurrenceOccurrence" />

            <flux:input type="date" wire:model="moveTo" :label="__('todos.recurrence.exceptions.fields.move_to')" />
            <flux:error name="moveTo" />

            <flux:textarea wire:model="exceptionNote" :label="__('todos.recurrence.exceptions.fields.note')" rows="3" />
            <flux:error name="exceptionNote" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('todos.actions.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="arrow-right-circle" wire:loading.attr="disabled" wire:target="moveOccurrence">
                    {{ __('todos.recurrence.exceptions.actions.save_move') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-recurring-occurrence" class="md:w-2xl">
        <form wire:submit="saveRecurringEdit" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('todos.recurrence.edit_scope.heading') }}</flux:heading>
                <flux:text class="mt-2">{{ __('todos.recurrence.edit_scope.description') }}</flux:text>
            </div>

            <flux:error name="recurrenceOccurrence" />

            <flux:radio.group wire:model.live="editScope" :label="__('todos.recurrence.edit_scope.fields.scope')" variant="cards" class="grid gap-3 sm:grid-cols-2">
                @foreach ($this->editScopeOptions() as $scopeOption)
                    <flux:radio
                        wire:key="recurrence-edit-scope-{{ $scopeOption->value }}"
                        value="{{ $scopeOption->value }}"
                        :label="$scopeOption->label()"
                        :description="$scopeOption->description()"
                        :icon="$scopeOption->icon()"
                    />
                @endforeach
            </flux:radio.group>
            <flux:error name="editScope" />

            @if ($editScope === 'occurrence')
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <flux:input wire:model="occurrenceEditTitle" :label="__('todos.recurrence.edit_scope.fields.occurrence_title')" maxlength="120" autocomplete="off" />
                        <flux:error name="occurrenceEditTitle" />
                    </div>

                    <div>
                        <flux:select variant="combobox" wire:model="occurrenceEditPriority" :label="__('todos.recurrence.edit_scope.fields.occurrence_priority')">
                            @foreach ($this->priorityOptions() as $priorityOption)
                                <flux:select.option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="occurrenceEditPriority" />
                    </div>

                    <div>
                        <flux:date-picker
                            type="input"
                            wire:model="occurrenceEditDueDate"
                            :label="__('todos.recurrence.edit_scope.fields.occurrence_due_date')"
                            with-today
                        />
                        <flux:error name="occurrenceEditDueDate" />
                    </div>
                </div>
            @else
                <flux:callout icon="rectangle-stack" variant="secondary" data-test="recurrence-series-edit-note">
                    <flux:callout.heading>{{ __('todos.recurrence.edit_scope.series_note.heading') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('todos.recurrence.edit_scope.series_note.description') }}</flux:callout.text>
                </flux:callout>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:input wire:model="seriesEditTitle" :label="__('todos.recurrence.edit_scope.fields.series_title')" maxlength="120" autocomplete="off" />
                        <flux:error name="seriesEditTitle" />
                    </div>

                    <div>
                        <flux:select variant="combobox" wire:model="seriesEditPriority" :label="__('todos.recurrence.edit_scope.fields.series_priority')">
                            @foreach ($this->priorityOptions() as $priorityOption)
                                <flux:select.option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="seriesEditPriority" />
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('todos.actions.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="saveRecurringEdit">
                    {{ __('todos.recurrence.edit_scope.actions.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</x-ui.page-container>
