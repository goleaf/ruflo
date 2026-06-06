<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <x-ui.page-header :title="__('goals.pages.create_milestone.title')" :description="__('goals.pages.create_milestone.description')">
        <flux:button :href="route('goals.index')" wire:navigate variant="ghost" icon="arrow-left">
            {{ __('goals.actions.back_to_goals') }}
        </flux:button>
    </x-ui.page-header>

    <div class="space-y-4" data-test="goals-tabs">
        <div role="tablist" class="flex flex-wrap gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-900">
            <a href="{{ route('goals.index') }}" wire:navigate role="tab" aria-selected="false" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                {{ __('goals.tabs.goals') }}
            </a>

            <a href="{{ route('goals.create') }}" wire:navigate role="tab" aria-selected="false" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                {{ __('goals.tabs.create') }}
            </a>

            <button type="button" role="tab" aria-selected="true" class="rounded-md bg-white px-3 py-1.5 text-sm font-medium text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white">
                {{ __('goals.tabs.milestones') }}
            </button>
        </div>
    </div>

    <flux:card class="space-y-5" data-test="goal-milestone-create">
        <div>
            <flux:heading size="lg">{{ __('goals.milestones.create_heading') }}</flux:heading>
            <flux:text class="mt-1">{{ __('goals.milestones.create_description') }}</flux:text>
        </div>

        <form wire:submit="addMilestone" class="space-y-4">
            <div>
                <flux:select wire:model="milestoneGoalId" :label="__('goals.fields.goal')">
                    <flux:select.option value="">{{ __('goals.fields.choose_goal') }}</flux:select.option>
                    @foreach ($this->goals as $goal)
                        <flux:select.option value="{{ $goal->id }}">{{ $goal->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="goal_id" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_12rem]">
                <div>
                    <flux:input wire:model="milestoneTitle" :label="__('goals.fields.milestone_title')" :placeholder="__('goals.placeholders.milestone_title')" maxlength="120" autocomplete="off" />
                    <flux:error name="title" />
                </div>

                <div>
                    <flux:input type="date" wire:model="milestoneTargetDate" :label="__('goals.fields.target_date')" />
                    <flux:error name="target_date" />
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="addMilestone">
                    {{ __('goals.actions.add_milestone') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</section>
