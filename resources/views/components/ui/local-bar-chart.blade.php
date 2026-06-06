@props([
    'accessibleLabel',
    'description' => null,
    'eyebrow' => null,
    'heading' => null,
    'headingSize' => 'lg',
    'itemKeyPrefix' => 'local-bar-chart',
    'items' => [],
    'test' => null,
])

<div
    {{ $attributes
        ->merge([
            'aria-label' => $accessibleLabel,
            'data-chart-driver' => 'local-css',
            'data-chart-library' => 'none',
            'data-chart-type' => 'bar',
            'data-test' => $test,
            'role' => 'img',
        ])
        ->class('space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-white/10') }}
>
    <div class="space-y-1">
        @if ($eyebrow)
            <flux:subheading>{{ $eyebrow }}</flux:subheading>
        @endif

        @if ($heading)
            <flux:heading :size="$headingSize">{{ $heading }}</flux:heading>
        @endif

        @if ($description)
            <flux:text size="sm">{{ $description }}</flux:text>
        @endif
    </div>

    <div class="space-y-3">
        @foreach ($items as $bar)
            <div wire:key="{{ $itemKeyPrefix }}-{{ $bar['key'] }}" class="space-y-1" data-chart-row="{{ $bar['key'] }}" data-test="{{ $itemKeyPrefix }}-{{ $bar['key'] }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $bar['label'] }}</span>
                    <span class="text-sm tabular-nums text-zinc-500 dark:text-zinc-400">{{ $bar['display_value'] ?? $bar['value'] }}</span>
                </div>

                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                    <span aria-hidden="true" class="block h-full rounded-full bg-blue-600 dark:bg-blue-400" style="width: {{ $bar['percent'] }}%"></span>
                </div>

                <span class="sr-only">{{ $bar['summary'] }}</span>
            </div>
        @endforeach
    </div>
</div>
