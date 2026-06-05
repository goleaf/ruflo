<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>RuFlo</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance

        <style>
            .ruflo-grid {
                background-image:
                    linear-gradient(rgba(9, 9, 11, 0.05) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(9, 9, 11, 0.05) 1px, transparent 1px);
                background-size: 48px 48px;
            }

            .dark .ruflo-grid {
                background-image:
                    linear-gradient(rgba(255, 255, 255, 0.045) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, 0.045) 1px, transparent 1px);
            }

            .ruflo-veil {
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.36), rgba(255, 255, 255, 0) 28%);
            }

            .dark .ruflo-veil {
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0) 28%);
            }
        </style>
    </head>
    <body class="bg-white text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="relative min-h-screen overflow-hidden ruflo-grid">
            <div class="absolute inset-0 ruflo-veil"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6 lg:px-10">
                <header class="flex flex-col gap-4 border-b border-zinc-200 pb-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-11 shrink-0 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <img src="{{ asset('images/ruflo-banner.jpeg') }}" alt="RuFlo banner" class="h-full w-full object-cover">
                        </div>

                        <div>
                            <p class="text-[0.65rem] uppercase tracking-[0.32em] text-cyan-700 dark:text-cyan-300/80">Agent orchestration platform</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">Multi-agent AI harness for Claude Code and Codex</p>
                        </div>
                    </div>

                    <a
                        href="https://github.com/ruvnet/ruflo"
                        target="_blank"
                        rel="noreferrer"
                        class="inline-flex items-center justify-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-900 transition hover:border-cyan-300 hover:bg-cyan-50 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:hover:border-cyan-400/40 dark:hover:bg-cyan-400/10"
                    >
                        Source
                    </a>
                </header>

                <main class="grid flex-1 gap-8 py-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-start">
                    <section class="space-y-8">
                        <div class="space-y-5">
                            <p class="inline-flex rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.3em] text-cyan-800 dark:border-cyan-400/25 dark:bg-cyan-400/10 dark:text-cyan-200">Full loop or plugin-only</p>
                            <h1 class="max-w-3xl text-5xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-6xl">RuFlo</h1>
                            <p class="max-w-2xl text-lg leading-8 text-zinc-600 dark:text-zinc-300">
                                Install the CLI when you want the full swarm, memory, hooks, and daemon.
                                Use the plugin path when you only need slash commands and agent definitions.
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <article class="rounded-2xl border border-zinc-200 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Step 1</p>
                                <h2 class="mt-3 text-lg font-medium text-zinc-950 dark:text-white">Choose your path</h2>
                                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">Plugins keep the workspace light. CLI installs the complete loop.</p>
                            </article>

                            <article class="rounded-2xl border border-zinc-200 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Step 2</p>
                                <h2 class="mt-3 text-lg font-medium text-zinc-950 dark:text-white">Register MCP</h2>
                                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">The full loop becomes callable from Claude Code once the server is added.</p>
                            </article>

                            <article class="rounded-2xl border border-zinc-200 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Step 3</p>
                                <h2 class="mt-3 text-lg font-medium text-zinc-950 dark:text-white">Use normally</h2>
                                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">Claude Code routes work through RuFlo in the background.</p>
                            </article>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <section class="rounded-3xl border border-zinc-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Path A</p>
                                        <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">Claude Code plugins</h2>
                                    </div>

                                    <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs text-zinc-600 dark:border-white/10 dark:bg-white/5 dark:text-zinc-400">Slash commands only</span>
                                </div>

                                <pre class="mt-5 overflow-x-auto rounded-2xl border border-zinc-200 bg-zinc-950 px-4 py-4 text-sm leading-6 text-zinc-100 dark:border-white/10"><code>/plugin marketplace add ruvnet/ruflo
/plugin install ruflo-core@ruflo
/plugin install ruflo-swarm@ruflo
/plugin install ruflo-rag-memory@ruflo
/plugin install ruflo-neural-trader@ruflo</code></pre>

                                <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                    Use this when you want agent definitions and slash commands without registering the full MCP server.
                                </p>
                            </section>

                            <section class="rounded-3xl border border-cyan-200 bg-cyan-50/70 p-6 shadow-sm backdrop-blur dark:border-cyan-400/20 dark:bg-cyan-400/5">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.28em] text-cyan-700 dark:text-cyan-300/80">Path B</p>
                                        <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">CLI install</h2>
                                    </div>

                                    <span class="rounded-full border border-cyan-200 bg-white px-3 py-1 text-xs text-cyan-800 dark:border-cyan-400/20 dark:bg-cyan-400/10 dark:text-cyan-100">Full loop</span>
                                </div>

                                <pre class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-zinc-950 px-4 py-4 text-sm leading-6 text-zinc-100 dark:border-cyan-400/20"><code>npx ruflo@latest init wizard
claude mcp add ruflo -- npx ruflo@latest mcp start
npm install -g ruflo@latest</code></pre>

                                <p class="mt-4 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                                    This path installs the MCP server, hooks, daemon, memory, and the full swarm workflow.
                                </p>
                            </section>
                        </div>
                    </section>

                    <aside class="space-y-4">
                        <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white/90 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <img src="{{ asset('images/ruflo-banner.jpeg') }}" alt="RuFlo banner" class="h-full w-full object-cover">
                        </div>

                        <div class="rounded-3xl border border-zinc-200 bg-white/90 p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">Use it</p>
                            <ol class="mt-4 space-y-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">1.</span> Install either the plugin path or the CLI path.</li>
                                <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">2.</span> If you use the CLI path, register the MCP server in Claude Code.</li>
                                <li><span class="mr-2 font-semibold text-zinc-950 dark:text-white">3.</span> Keep using Claude Code normally. RuFlo routes the background coordination.</li>
                            </ol>
                        </div>

                        <div class="rounded-3xl border border-zinc-200 bg-white/90 p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">What you get</p>
                            <ul class="mt-4 space-y-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                <li>100+ agents for coding, testing, security, docs, and architecture.</li>
                                <li>Shared memory, swarms, hooks, and background workers.</li>
                                <li>Federation support for secure cross-machine collaboration.</li>
                                <li>Hosted demo at <a href="https://flo.ruv.io/" target="_blank" rel="noreferrer" class="font-medium text-cyan-700 underline decoration-cyan-300 decoration-2 underline-offset-4 dark:text-cyan-300">flo.ruv.io</a> if you want to see it first.</li>
                            </ul>
                        </div>
                    </aside>
                </main>

                <footer class="flex flex-col gap-3 border-t border-zinc-200 py-4 text-sm text-zinc-500 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                    <p>RuFlo is the command layer; this page keeps the install paths in one place.</p>

                    <div class="flex flex-wrap gap-4">
                        <a href="https://flo.ruv.io/" target="_blank" rel="noreferrer" class="font-medium text-zinc-700 underline decoration-cyan-300 decoration-2 underline-offset-4 dark:text-zinc-300">Hosted demo</a>
                        <a href="https://github.com/ruvnet/ruflo" target="_blank" rel="noreferrer" class="font-medium text-zinc-700 underline decoration-cyan-300 decoration-2 underline-offset-4 dark:text-zinc-300">Source</a>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
