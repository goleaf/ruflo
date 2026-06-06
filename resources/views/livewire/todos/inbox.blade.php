<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.page-header :title="__('todos.pages.inbox.title')" :description="__('todos.pages.inbox.description')">
        <div class="flex flex-col gap-3 sm:min-w-72">
            <x-ui.stat :label="__('todos.inbox.count')" :value="$this->inboxTodos->total()" />

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                    {{ __('todos.actions.back_to_list') }}
                </flux:button>
            </div>
        </div>
    </x-ui.page-header>

    <flux:card>
        <form wire:submit="capture" class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="min-w-0 flex-1">
                <flux:input
                    wire:model="captureTitle"
                    :label="__('todos.inbox.fields.capture_title')"
                    :placeholder="__('todos.inbox.placeholders.capture_title')"
                    maxlength="120"
                    autocomplete="off"
                />
                <flux:error name="captureTitle" />
            </div>

            <flux:button type="submit" variant="primary" icon="plus" class="sm:mt-6" wire:loading.attr="disabled" wire:target="capture">
                {{ __('todos.inbox.actions.capture') }}
            </flux:button>
        </form>
    </flux:card>

    <flux:card class="space-y-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:subheading>{{ __('todos.inbox.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.inbox.heading') }}</flux:heading>
            </div>

            <flux:badge color="blue" icon="inbox">{{ __('todos.inbox.badge') }}</flux:badge>
        </div>

        <div class="space-y-2">
            @forelse ($this->inboxTodos as $todo)
                <div wire:key="inbox-todo-{{ $todo->id }}" class="flex min-h-16 items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/10 dark:bg-zinc-900">
                    <div class="min-w-0 flex-1 space-y-1">
                        <a href="{{ route('todos.show', $todo) }}" wire:navigate class="text-sm font-medium break-words text-zinc-950 dark:text-white">
                            {{ $todo->title }}
                        </a>

                        <div class="flex flex-wrap items-center gap-1.5">
                            <flux:badge size="sm" color="blue" icon="inbox">
                                {{ __('todos.inbox.captured_at', ['time' => $todo->inbox_captured_at?->diffForHumans()]) }}
                            </flux:badge>

                            @if ($todo->priority->value !== 'normal')
                                <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <flux:button size="sm" variant="subtle" icon="adjustments-horizontal" wire:click="startTriage({{ $todo->id }})">
                        {{ __('todos.inbox.actions.triage') }}
                    </flux:button>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.inbox.empty.title')"
                    :description="__('todos.inbox.empty.description')"
                />
            @endforelse
        </div>

        @if ($this->inboxTodos->hasPages())
            <div>{{ $this->inboxTodos->links() }}</div>
        @endif
    </flux:card>

    <flux:modal wire:model.self="showTriageModal" @close="closeTriage" class="md:w-[28rem]">
        <form wire:submit="saveTriage" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('todos.inbox.triage.heading') }}</flux:heading>
                <flux:text class="mt-1">{{ __('todos.inbox.triage.description') }}</flux:text>
            </div>

            <div>
                <flux:input wire:model="triageForm.title" :label="__('todos.fields.title')" maxlength="120" autocomplete="off" />
                <flux:error name="triageForm.title" />
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <flux:select wire:model="triageForm.priority" :label="__('todos.fields.priority')">
                        @foreach ($this->priorityOptions() as $priority)
                            <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="triageForm.priority" />
                </div>

                <div>
                    <flux:input type="date" wire:model="triageForm.due_date" :label="__('todos.fields.due_date')" />
                    <flux:error name="triageForm.due_date" />
                </div>
            </div>

            <div>
                <flux:select wire:model="triageForm.project_id" :label="__('todos.fields.project')">
                    <flux:select.option value="">{{ __('todos.fields.no_project') }}</flux:select.option>
                    @foreach ($this->projects as $project)
                        <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="triageForm.project_id" />
            </div>

            @if ($this->tags->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('todos.fields.tags') }}</span>
                    @foreach ($this->tags as $tagOption)
                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 px-2.5 py-1 text-sm dark:border-white/15">
                            <input type="checkbox" wire:model="triageForm.tag_ids" value="{{ $tagOption->id }}" class="rounded border-zinc-300 text-blue-600">
                            {{ $tagOption->name }}
                        </label>
                    @endforeach
                    @error('triageForm.tag_ids.*')
                        <flux:text class="basis-full text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            @endif

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="closeTriage">{{ __('todos.actions.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveTriage">
                    {{ __('todos.inbox.actions.save_triage') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
