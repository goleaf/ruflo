<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <x-ui.page-header :title="__('goals.pages.create.title')" :description="__('goals.pages.create.description')">
        <flux:button :href="route('goals.index')" wire:navigate variant="ghost" icon="arrow-left">
            {{ __('goals.actions.back_to_goals') }}
        </flux:button>
    </x-ui.page-header>

    <div class="space-y-4" data-test="goals-tabs">
        <div role="tablist" class="flex flex-wrap gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-900">
            <a href="{{ route('goals.index') }}" wire:navigate role="tab" aria-selected="false" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                {{ __('goals.tabs.goals') }}
            </a>

            <button type="button" role="tab" aria-selected="true" class="rounded-md bg-white px-3 py-1.5 text-sm font-medium text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white">
                {{ __('goals.tabs.create') }}
            </button>

            <a href="{{ route('goals.milestones.create') }}" wire:navigate role="tab" aria-selected="false" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                {{ __('goals.tabs.milestones') }}
            </a>
        </div>
    </div>

    <flux:card class="space-y-5" data-test="goal-create">
        <div>
            <flux:heading size="lg">{{ __('goals.create.heading') }}</flux:heading>
            <flux:text class="mt-1">{{ __('goals.create.description') }}</flux:text>
        </div>

        <form wire:submit="createGoal" class="space-y-4">
            <div>
                <flux:input wire:model="title" :label="__('goals.fields.title')" :placeholder="__('goals.placeholders.title')" maxlength="120" autocomplete="off" />
                <flux:error name="title" />
            </div>

            <div>
                <flux:textarea wire:model="description" :label="__('goals.fields.description')" :placeholder="__('goals.placeholders.description')" rows="3" maxlength="2000" />
                <flux:error name="description" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:select wire:model="projectId" :label="__('goals.fields.project')">
                        <flux:select.option value="">{{ __('goals.fields.no_project') }}</flux:select.option>
                        @foreach ($this->projects as $project)
                            <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="project_id" />
                </div>

                <div>
                    <flux:input type="date" wire:model="targetDate" :label="__('goals.fields.target_date')" />
                    <flux:error name="target_date" />
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" icon="flag" wire:loading.attr="disabled" wire:target="createGoal">
                    {{ __('goals.actions.create') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</section>
