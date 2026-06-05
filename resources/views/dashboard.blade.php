<x-layouts::app :title="__('RuFlo Control Deck')">
    <div class="space-y-8">
        <section class="flex flex-col gap-5 border-b border-zinc-200 pb-6 dark:border-white/10">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700 dark:text-cyan-300/80">Authenticated workspace</p>
                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">RuFlo Control Deck</h1>
                    <p class="max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                        This workspace is already branded for RuFlo and wired into the existing MCP manifests.
                        Use this page when you want the shortest path to the CLI install, the plugin path, or the hosted demo.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">MCP server</p>
                    <p class="mt-3 font-mono text-sm text-zinc-950 dark:text-zinc-100">npx ruflo@latest mcp start</p>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">Register this in Claude Code to expose the full loop.</p>
                </article>

                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Plugin path</p>
                    <p class="mt-3 font-mono text-sm text-zinc-950 dark:text-zinc-100">/plugin marketplace add ruvnet/ruflo</p>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">Use this for slash commands and agent definitions only.</p>
                </article>

                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Hosted demo</p>
                    <a href="https://flo.ruv.io/" target="_blank" rel="noreferrer" class="mt-3 block text-sm font-medium text-cyan-700 underline decoration-cyan-300 decoration-2 underline-offset-4 dark:text-cyan-300">
                        flo.ruv.io
                    </a>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">Open the web UI first if you want to inspect the hosted experience.</p>
                </article>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">CLI path</p>
                        <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">Full install</h2>
                    </div>
                    <span class="rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs text-cyan-800 dark:border-cyan-400/20 dark:bg-cyan-400/10 dark:text-cyan-100">Hooks + daemon + memory</span>
                </div>

                <pre class="mt-5 overflow-x-auto rounded-2xl border border-zinc-200 bg-zinc-950 px-4 py-4 text-sm leading-6 text-zinc-100 dark:border-white/10"><code>npx ruflo@latest init wizard
claude mcp add ruflo -- npx ruflo@latest mcp start
npm install -g ruflo@latest</code></pre>

                <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                    Run the wizard if you want the safest guided install. Use the direct MCP command when you already know the target client.
                </p>
            </div>

            <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div>
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Quick use</p>
                    <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">What to do next</h2>
                </div>

                <ol class="mt-5 space-y-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                    <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">1.</span> Pick the plugin path if you only need commands and agents.</li>
                    <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">2.</span> Pick the CLI path if you want swarms, hooks, memory, and the daemon.</li>
                    <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">3.</span> Keep using Claude Code normally. RuFlo handles coordination in the background.</li>
                </ol>

                <div class="mt-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Workspace note</p>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        The local agent manifests in this repo already register both Laravel Boost and RuFlo.
                    </p>
                </div>
            </div>
        </section>
    </div>
</x-layouts::app>
