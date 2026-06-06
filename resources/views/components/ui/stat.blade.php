@props([
    'label',
    'value',
    'tone' => 'default',
    'variant' => 'plain',
])

@php
    $coloredTheme = match ($tone) {
        'amber', 'warning' => [
            'card' => 'border-amber-200 bg-amber-50 text-amber-950 shadow-amber-100/70 dark:border-amber-400/25 dark:bg-amber-400/10 dark:text-amber-50 dark:shadow-none',
            'label' => 'text-amber-700 dark:text-amber-200/80',
            'value' => 'text-amber-950 dark:text-amber-50',
        ],
        'cyan' => [
            'card' => 'border-cyan-200 bg-cyan-50 text-cyan-950 shadow-cyan-100/70 dark:border-cyan-400/25 dark:bg-cyan-400/10 dark:text-cyan-50 dark:shadow-none',
            'label' => 'text-cyan-700 dark:text-cyan-200/80',
            'value' => 'text-cyan-950 dark:text-cyan-50',
        ],
        'danger', 'red', 'rose' => [
            'card' => 'border-rose-200 bg-rose-50 text-rose-950 shadow-rose-100/70 dark:border-rose-400/25 dark:bg-rose-400/10 dark:text-rose-50 dark:shadow-none',
            'label' => 'text-rose-700 dark:text-rose-200/80',
            'value' => 'text-rose-950 dark:text-rose-50',
        ],
        'emerald', 'success' => [
            'card' => 'border-emerald-200 bg-emerald-50 text-emerald-950 shadow-emerald-100/70 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-50 dark:shadow-none',
            'label' => 'text-emerald-700 dark:text-emerald-200/80',
            'value' => 'text-emerald-950 dark:text-emerald-50',
        ],
        'fuchsia' => [
            'card' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-950 shadow-fuchsia-100/70 dark:border-fuchsia-400/25 dark:bg-fuchsia-400/10 dark:text-fuchsia-50 dark:shadow-none',
            'label' => 'text-fuchsia-700 dark:text-fuchsia-200/80',
            'value' => 'text-fuchsia-950 dark:text-fuchsia-50',
        ],
        'indigo' => [
            'card' => 'border-indigo-200 bg-indigo-50 text-indigo-950 shadow-indigo-100/70 dark:border-indigo-400/25 dark:bg-indigo-400/10 dark:text-indigo-50 dark:shadow-none',
            'label' => 'text-indigo-700 dark:text-indigo-200/80',
            'value' => 'text-indigo-950 dark:text-indigo-50',
        ],
        'lime' => [
            'card' => 'border-lime-200 bg-lime-50 text-lime-950 shadow-lime-100/70 dark:border-lime-400/25 dark:bg-lime-400/10 dark:text-lime-50 dark:shadow-none',
            'label' => 'text-lime-700 dark:text-lime-200/80',
            'value' => 'text-lime-950 dark:text-lime-50',
        ],
        'muted', 'slate' => [
            'card' => 'border-slate-200 bg-slate-50 text-slate-950 shadow-slate-100/70 dark:border-slate-400/25 dark:bg-slate-400/10 dark:text-slate-50 dark:shadow-none',
            'label' => 'text-slate-700 dark:text-slate-200/80',
            'value' => 'text-slate-950 dark:text-slate-50',
        ],
        'teal' => [
            'card' => 'border-teal-200 bg-teal-50 text-teal-950 shadow-teal-100/70 dark:border-teal-400/25 dark:bg-teal-400/10 dark:text-teal-50 dark:shadow-none',
            'label' => 'text-teal-700 dark:text-teal-200/80',
            'value' => 'text-teal-950 dark:text-teal-50',
        ],
        'violet' => [
            'card' => 'border-violet-200 bg-violet-50 text-violet-950 shadow-violet-100/70 dark:border-violet-400/25 dark:bg-violet-400/10 dark:text-violet-50 dark:shadow-none',
            'label' => 'text-violet-700 dark:text-violet-200/80',
            'value' => 'text-violet-950 dark:text-violet-50',
        ],
        default => [
            'card' => 'border-sky-200 bg-sky-50 text-sky-950 shadow-sky-100/70 dark:border-sky-400/25 dark:bg-sky-400/10 dark:text-sky-50 dark:shadow-none',
            'label' => 'text-sky-700 dark:text-sky-200/80',
            'value' => 'text-sky-950 dark:text-sky-50',
        ],
    };

    $plainValueClass = match ($tone) {
        'danger', 'red', 'rose' => 'text-red-700 dark:text-red-300',
        'emerald', 'success', 'teal' => 'text-emerald-700 dark:text-emerald-300',
        'muted', 'slate' => 'text-zinc-600 dark:text-zinc-300',
        default => 'text-zinc-950 dark:text-white',
    };

    $cardClass = $variant === 'colored'
        ? 'min-h-[5.75rem] rounded-lg border p-3 shadow-sm '.$coloredTheme['card']
        : 'rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5';

    $labelClass = $variant === 'colored'
        ? $coloredTheme['label']
        : 'text-zinc-500 dark:text-zinc-400';

    $valueClass = $variant === 'colored'
        ? $coloredTheme['value']
        : $plainValueClass;
@endphp

<div {{ $attributes->class($cardClass) }}>
    <div @class(['text-[0.68rem] font-semibold uppercase leading-tight', $labelClass])>{{ $label }}</div>
    <div @class(['mt-2 text-2xl font-semibold leading-none', $valueClass])>{{ $value }}</div>
</div>
