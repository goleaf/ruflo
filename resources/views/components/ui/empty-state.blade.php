@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('rounded-lg border border-dashed border-zinc-300 p-6 text-center dark:border-white/15') }}>
    <flux:heading size="md">{{ $title }}</flux:heading>

    @if ($description)
        <flux:text class="mt-2">{{ $description }}</flux:text>
    @endif
</div>
