<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('settings.delete_account.heading') }}</flux:heading>
        <flux:subheading>{{ __('settings.delete_account.subheading') }}</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
            {{ __('settings.delete_account.button') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('settings.delete_account.confirm_heading') }}</flux:heading>

                <flux:subheading>
                    {{ __('settings.delete_account.confirm_body') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="password" :label="__('auth.labels.password')" type="password" viewable />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('settings.actions.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">{{ __('settings.delete_account.button') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
