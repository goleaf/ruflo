<section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <div class="flex flex-col gap-4 border-b border-zinc-200 pb-6 dark:border-white/10 md:flex-row md:items-end md:justify-between">
        <div class="space-y-2">
            <flux:heading size="xl">{{ __('Mini todos') }}</flux:heading>
            <flux:text>{{ __('Keep the current flow visible and lightweight.') }}</flux:text>
        </div>

        <div class="grid grid-cols-2 gap-3 text-sm sm:min-w-72">
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('Remaining') }}</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $this->remainingCount }}</div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('Completed') }}</div>
                <div class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->completedCount }}</div>
            </div>
        </div>
    </div>

    <flux:card class="space-y-5">
        <form wire:submit="createTodo" class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="min-w-0 flex-1">
                <flux:input wire:model="title" :label="__('Task')" type="text" maxlength="120" autocomplete="off" />
                <flux:error name="title" />
            </div>

            <flux:button type="submit" variant="primary" icon="plus" class="sm:mt-6">
                {{ __('Add') }}
            </flux:button>
        </form>

        <flux:separator />

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div wire:key="todo-{{ $todo->id }}" class="flex min-h-14 items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-white/10 dark:bg-zinc-900">
                    <flux:checkbox
                        :checked="$todo->is_completed"
                        wire:click="toggleTodo({{ $todo->id }})"
                        :aria-label="__('Toggle todo')"
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
                        tooltip="{{ __('Delete') }}"
                        wire:click="deleteTodo({{ $todo->id }})"
                        :aria-label="__('Delete todo')"
                    />
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center dark:border-white/15">
                    <flux:text>{{ __('No todos yet.') }}</flux:text>
                </div>
            @endforelse
        </div>

        @if ($this->completedCount > 0)
            <div class="flex justify-end">
                <flux:button type="button" variant="subtle" size="sm" wire:click="clearCompleted">
                    {{ __('Clear completed') }}
                </flux:button>
            </div>
        @endif
    </flux:card>
</section>
