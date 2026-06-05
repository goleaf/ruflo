@props([
    'status',
])

{{-- Renders a task's lifecycle status as a consistent Flux badge. --}}
<flux:badge :color="$status->color()" size="sm" {{ $attributes }}>
    {{ $status->label() }}
</flux:badge>
