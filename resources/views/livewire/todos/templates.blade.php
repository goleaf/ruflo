<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.templates.title')" :description="__('todos.pages.templates.description')">
        <div class="flex flex-wrap gap-2">
            <flux:button variant="ghost" icon="list-bullet" :href="route('todos.index')" wire:navigate>
                {{ __('todos.templates.actions.open_tasks') }}
            </flux:button>
        </div>
    </x-ui.page-header>

    <flux:card class="space-y-5" data-test="task-template-create">
        <div>
            <flux:heading size="lg">{{ __('todos.templates.create.heading') }}</flux:heading>
            <flux:text class="mt-1">{{ __('todos.templates.create.description') }}</flux:text>
        </div>

        <form wire:submit="createTemplate" class="space-y-5">
            <flux:radio.group wire:model.live="kind" :label="__('todos.templates.fields.kind')" variant="cards" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($this->kindOptions() as $kindOption)
                    <flux:radio
                        value="{{ $kindOption->value }}"
                        :icon="$kindOption->icon()"
                        :label="$kindOption->label()"
                        :description="__('todos.templates.kind_descriptions.'.$kindOption->value)"
                    />
                @endforeach
            </flux:radio.group>
            <flux:error name="kind" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:input wire:model="name" :label="__('todos.templates.fields.name')" :placeholder="__('todos.templates.placeholders.name')" maxlength="80" autocomplete="off" />
                    <flux:error name="name" />
                </div>

                <div>
                    <flux:select wire:model="visibility" :label="__('todos.templates.fields.visibility')">
                        @foreach ($this->visibilityOptions() as $visibilityOption)
                            <flux:select.option value="{{ $visibilityOption['value'] }}">{{ $visibilityOption['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="visibility" />
                </div>
            </div>

            <div>
                <flux:textarea wire:model="description" :label="__('todos.templates.fields.description')" :placeholder="__('todos.templates.placeholders.description')" rows="2" maxlength="500" />
                <flux:error name="description" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:input wire:model="title" :label="__('todos.fields.title')" :placeholder="__('todos.templates.placeholders.title')" maxlength="120" autocomplete="off" />
                    <flux:error name="title" />
                </div>

                <div>
                    <flux:input wire:model="projectName" :label="__('todos.templates.fields.project_name')" :placeholder="__('todos.templates.placeholders.project_name')" maxlength="120" autocomplete="off" />
                    <flux:error name="projectName" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:select wire:model="priority" :label="__('todos.fields.priority')">
                        @foreach ($this->priorityOptions() as $priorityOption)
                            <flux:select.option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="priority" />
                </div>

                <div>
                    <flux:input wire:model="dueOffsetDays" type="number" min="0" max="365" :label="__('todos.templates.fields.due_offset_days')" :placeholder="__('todos.templates.placeholders.due_offset_days')" />
                    <flux:error name="dueOffsetDays" />
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <flux:subheading>{{ __('todos.templates.fields.checklist_items') }}</flux:subheading>
                    <flux:button type="button" size="sm" variant="subtle" icon="plus" wire:click="addChecklistItem">
                        {{ __('todos.checklist.actions.add') }}
                    </flux:button>
                </div>

                <div class="space-y-2">
                    @foreach ($checklistItems as $index => $checklistItem)
                        <div wire:key="template-checklist-item-{{ $index }}" class="flex items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <flux:input wire:model="checklistItems.{{ $index }}" :label="__('todos.checklist.fields.item_title')" :placeholder="__('todos.checklist.fields.item_placeholder')" maxlength="120" autocomplete="off" />
                                <flux:error name="checklistItems.{{ $index }}" />
                            </div>

                            <flux:button type="button" variant="ghost" size="sm" icon="x-mark" square wire:click="removeChecklistItem({{ $index }})" :aria-label="__('todos.templates.actions.remove_checklist_item')" class="mt-6" />
                        </div>
                    @endforeach
                </div>
                <flux:error name="checklistItems" />
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" icon="document-plus" wire:loading.attr="disabled" wire:target="createTemplate">
                    {{ __('todos.templates.actions.create') }}
                </flux:button>
            </div>
        </form>
    </flux:card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2" data-test="task-template-list">
        @forelse ($this->templates as $template)
            <flux:card wire:key="todo-template-{{ $template->id }}" class="space-y-4" data-test="task-template-card">
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge size="sm" :color="$template->kind->color()" :icon="$template->kind->icon()">{{ $template->kind->label() }}</flux:badge>
                            <flux:badge size="sm" :color="$template->isShared() ? 'amber' : 'zinc'">
                                {{ $template->isShared() ? __('todos.templates.visibility.shared') : __('todos.templates.visibility.private') }}
                            </flux:badge>
                            <flux:badge size="sm" :color="$template->priority->color()" icon="flag">{{ $template->priority->label() }}</flux:badge>
                        </div>

                        <flux:heading size="lg" class="mt-2 break-words">{{ $template->name }}</flux:heading>

                        @if ($template->description)
                            <flux:text class="mt-1 break-words">{{ $template->description }}</flux:text>
                        @endif
                    </div>

                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" square icon="ellipsis-horizontal" :aria-label="__('todos.actions.more')" />

                        <flux:menu>
                            <flux:menu.item icon="pencil-square" wire:click="startEditTemplate({{ $template->id }})">{{ __('todos.actions.edit') }}</flux:menu.item>
                            <flux:menu.item icon="trash" variant="danger" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="{{ __('todos.confirmations.delete_template') }}">
                                {{ __('todos.actions.delete') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge size="sm" color="blue" icon="check-circle">{{ $template->title }}</flux:badge>

                        @if ($template->project_name)
                            <flux:badge size="sm" color="green" icon="folder">{{ $template->project_name }}</flux:badge>
                        @endif

                        @if ($template->due_offset_days !== null)
                            <flux:badge size="sm" color="amber" icon="calendar">
                                {{ __('todos.templates.preview.due_offset', ['days' => $template->due_offset_days]) }}
                            </flux:badge>
                        @endif
                    </div>

                    @if (count($template->checklist_items ?? []) > 0)
                        <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                            @foreach (array_slice($template->checklist_items ?? [], 0, 5) as $item)
                                <li class="flex items-center gap-2">
                                    <flux:icon.check variant="micro" class="text-zinc-400" />
                                    <span class="break-words">{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <flux:text class="text-sm">{{ __('todos.templates.preview.no_checklist') }}</flux:text>
                    @endif
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <flux:button type="button" variant="primary" size="sm" icon="sparkles" wire:click="createTodoFromTemplate({{ $template->id }})" wire:loading.attr="disabled" wire:target="createTodoFromTemplate({{ $template->id }})">
                        {{ __('todos.templates.actions.use') }}
                    </flux:button>
                    <flux:button type="button" variant="ghost" size="sm" icon="pencil-square" wire:click="startEditTemplate({{ $template->id }})">
                        {{ __('todos.actions.edit') }}
                    </flux:button>
                </div>
            </flux:card>
        @empty
            <div class="lg:col-span-2">
                <x-ui.empty-state
                    :title="__('todos.templates.empty.title')"
                    :description="__('todos.templates.empty.description')"
                />
            </div>
        @endforelse
    </div>

    <flux:modal wire:model.self="showEditModal" @close="closeEdit" class="md:w-[42rem]">
        <form wire:submit="saveTemplate" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('todos.templates.edit.heading') }}</flux:heading>
                <flux:text class="mt-1">{{ __('todos.templates.edit.description') }}</flux:text>
            </div>

            <flux:radio.group wire:model.live="editKind" :label="__('todos.templates.fields.kind')" variant="cards" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($this->kindOptions() as $kindOption)
                    <flux:radio
                        value="{{ $kindOption->value }}"
                        :icon="$kindOption->icon()"
                        :label="$kindOption->label()"
                        :description="__('todos.templates.kind_descriptions.'.$kindOption->value)"
                    />
                @endforeach
            </flux:radio.group>
            <flux:error name="editKind" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:input wire:model="editName" :label="__('todos.templates.fields.name')" maxlength="80" autocomplete="off" />
                    <flux:error name="editName" />
                </div>

                <div>
                    <flux:select wire:model="editVisibility" :label="__('todos.templates.fields.visibility')">
                        @foreach ($this->visibilityOptions() as $visibilityOption)
                            <flux:select.option value="{{ $visibilityOption['value'] }}">{{ $visibilityOption['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="editVisibility" />
                </div>
            </div>

            <div>
                <flux:textarea wire:model="editDescription" :label="__('todos.templates.fields.description')" rows="2" maxlength="500" />
                <flux:error name="editDescription" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:input wire:model="editTitle" :label="__('todos.fields.title')" maxlength="120" autocomplete="off" />
                    <flux:error name="editTitle" />
                </div>

                <div>
                    <flux:input wire:model="editProjectName" :label="__('todos.templates.fields.project_name')" maxlength="120" autocomplete="off" />
                    <flux:error name="editProjectName" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:select wire:model="editPriority" :label="__('todos.fields.priority')">
                        @foreach ($this->priorityOptions() as $priorityOption)
                            <flux:select.option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="editPriority" />
                </div>

                <div>
                    <flux:input wire:model="editDueOffsetDays" type="number" min="0" max="365" :label="__('todos.templates.fields.due_offset_days')" />
                    <flux:error name="editDueOffsetDays" />
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <flux:subheading>{{ __('todos.templates.fields.checklist_items') }}</flux:subheading>
                    <flux:button type="button" size="sm" variant="subtle" icon="plus" wire:click="addEditChecklistItem">
                        {{ __('todos.checklist.actions.add') }}
                    </flux:button>
                </div>

                <div class="space-y-2">
                    @foreach ($editChecklistItems as $index => $checklistItem)
                        <div wire:key="edit-template-checklist-item-{{ $index }}" class="flex items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <flux:input wire:model="editChecklistItems.{{ $index }}" :label="__('todos.checklist.fields.item_title')" maxlength="120" autocomplete="off" />
                                <flux:error name="editChecklistItems.{{ $index }}" />
                            </div>

                            <flux:button type="button" variant="ghost" size="sm" icon="x-mark" square wire:click="removeEditChecklistItem({{ $index }})" :aria-label="__('todos.templates.actions.remove_checklist_item')" class="mt-6" />
                        </div>
                    @endforeach
                </div>
                <flux:error name="editChecklistItems" />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="closeEdit">{{ __('todos.actions.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveTemplate">
                    {{ __('todos.actions.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
