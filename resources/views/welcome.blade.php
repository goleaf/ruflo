<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('welcome.title') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/scss/app.scss', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="bg-zinc-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="min-h-screen">
            <section class="relative isolate overflow-hidden bg-zinc-950 text-white">
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/ruflo-banner.jpeg') }}')" aria-hidden="true"></div>
                <div class="absolute inset-0 bg-zinc-950/70" aria-hidden="true"></div>

                <div class="relative mx-auto flex min-h-[64svh] max-w-7xl flex-col px-5 py-5 sm:px-8 lg:px-10">
                    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ __('welcome.brand.aria') }}">
                            <span class="flex size-11 shrink-0 overflow-hidden rounded-lg border border-white/20 bg-white/10 shadow-sm">
                                <img src="{{ asset('images/ruflo-banner.jpeg') }}" alt="{{ __('welcome.image_alt') }}" class="h-full w-full object-cover">
                            </span>
                            <span>
                                <span class="block text-base font-semibold">{{ __('welcome.brand.name') }}</span>
                                <span class="block text-xs text-zinc-300">{{ __('welcome.brand.line') }}</span>
                            </span>
                        </a>

                        <nav class="flex flex-wrap items-center gap-2 text-sm" aria-label="{{ __('welcome.nav.aria') }}">
                            <a href="#features" class="rounded-md px-3 py-2 text-zinc-200 transition hover:bg-white/10 hover:text-white">{{ __('welcome.nav.features') }}</a>
                            <a href="#workflow" class="rounded-md px-3 py-2 text-zinc-200 transition hover:bg-white/10 hover:text-white">{{ __('welcome.nav.workflow') }}</a>
                            <a href="#privacy" class="rounded-md px-3 py-2 text-zinc-200 transition hover:bg-white/10 hover:text-white">{{ __('welcome.nav.privacy') }}</a>
                            @auth
                                <a href="{{ route('dashboard') }}" class="rounded-md bg-white px-4 py-2 font-medium text-zinc-950 transition hover:bg-zinc-100">{{ __('welcome.nav.dashboard') }}</a>
                            @else
                                <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-zinc-200 transition hover:bg-white/10 hover:text-white">{{ __('welcome.nav.sign_in') }}</a>
                                <a href="{{ route('register') }}" class="rounded-md bg-white px-4 py-2 font-medium text-zinc-950 transition hover:bg-zinc-100">{{ __('welcome.nav.create') }}</a>
                            @endauth
                        </nav>
                    </header>

                    <div class="flex flex-1 items-center py-12 sm:py-16">
                        <div class="max-w-4xl">
                            <p class="inline-flex rounded-md border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase text-zinc-100">{{ __('welcome.hero.eyebrow') }}</p>
                            <h1 class="mt-6 max-w-3xl text-5xl font-semibold tracking-tight text-white sm:text-6xl lg:text-7xl">{{ __('welcome.heading') }}</h1>
                            <p class="mt-5 max-w-2xl text-lg leading-8 text-zinc-200 sm:text-xl">
                                {{ __('welcome.description') }}
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-md bg-white px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-zinc-100">{{ __('welcome.hero.dashboard_cta') }}</a>
                                @else
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md bg-white px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-zinc-100">{{ __('welcome.hero.primary_cta') }}</a>
                                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md border border-white/25 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">{{ __('welcome.hero.secondary_cta') }}</a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <main>
                <section class="mx-auto grid max-w-7xl grid-cols-1 gap-3 px-5 py-6 sm:grid-cols-2 sm:px-8 lg:grid-cols-4 lg:px-10" aria-label="{{ __('welcome.proof.aria') }}">
                    @foreach (__('welcome.proof.items') as $item)
                        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <p class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $item['value'] }}</p>
                            <p class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $item['label'] }}</p>
                        </div>
                    @endforeach
                </section>

                <section id="features" class="border-y border-zinc-200 bg-white py-14 dark:border-white/10 dark:bg-zinc-900">
                    <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase text-sky-700 dark:text-sky-300">{{ __('welcome.features.eyebrow') }}</p>
                            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-4xl">{{ __('welcome.features.heading') }}</h2>
                            <p class="mt-4 text-base leading-7 text-zinc-600 dark:text-zinc-300">{{ __('welcome.features.description') }}</p>
                        </div>

                        <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach (__('welcome.feature_groups') as $feature)
                                <article @class([
                                    'rounded-lg border bg-zinc-50 p-5 shadow-sm dark:bg-white/5',
                                    'border-t-4 border-zinc-200 border-t-sky-500 dark:border-white/10' => $loop->iteration === 1,
                                    'border-t-4 border-zinc-200 border-t-emerald-500 dark:border-white/10' => $loop->iteration === 2,
                                    'border-t-4 border-zinc-200 border-t-amber-500 dark:border-white/10' => $loop->iteration === 3,
                                    'border-t-4 border-zinc-200 border-t-rose-500 dark:border-white/10' => $loop->iteration === 4,
                                    'border-t-4 border-zinc-200 border-t-indigo-500 dark:border-white/10' => $loop->iteration === 5,
                                    'border-t-4 border-zinc-200 border-t-teal-500 dark:border-white/10' => $loop->iteration === 6,
                                    'border-t-4 border-zinc-200 border-t-lime-500 dark:border-white/10' => $loop->iteration === 7,
                                    'border-t-4 border-zinc-200 border-t-cyan-500 dark:border-white/10' => $loop->iteration === 8,
                                    'border-t-4 border-zinc-200 border-t-violet-500 dark:border-white/10' => $loop->iteration === 9,
                                ])>
                                    <p class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ $feature['label'] }}</p>
                                    <h3 class="mt-3 text-xl font-semibold text-zinc-950 dark:text-white">{{ $feature['title'] }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $feature['description'] }}</p>
                                    <ul class="mt-4 space-y-2 text-sm text-zinc-700 dark:text-zinc-200">
                                        @foreach ($feature['items'] as $point)
                                            <li class="flex gap-2">
                                                <span class="mt-2 size-1.5 shrink-0 rounded-full bg-zinc-900 dark:bg-white" aria-hidden="true"></span>
                                                <span>{{ $point }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="workflow" class="bg-zinc-50 py-14 dark:bg-zinc-950">
                    <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                        <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                            <div>
                                <p class="text-sm font-semibold uppercase text-emerald-700 dark:text-emerald-300">{{ __('welcome.workflow.eyebrow') }}</p>
                                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-4xl">{{ __('welcome.workflow.heading') }}</h2>
                                <p class="mt-4 text-base leading-7 text-zinc-600 dark:text-zinc-300">{{ __('welcome.workflow.description') }}</p>
                            </div>

                            <div class="grid gap-3">
                                @foreach (__('welcome.workflow.steps') as $step)
                                    <article class="grid gap-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5 sm:grid-cols-[8rem_1fr]">
                                        <p class="text-sm font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ $step['stage'] }}</p>
                                        <div>
                                            <h3 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $step['title'] }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $step['description'] }}</p>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section id="privacy" class="bg-white py-14 dark:bg-zinc-900">
                    <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                        <div class="grid gap-6 lg:grid-cols-[1fr_1fr] lg:items-stretch">
                            <div class="rounded-lg border border-zinc-200 bg-zinc-950 p-6 text-white shadow-sm dark:border-white/10">
                                <p class="text-sm font-semibold uppercase text-amber-300">{{ __('welcome.privacy.eyebrow') }}</p>
                                <h2 class="mt-3 text-3xl font-semibold tracking-tight">{{ __('welcome.privacy.heading') }}</h2>
                                <p class="mt-4 text-base leading-7 text-zinc-300">{{ __('welcome.privacy.description') }}</p>
                            </div>

                            <div class="grid gap-3">
                                @foreach (__('welcome.privacy.items') as $item)
                                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-white/10 dark:bg-white/5">
                                        <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $item['title'] }}</h3>
                                        <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $item['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-zinc-950 px-5 py-14 text-white sm:px-8 lg:px-10">
                    <div class="mx-auto flex max-w-7xl flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase text-cyan-300">{{ __('welcome.cta.eyebrow') }}</p>
                            <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('welcome.cta.heading') }}</h2>
                            <p class="mt-4 text-base leading-7 text-zinc-300">{{ __('welcome.cta.description') }}</p>
                        </div>

                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center rounded-md bg-white px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-zinc-100">{{ __('welcome.cta.dashboard') }}</a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex shrink-0 items-center justify-center rounded-md bg-white px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-zinc-100">{{ __('welcome.cta.create') }}</a>
                        @endauth
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
