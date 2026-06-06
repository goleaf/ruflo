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
                    {{ $this->todo->project?->name ?? __('todos.fields.no_project') }}
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
                    <flux:badge wire:key="detail-tag-{{ $tagBadge->id }}" size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                @empty
                    <flux:text class="text-sm">{{ __('todos.fields.no_tags') }}</flux:text>
                @endforelse
            </div>
        </div>
    </flux:card>
</section>
