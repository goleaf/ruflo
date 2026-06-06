@props([
    'name',
    'label',
    'model',
    'placeholder' => __('todos.datepicker.placeholder'),
])

<div
    x-data="modalDatePicker({
        modalName: @js($name),
        value: $wire.entangle(@js($model)).live,
    })"
    x-id="['datepicker-input']"
    class="space-y-2"
    data-modal-datepicker="{{ $name }}"
>
    <label x-bind:for="$id('datepicker-input')" class="block text-sm font-medium text-zinc-800 dark:text-white">
        {{ $label }}
    </label>

    <div class="flex items-center gap-2">
        <input
            x-bind:id="$id('datepicker-input')"
            x-model.debounce.300ms="value"
            type="text"
            inputmode="numeric"
            autocomplete="off"
            placeholder="{{ $placeholder }}"
            pattern="\d{4}-\d{2}-\d{2}"
            class="block min-h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-950 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-75 dark:border-white/10 dark:bg-white/10 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-400 dark:focus:ring-blue-400/20"
        >

        <flux:modal.trigger :name="$name">
            <flux:button type="button" icon="calendar" :aria-label="__('todos.datepicker.open')" />
        </flux:modal.trigger>
    </div>

    <input type="hidden" name="{{ $model }}" x-bind:value="value ?? ''">

    <flux:modal :name="$name" class="md:w-[23rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $label }}</flux:heading>
            </div>

            <div
                wire:ignore
                class="flex min-h-80 justify-center rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-950"
            >
                <input x-ref="calendar" type="text" class="sr-only" aria-label="{{ $label }}" tabindex="-1">
            </div>

            <p x-show="isLoading" class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('todos.datepicker.loading') }}
            </p>

            <p x-show="loadError" class="text-sm text-red-600 dark:text-red-400">
                {{ __('todos.datepicker.unavailable') }}
            </p>

            <div class="flex gap-2">
                <flux:button type="button" variant="ghost" x-on:click="clear">
                    {{ __('todos.datepicker.clear') }}
                </flux:button>

                <flux:spacer />

                <flux:modal.close>
                    <flux:button type="button" variant="primary">
                        {{ __('todos.datepicker.done') }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
