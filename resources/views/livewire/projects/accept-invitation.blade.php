<x-ui.page-container>
    <x-ui.page-header
        :title="__('todos.collaboration.invites.accept.title')"
        :description="__('todos.collaboration.invites.accept.description')"
        :breadcrumbs="[
            ['label' => __('navigation.items.dashboard'), 'href' => route('dashboard'), 'icon' => 'home'],
            ['label' => __('todos.collaboration.invites.accept.title')],
        ]"
    >
        <flux:button :href="route('dashboard')" wire:navigate variant="ghost" icon="arrow-left">
            {{ __('navigation.items.dashboard') }}
        </flux:button>
    </x-ui.page-header>

    <flux:card class="mx-auto max-w-2xl space-y-5" data-test="project-invite-accept">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge size="sm" :color="$this->status->color()">
                {{ $this->status->label() }}
            </flux:badge>

            <flux:badge size="sm" :color="$this->invitation->role->color()">
                {{ __('todos.collaboration.invites.accept.role', ['role' => $this->invitation->role->label()]) }}
            </flux:badge>
        </div>

        <div class="space-y-2">
            <flux:heading size="lg">{{ __('todos.collaboration.invites.accept.heading') }}</flux:heading>
            <flux:text>{{ __('todos.collaboration.invites.accept.body') }}</flux:text>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('todos.collaboration.invites.accept.expires_at', ['date' => $this->invitation->expires_at->isoFormat('MMM D, YYYY')]) }}
            </flux:text>
        </div>

        @if ($errorMessage)
            <flux:callout icon="x-circle" variant="danger" data-test="project-invite-accept-error">
                <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
            </flux:callout>
        @endif

        <flux:error name="invitation" />

        @if ($this->canAccept)
            <flux:callout icon="lock-closed" variant="secondary" data-test="project-invite-accept-privacy">
                <flux:callout.heading>{{ __('todos.collaboration.invites.accept.privacy_heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('todos.collaboration.invites.accept.privacy_description') }}</flux:callout.text>
            </flux:callout>

            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <flux:button :href="route('dashboard')" wire:navigate variant="ghost" class="w-full sm:w-auto">
                    {{ __('todos.actions.cancel') }}
                </flux:button>

                <flux:button type="button" wire:click="accept" wire:loading.attr="disabled" wire:target="accept" variant="primary" icon="check-circle" class="w-full sm:w-auto">
                    {{ __('todos.collaboration.invites.accept.action') }}
                </flux:button>
            </div>
        @else
            <flux:callout icon="exclamation-triangle" variant="secondary" data-test="project-invite-accept-unavailable">
                <flux:callout.heading>{{ __('todos.collaboration.invites.accept.unavailable_heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('todos.collaboration.invites.accept.unavailable_description') }}</flux:callout.text>
            </flux:callout>
        @endif
    </flux:card>
</x-ui.page-container>
