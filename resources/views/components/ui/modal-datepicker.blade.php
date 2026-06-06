@props([
    'name',
    'label',
    'model',
    'placeholder' => __('todos.datepicker.placeholder'),
])

<div
    x-data="{
        value: $wire.entangle(@js($model)).live,
        openPicker() {
            if (this.$refs.dateInput?.showPicker) {
                this.$refs.dateInput.showPicker();

                return;
            }

            this.$refs.dateInput?.focus();
        },
        clear() {
            this.value = '';
            this.$refs.dateInput?.focus();
        },
    }"
    x-id="['datepicker-input']"
    class="space-y-2"
    data-modal-datepicker="{{ $name }}"
>
    <label x-bind:for="$id('datepicker-input')" class="block text-sm font-medium text-zinc-800 dark:text-white">
        {{ $label }}
    </label>

    <div class="flex items-center gap-2">
        <input
            x-ref="dateInput"
            x-bind:id="$id('datepicker-input')"
            x-model="value"
            type="date"
            autocomplete="off"
            placeholder="{{ $placeholder }}"
            class="block min-h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-950 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-75 dark:border-white/10 dark:bg-white/10 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-400 dark:focus:ring-blue-400/20"
        >

        <flux:button type="button" icon="calendar" x-on:click="openPicker()" :aria-label="__('todos.datepicker.open')" />

        <flux:button type="button" variant="ghost" icon="x-mark" x-on:click="clear()" :aria-label="__('todos.datepicker.clear')" />
    </div>

    <input type="hidden" name="{{ $model }}" x-bind:value="value ?? ''">
</div>
