<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.index.title')" :description="__('todos.pages.index.description')">
        <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4 sm:min-w-[26rem]">
            <x-ui.stat :label="__('todos.summary.active')" :value="$this->summary['active']" />
            <x-ui.stat :label="__('todos.summary.overdue')" :value="$this->summary['overdue']" tone="danger" />
            <x-ui.stat :label="__('todos.summary.completed')" :value="$this->summary['completed']" tone="success" />
            <x-ui.stat :label="__('todos.summary.archived')" :value="$this->summary['archived']" tone="muted" />
        </div>
    </x-ui.page-header>

    {{-- Create form (active tab only) --}}
    @if ($tab === 'active')
        <flux:card>
            <form wire:submit="createTodo" class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1">
                        <flux:input
                            wire:model="form.title"
                            :label="__('todos.fields.title')"
                            :placeholder="__('todos.fields.title_placeholder')"
                            maxlength="120"
                            autocomplete="off"
                        />
                        <flux:error name="form.title" />
                    </div>

                    <flux:button type="submit" variant="primary" icon="plus" class="sm:mt-6">
                        {{ __('todos.actions.add') }}
                    </flux:button>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <flux:select wire:model="form.priority" :label="__('todos.fields.priority')">
                            @foreach ($this->priorityOptions() as $priority)
                                <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="form.priority" />
                    </div>

                    <div>
                        <flux:input type="date" wire:model="form.due_date" :label="__('todos.fields.due_date')" />
                        <flux:error name="form.due_date" />
                    </div>

                    <div>
                        <flux:select wire:model="form.project_id" :label="__('todos.fields.project')">
                            <flux:select.option value="">{{ __('todos.fields.no_project') }}</flux:select.option>
                            @foreach ($this->projects as $project)
                                <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="form.project_id" />
                    </div>
                </div>

                @if ($this->tags->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.tags') }}</span>
                        @foreach ($this->tags as $tagOption)
                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 px-2.5 py-1 text-sm dark:border-white/15">
                                <input type="checkbox" wire:model="form.tag_ids" value="{{ $tagOption->id }}" class="rounded border-zinc-300 text-blue-600">
                                {{ $tagOption->name }}
                            </label>
                        @endforeach
                        @error('form.tag_ids.*')
                            <flux:text class="basis-full text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                    </div>
                @endif
            </form>
        </flux:card>
    @endif

    <flux:card class="space-y-5">
        {{-- Lifecycle tabs (segmented control; Flux Free has no tabs component) --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div role="tablist" class="flex flex-wrap gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-900">
                @foreach (['active' => $this->summary['active'], 'completed' => $this->summary['completed'], 'archived' => $this->summary['archived']] as $tabValue => $tabCount)
                    <button
                        type="button"
                        role="tab"
                        wire:click="$set('tab', '{{ $tabValue }}')"
                        @class([
                            'flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition',
                            'bg-white text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white' => $tab === $tabValue,
                            'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100' => $tab !== $tabValue,
                        ])
                    >
                        {{ __('todos.tabs.'.$tabValue) }}
                        <flux:badge size="sm" :color="$tab === $tabValue ? 'blue' : 'zinc'">{{ $tabCount }}</flux:badge>
                    </button>
                @endforeach
            </div>

            <flux:button size="sm" variant="ghost" icon="adjustments-horizontal" wire:click="$set('showManageModal', true)">
                {{ __('todos.actions.manage') }}
            </flux:button>
        </div>

        {{-- Filter toolbar --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                :placeholder="__('todos.filters.search_placeholder')"
                :label="__('todos.filters.search')"
                class="lg:col-span-2"
            />

            <flux:select wire:model.live="project" :label="__('todos.filters.project')">
                <flux:select.option value="">{{ __('todos.filters.all_projects') }}</flux:select.option>
                <flux:select.option value="none">{{ __('todos.fields.no_project') }}</flux:select.option>
                @foreach ($this->projects as $project)
                    <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="tag" :label="__('todos.filters.tag')">
                <flux:select.option value="">{{ __('todos.filters.all_tags') }}</flux:select.option>
                @foreach ($this->tags as $tagOption)
                    <flux:select.option value="{{ $tagOption->id }}">{{ $tagOption->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="priorityFilter" :label="__('todos.filters.priority')">
                <flux:select.option value="">{{ __('todos.filters.all_priorities') }}</flux:select.option>
                @foreach ($this->priorityOptions() as $priority)
                    <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($tab === 'active')
                <flux:select wire:model.live="due" :label="__('todos.filters.due')">
                    <flux:select.option value="">{{ __('todos.filters.all_dates') }}</flux:select.option>
                    <flux:select.option value="today">{{ __('todos.filters.due_today') }}</flux:select.option>
                    <flux:select.option value="overdue">{{ __('todos.filters.overdue') }}</flux:select.option>
                    <flux:select.option value="upcoming">{{ __('todos.filters.upcoming') }}</flux:select.option>
                    <flux:select.option value="with">{{ __('todos.filters.with_due_date') }}</flux:select.option>
                    <flux:select.option value="without">{{ __('todos.filters.without_due_date') }}</flux:select.option>
                </flux:select>
            @endif

            <flux:select wire:model.live="sort" :label="__('todos.filters.sort')">
                <flux:select.option value="created">{{ __('todos.sort.created') }}</flux:select.option>
                <flux:select.option value="updated">{{ __('todos.sort.updated') }}</flux:select.option>
                <flux:select.option value="due">{{ __('todos.sort.due') }}</flux:select.option>
                <flux:select.option value="priority">{{ __('todos.sort.priority') }}</flux:select.option>
                <flux:select.option value="project">{{ __('todos.sort.project') }}</flux:select.option>
                <flux:select.option value="title">{{ __('todos.sort.title') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="direction" :label="__('todos.filters.direction')">
                <flux:select.option value="desc">{{ __('todos.sort.desc') }}</flux:select.option>
                <flux:select.option value="asc">{{ __('todos.sort.asc') }}</flux:select.option>
            </flux:select>

            <div class="flex items-end">
                <flux:button size="sm" variant="subtle" wire:click="resetFilters" class="w-full">
                    {{ __('todos.actions.clear_filters') }}
                </flux:button>
            </div>
        </div>

        {{-- Bulk action toolbar --}}
        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm dark:border-blue-500/30 dark:bg-blue-500/10">
                <span class="font-medium text-blue-900 dark:text-blue-200">
                    {{ __('todos.bulk.selected', ['count' => count($selected)]) }}
                </span>
                <flux:spacer />
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <flux:select wire:model="bulkProject" :label="__('todos.bulk.move_to')" size="sm" class="min-w-48">
                        <flux:select.option value="">{{ __('todos.fields.no_project') }}</flux:select.option>
                        @foreach ($this->projects as $project)
                            <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button size="sm" variant="ghost" icon="folder-arrow-down" wire:click="bulkMove">{{ __('todos.bulk.move') }}</flux:button>
                    <flux:error name="bulkProject" />
                </div>
                @if ($tab === 'active')
                    <flux:button size="sm" variant="ghost" icon="check" wire:click="bulkComplete">{{ __('todos.bulk.complete') }}</flux:button>
                @endif
                @if ($tab !== 'archived')
                    <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="bulkArchive">{{ __('todos.bulk.archive') }}</flux:button>
                @else
                    <flux:button size="sm" variant="ghost" icon="archive-box-x-mark" wire:click="bulkUnarchive">{{ __('todos.bulk.unarchive') }}</flux:button>
                @endif
                <flux:button size="sm" variant="danger" icon="trash" wire:click="bulkDelete" wire:confirm="{{ __('todos.confirmations.bulk_delete') }}">
                    {{ __('todos.bulk.delete') }}
                </flux:button>
            </div>
        @endif

        {{-- Task list --}}
        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div
                    wire:key="todo-{{ $todo->id }}"
                    class="flex min-h-14 items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <input
                        type="checkbox"
                        wire:model.live="selected"
                        value="{{ $todo->id }}"
                        class="mt-1 rounded border-zinc-300 text-blue-600"
                        aria-label="{{ __('todos.bulk.select_one') }}"
                    />

                    @unless ($todo->isArchived())
                        <flux:checkbox
                            :checked="$todo->is_completed"
                            wire:click="{{ $todo->is_completed ? 'reopenTodo' : 'completeTodo' }}({{ $todo->id }})"
                            :aria-label="$todo->is_completed ? __('todos.actions.reopen') : __('todos.actions.complete')"
                            class="mt-0.5"
                        />
                    @else
                        <flux:icon.archive-box variant="micro" class="mt-1 text-zinc-400" />
                    @endunless

                    <div class="min-w-0 flex-1 space-y-1">
                        <a
                            href="{{ route('todos.show', $todo) }}"
                            wire:navigate
                            @class([
                            'text-sm font-medium break-words',
                            'text-zinc-950 dark:text-white' => $todo->isActive(),
                            'text-zinc-500 line-through dark:text-zinc-400' => $todo->is_completed && ! $todo->isArchived(),
                            'text-zinc-500 dark:text-zinc-400' => $todo->isArchived(),
                        ])>
                            {{ $todo->title }}
                        </a>

                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($todo->priority->value !== 'normal')
                                <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                            @endif

                            @if ($todo->due_date)
                                <flux:badge size="sm" :color="$todo->isOverdue() ? 'red' : ($todo->isDueToday() ? 'amber' : 'zinc')" icon="calendar">
                                    {{ $todo->due_date->isoFormat('MMM D') }}
                                </flux:badge>
                            @endif

                            @if ($todo->project)
                                <flux:badge size="sm" :color="$todo->project->color" icon="folder">{{ $todo->project->name }}</flux:badge>
                            @endif

                            @foreach ($todo->tags as $tagBadge)
                                <flux:badge size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                            @endforeach
                        </div>
                    </div>

                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" square icon="ellipsis-horizontal" :aria-label="__('todos.actions.more')" />

                        <flux:menu>
                            @unless ($todo->isArchived())
                                <flux:menu.item icon="pencil-square" wire:click="startEdit({{ $todo->id }})">{{ __('todos.actions.edit') }}</flux:menu.item>
                                <flux:menu.item icon="archive-box" wire:click="archiveTodo({{ $todo->id }})">{{ __('todos.actions.archive') }}</flux:menu.item>
                            @else
                                <flux:menu.item icon="archive-box-x-mark" wire:click="unarchiveTodo({{ $todo->id }})">{{ __('todos.actions.unarchive') }}</flux:menu.item>
                            @endunless

                            <flux:menu.separator />

                            <flux:menu.item icon="trash" variant="danger" wire:click="deleteTodo({{ $todo->id }})" wire:confirm="{{ __('todos.confirmations.delete') }}">
                                {{ __('todos.actions.delete') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            @empty
                <x-ui.empty-state
                    :title="$this->emptyStateTitle()"
                    :description="$this->emptyStateDescription()"
                />
            @endforelse
        </div>

        @if ($this->todos->hasPages())
            <div>{{ $this->todos->links() }}</div>
        @endif

        @if ($tab === 'completed' && $this->summary['completed'] > 0)
            <div class="flex justify-end">
                <flux:button type="button" variant="subtle" size="sm" wire:click="clearCompleted" wire:confirm="{{ __('todos.confirmations.clear_completed') }}">
                    {{ __('todos.actions.clear_completed') }}
                </flux:button>
            </div>
        @endif
    </flux:card>

    {{-- Edit modal --}}
    <flux:modal wire:model.self="showEditModal" @close="closeEdit" class="md:w-[28rem]">
        <form wire:submit="saveEdit" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('todos.modals.edit.heading') }}</flux:heading>
                <flux:text class="mt-1">{{ __('todos.modals.edit.description') }}</flux:text>
            </div>

            <div>
                <flux:input wire:model="editForm.title" :label="__('todos.fields.title')" maxlength="120" autocomplete="off" />
                <flux:error name="editForm.title" />
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <flux:select wire:model="editForm.priority" :label="__('todos.fields.priority')">
                        @foreach ($this->priorityOptions() as $priority)
                            <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="editForm.priority" />
                </div>

                <div>
                    <flux:input type="date" wire:model="editForm.due_date" :label="__('todos.fields.due_date')" />
                    <flux:error name="editForm.due_date" />
                </div>
            </div>

            <div>
                <flux:select wire:model="editForm.project_id" :label="__('todos.fields.project')">
                    <flux:select.option value="">{{ __('todos.fields.no_project') }}</flux:select.option>
                    @foreach ($this->projects as $project)
                        <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="editForm.project_id" />
            </div>

            @if ($this->tags->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.tags') }}</span>
                    @foreach ($this->tags as $tagOption)
                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 px-2.5 py-1 text-sm dark:border-white/15">
                            <input type="checkbox" wire:model="editForm.tag_ids" value="{{ $tagOption->id }}" class="rounded border-zinc-300 text-blue-600">
                            {{ $tagOption->name }}
                        </label>
                    @endforeach
                    @error('editForm.tag_ids.*')
                        <flux:text class="basis-full text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            @endif

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="closeEdit">{{ __('todos.actions.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('todos.actions.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Manage projects & tags modal --}}
    <flux:modal wire:model.self="showManageModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('todos.modals.manage.heading') }}</flux:heading>
                <flux:text class="mt-1">{{ __('todos.modals.manage.description') }}</flux:text>
            </div>

            {{-- Projects --}}
            <div class="space-y-3">
                <flux:subheading>{{ __('todos.modals.manage.projects') }}</flux:subheading>

                <form wire:submit="createProject" class="flex gap-2">
                    <flux:input wire:model="newProjectName" :placeholder="__('todos.fields.project_name')" maxlength="120" class="flex-1" />
                    <flux:button type="submit" variant="primary" icon="plus" square :aria-label="__('todos.actions.add')" />
                </form>
                <flux:error name="newProjectName" />

                <div class="space-y-1.5">
                    @forelse ($this->allProjects as $project)
                        <div wire:key="manage-project-{{ $project->id }}" class="flex items-center gap-2 rounded-md border border-zinc-200 px-2.5 py-1.5 text-sm dark:border-white/10">
                            @if ($editingProjectId === $project->id)
                                <form wire:submit="saveProjectName" class="flex min-w-0 flex-1 items-center gap-2">
                                    <flux:input wire:model="editingProjectName" :label="__('todos.fields.project_name')" size="sm" class="min-w-0 flex-1" />
                                    <flux:button type="submit" size="xs" variant="primary">{{ __('todos.actions.save') }}</flux:button>
                                    <flux:button type="button" size="xs" variant="ghost" wire:click="cancelRenameProject">{{ __('todos.actions.cancel') }}</flux:button>
                                </form>
                            @else
                                <flux:badge size="sm" :color="$project->color">{{ $project->name }}</flux:badge>
                                @if ($project->isArchived())
                                    <flux:badge size="sm" color="zinc">{{ __('todos.status.archived') }}</flux:badge>
                                @endif
                                <flux:spacer />
                                <flux:button size="xs" variant="ghost" icon="pencil-square" square wire:click="startRenameProject({{ $project->id }})" :aria-label="__('todos.actions.rename')" />
                                @if ($project->isArchived())
                                    <flux:button size="xs" variant="ghost" wire:click="restoreProject({{ $project->id }})">{{ __('todos.actions.restore') }}</flux:button>
                                @else
                                    <flux:button size="xs" variant="ghost" wire:click="archiveProject({{ $project->id }})">{{ __('todos.actions.archive') }}</flux:button>
                                @endif
                                <flux:button size="xs" variant="ghost" icon="trash" square wire:click="deleteProject({{ $project->id }})" wire:confirm="{{ __('todos.confirmations.delete_project') }}" :aria-label="__('todos.actions.delete')" />
                            @endif
                        </div>
                    @empty
                        <flux:text class="text-sm">{{ __('todos.empty.projects.title') }}</flux:text>
                    @endforelse
                </div>
            </div>

            <flux:separator />

            {{-- Tags --}}
            <div class="space-y-3">
                <flux:subheading>{{ __('todos.modals.manage.tags') }}</flux:subheading>

                <form wire:submit="createTag" class="flex gap-2">
                    <flux:input wire:model="newTagName" :placeholder="__('todos.fields.tag_name')" maxlength="50" class="flex-1" />
                    <flux:button type="submit" variant="primary" icon="plus" square :aria-label="__('todos.actions.add')" />
                </form>
                <flux:error name="newTagName" />

                <div class="flex flex-wrap gap-1.5">
                    @forelse ($this->tags as $tagOption)
                        <span wire:key="manage-tag-{{ $tagOption->id }}" class="inline-flex items-center gap-1 rounded-full border border-zinc-200 px-2 py-0.5 text-sm dark:border-white/15">
                            #{{ $tagOption->name }}
                            <button type="button" wire:click="deleteTag({{ $tagOption->id }})" wire:confirm="{{ __('todos.confirmations.delete_tag') }}" class="text-zinc-400 hover:text-red-600" aria-label="{{ __('todos.actions.delete') }}">&times;</button>
                        </span>
                    @empty
                        <flux:text class="text-sm">{{ __('todos.empty.tags.title') }}</flux:text>
                    @endforelse
                </div>
            </div>
        </div>
    </flux:modal>
</section>
