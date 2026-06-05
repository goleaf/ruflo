<x-layouts::app :title="__('dashboard.title')">
    <div class="space-y-8">
        <section class="flex flex-col gap-5 border-b border-zinc-200 pb-6 dark:border-white/10">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700 dark:text-cyan-300/80">{{ __('dashboard.eyebrow') }}</p>
                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('dashboard.heading') }}</h1>
                    <p class="max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                        {{ __('dashboard.description') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">{{ __('dashboard.cards.mcp.label') }}</p>
                    <p class="mt-3 font-mono text-sm text-zinc-950 dark:text-zinc-100">npx ruflo@latest mcp start</p>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ __('dashboard.cards.mcp.description') }}</p>
                </article>

                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">{{ __('dashboard.cards.plugin.label') }}</p>
                    <p class="mt-3 font-mono text-sm text-zinc-950 dark:text-zinc-100">/plugin marketplace add ruvnet/ruflo</p>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ __('dashboard.cards.plugin.description') }}</p>
                </article>

                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">{{ __('dashboard.cards.demo.label') }}</p>
                    <a href="https://flo.ruv.io/" target="_blank" rel="noreferrer" class="mt-3 block text-sm font-medium text-cyan-700 underline decoration-cyan-300 decoration-2 underline-offset-4 dark:text-cyan-300">
                        flo.ruv.io
                    </a>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ __('dashboard.cards.demo.description') }}</p>
                </article>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">{{ __('dashboard.install.label') }}</p>
                        <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('dashboard.install.heading') }}</h2>
                    </div>
                    <span class="rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs text-cyan-800 dark:border-cyan-400/20 dark:bg-cyan-400/10 dark:text-cyan-100">{{ __('dashboard.install.badge') }}</span>
                </div>

                <pre class="mt-5 overflow-x-auto rounded-2xl border border-zinc-200 bg-zinc-950 px-4 py-4 text-sm leading-6 text-zinc-100 dark:border-white/10"><code>npx ruflo@latest init wizard
claude mcp add ruflo -- npx ruflo@latest mcp start
npm install -g ruflo@latest</code></pre>

                <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                    {{ __('dashboard.install.description') }}
                </p>
            </div>

            <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div>
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">{{ __('dashboard.next.label') }}</p>
                    <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('dashboard.next.heading') }}</h2>
                </div>

                <ol class="mt-5 space-y-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                    <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">1.</span> {{ __('dashboard.next.plugin') }}</li>
                    <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">2.</span> {{ __('dashboard.next.cli') }}</li>
                    <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">3.</span> {{ __('dashboard.next.normal') }}</li>
                </ol>

                <div class="mt-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">{{ __('dashboard.workspace.label') }}</p>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ __('dashboard.workspace.description') }}
                    </p>
                </div>
            </div>
        </section>
    </div>
</x-layouts::app>
