@props([
    'gap' => 'gap-6',
])

<section {{ $attributes->class(['mx-auto flex w-full max-w-6xl flex-col', $gap]) }}>
    {{ $slot }}
</section>
