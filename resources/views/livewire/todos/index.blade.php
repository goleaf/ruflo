@php
    use App\Enums\TodoStatus;
@endphp

<section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.index.title')" :description="__('todos.pages.index.description')">
        <div class="grid grid-cols-3 gap-3 text-sm sm:min-w-80">
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.summary.active') }}</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $this->summary['active'] }}</div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.summary.completed') }}</div>
                <div class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->summary['completed'] }}</div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.summary.archived') }}</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-600 dark:text-zinc-300">{{ $this->summary['archived'] }}</div>
            </div>
        </div>
    </x-ui.page-header>

    <flux:card class="space-y-5">
        @if ($tab === TodoStatus::Active->value)
            <form wire:submit="createTodo" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="min-w-0 flex-1">
                    <flux:input
                        wire:model="form.title"
                        :label="__('todos.fields.title')"
                        :placeholder="__('todos.fields.title_placeholder')"
                        type="text"
                        maxlength="120"
                        autocomplete="off"
                    />
                    <flux:error name="form.title" />
                </div>

                <flux:button type="submit" variant="primary" icon="plus" class="sm:mt-6">
                    {{ __('todos.actions.add') }}
                </flux:button>
            </form>

            <flux:separator />
        @endif

        {{-- Lifecycle tabs (segmented control; Flux Free has no tabs component). --}}
        <div role="tablist" class="flex flex-wrap gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-900">
            @foreach (['active' => $this->summary['active'], 'completed' => $this->summary['completed'], 'archived' => $this->summary['archived']] as $tabValue => $tabCount)
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('tab', '{{ $tabValue }}')"
                    :aria-selected="$tab === '{{ $tabValue }}' ? 'true' : 'false'"
                    @class([
                        'flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition',
                        'bg-white text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white' => $tab === $tabValue,
                        'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100' => $tab !== $tabValue,
                    ])
                >
                    {{ __('todos.tabs.'.$tabValue) }}
                    <flux:badge size="sm" :color="$tab === $tabValue ? 'blue' : 'zinc'">{{ $tabCount }}</flux:badge>
                </button>
            @endforeach
        </div>

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div
                    wire:key="todo-{{ $todo->id }}"
                    class="flex min-h-14 items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
                >
                    @unless ($todo->isArchived())
                        <flux:checkbox
                            :checked="$todo->is_completed"
                            wire:click="toggleTodo({{ $todo->id }})"
                            :aria-label="__('todos.actions.toggle')"
                        />
                    @else
                        <flux:icon.archive-box variant="micro" class="text-zinc-400" />
                    @endunless

                    <div class="min-w-0 flex-1">
                        <div @class([
                            'truncate text-sm font-medium',
                            'text-zinc-950 dark:text-white' => $todo->isActive(),
                            'text-zinc-500 line-through dark:text-zinc-400' => $todo->is_completed && ! $todo->isArchived(),
                            'text-zinc-500 dark:text-zinc-400' => $todo->isArchived(),
                        ])>
                            {{ $todo->title }}
                        </div>
                    </div>

                    <x-ui.status-badge :status="$todo->status()" class="max-sm:hidden" />

                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" square icon="ellipsis-horizontal" :aria-label="__('todos.actions.edit')" />

                        <flux:menu>
                            @unless ($todo->isArchived())
                                <flux:menu.item icon="pencil-square" wire:click="startEdit({{ $todo->id }})">
                                    {{ __('todos.actions.edit') }}
                                </flux:menu.item>

                                <flux:menu.item icon="archive-box" wire:click="archiveTodo({{ $todo->id }})">
                                    {{ __('todos.actions.archive') }}
                                </flux:menu.item>
                            @else
                                <flux:menu.item icon="archive-box-x-mark" wire:click="restoreTodo({{ $todo->id }})">
                                    {{ __('todos.actions.restore') }}
                                </flux:menu.item>
                            @endunless

                            <flux:menu.separator />

                            <flux:menu.item
                                icon="trash"
                                variant="danger"
                                wire:click="deleteTodo({{ $todo->id }})"
                                wire:confirm="{{ __('todos.confirmations.delete') }}"
                            >
                                {{ __('todos.actions.delete') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.empty.'.$tab.'.title')"
                    :description="__('todos.empty.'.$tab.'.description')"
                />
            @endforelse
        </div>

        @if ($tab === TodoStatus::Completed->value && $this->summary['completed'] > 0)
            <div class="flex justify-end">
                <flux:button
                    type="button"
                    variant="subtle"
                    size="sm"
                    wire:click="clearCompleted"
                    wire:confirm="{{ __('todos.confirmations.delete') }}"
                >
                    {{ __('todos.actions.clear_completed') }}
                </flux:button>
            </div>
        @endif
    </flux:card>

    {{-- Edit task modal --}}
    <flux:modal wire:model.self="showEditModal" @close="closeEdit" class="md:w-96">
        <form wire:submit="saveEdit" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('todos.modals.edit.heading') }}</flux:heading>
                <flux:text class="mt-2">{{ __('todos.modals.edit.description') }}</flux:text>
            </div>

            <div>
                <flux:input
                    wire:model="editForm.title"
                    :label="__('todos.fields.title')"
                    type="text"
                    maxlength="120"
                    autocomplete="off"
                />
                <flux:error name="editForm.title" />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="closeEdit">
                    {{ __('todos.actions.cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('todos.actions.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
