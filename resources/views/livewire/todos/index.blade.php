<section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.index.title')" :description="__('todos.pages.index.description')">
        <div class="grid grid-cols-2 gap-3 text-sm sm:min-w-72">
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.summary.remaining') }}</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $this->summary['remaining'] }}</div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.summary.completed') }}</div>
                <div class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->summary['completed'] }}</div>
            </div>
        </div>
    </x-ui.page-header>

    <flux:card class="space-y-5">
        <form wire:submit="createTodo" class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="min-w-0 flex-1">
                <flux:input wire:model="form.title" :label="__('todos.fields.title')" type="text" maxlength="120" autocomplete="off" />
                <flux:error name="form.title" />
            </div>

            <flux:button type="submit" variant="primary" icon="plus" class="sm:mt-6">
                {{ __('todos.actions.add') }}
            </flux:button>
        </form>

        <flux:separator />

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div wire:key="todo-{{ $todo->id }}" class="flex min-h-14 items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-white/10 dark:bg-zinc-900">
                    <flux:checkbox
                        :checked="$todo->is_completed"
                        wire:click="toggleTodo({{ $todo->id }})"
                        :aria-label="__('todos.actions.toggle')"
                    />

                    <div class="min-w-0 flex-1">
                        <div @class([
                            'truncate text-sm font-medium',
                            'text-zinc-950 dark:text-white' => ! $todo->is_completed,
                            'text-zinc-500 line-through dark:text-zinc-400' => $todo->is_completed,
                        ])>
                            {{ $todo->title }}
                        </div>
                    </div>

                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        square
                        icon="trash"
                        tooltip="{{ __('todos.actions.delete') }}"
                        wire:click="deleteTodo({{ $todo->id }})"
                        :aria-label="__('todos.actions.delete')"
                    />
                </div>
            @empty
                <x-ui.empty-state :title="__('todos.empty.title')" :description="__('todos.empty.description')" />
            @endforelse
        </div>

        @if ($this->summary['completed'] > 0)
            <div class="flex justify-end">
                <flux:button type="button" variant="subtle" size="sm" wire:click="clearCompleted">
                    {{ __('todos.actions.clear_completed') }}
                </flux:button>
            </div>
        @endif
    </flux:card>
</section>
