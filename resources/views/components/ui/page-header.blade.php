@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col gap-4 border-b border-zinc-200 pb-6 dark:border-white/10 md:flex-row md:items-end md:justify-between') }}>
    <div class="space-y-2">
        <flux:heading size="xl">{{ $title }}</flux:heading>

        @if ($description)
            <flux:text>{{ $description }}</flux:text>
        @endif
    </div>

    {{ $slot }}
</div>
