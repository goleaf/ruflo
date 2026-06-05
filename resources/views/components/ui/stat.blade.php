@props([
    'label',
    'value',
    'tone' => 'default',
])

@php
    $valueClass = match ($tone) {
        'danger' => 'text-red-700 dark:text-red-300',
        'success' => 'text-emerald-700 dark:text-emerald-300',
        'muted' => 'text-zinc-600 dark:text-zinc-300',
        default => 'text-zinc-950 dark:text-white',
    };
@endphp

<div {{ $attributes->class('rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5') }}>
    <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
    <div @class(['mt-1 text-2xl font-semibold', $valueClass])>{{ $value }}</div>
</div>
