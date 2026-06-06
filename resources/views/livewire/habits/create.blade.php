<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <x-ui.page-header :title="__('habits.pages.create.title')" :description="__('habits.pages.create.description')">
        <flux:button :href="route('habits.index')" wire:navigate variant="ghost" icon="arrow-left">
            {{ __('habits.actions.back_to_habits') }}
        </flux:button>
    </x-ui.page-header>

    <flux:card class="space-y-5" data-test="habit-create">
        <div>
            <flux:heading size="lg">{{ __('habits.create.heading') }}</flux:heading>
            <flux:text class="mt-1">{{ __('habits.create.description') }}</flux:text>
        </div>

        <form wire:submit="createHabit" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1fr]">
                <div>
                    <flux:input wire:model="title" :label="__('habits.fields.title')" :placeholder="__('habits.placeholders.title')" maxlength="120" autocomplete="off" />
                    <flux:error name="title" />
                </div>

                <div>
                    <flux:select wire:model.live="frequency" :label="__('habits.fields.frequency')">
                        <flux:select.option value="daily">{{ __('habits.frequency.daily') }}</flux:select.option>
                        <flux:select.option value="weekly">{{ __('habits.frequency.weekly') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="frequency" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_12rem]">
                <div>
                    <flux:textarea wire:model="description" :label="__('habits.fields.description')" :placeholder="__('habits.placeholders.description')" rows="3" maxlength="2000" />
                    <flux:error name="description" />
                </div>

                <div>
                    <flux:input type="number" min="1" max="7" wire:model="targetCount" :label="__('habits.fields.target_count')" />
                    <flux:error name="target_count" />
                </div>
            </div>

            <div>
                <flux:select wire:model="goalId" :label="__('habits.fields.goal')">
                    <flux:select.option value="">{{ __('habits.fields.no_goal') }}</flux:select.option>
                    @foreach ($this->goals as $goal)
                        <flux:select.option value="{{ $goal->id }}">{{ $goal->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="goal_id" />
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="createHabit">
                    {{ __('habits.actions.create') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</section>
