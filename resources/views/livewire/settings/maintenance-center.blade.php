<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('maintenance.pages.center.title') }}</flux:heading>

    <x-settings.layout :heading="__('maintenance.pages.center.heading')" :subheading="__('maintenance.pages.center.subheading')">
        <div class="space-y-6" data-test="maintenance-center">
            <flux:callout icon="wrench-screwdriver" variant="secondary">
                <flux:callout.heading>
                    {{ $snapshot['setup']['ready'] ? __('maintenance.messages.ready') : __('maintenance.messages.review_needed') }}
                </flux:callout.heading>

                <flux:callout.text>
                    {{ __('maintenance.messages.web_only') }}
                </flux:callout.text>
            </flux:callout>

            @if ($lastAction)
                <flux:callout icon="check-circle" variant="secondary" data-test="last-maintenance-action">
                    <flux:callout.text>{{ $lastAction }}</flux:callout.text>
                </flux:callout>
            @endif

            <section class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="sm">{{ __('maintenance.sections.health') }}</flux:heading>

                    <flux:button size="sm" icon="arrow-path" wire:click="refresh">
                        {{ __('maintenance.actions.refresh') }}
                    </flux:button>
                </div>

                <div class="space-y-2">
                    @foreach ($snapshot['setup']['checks'] as $check)
                        <div wire:key="maintenance-check-{{ $check['key'] }}" class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 p-3 dark:border-white/10">
                            <div class="min-w-0">
                                <flux:text class="font-medium">{{ __("setup.checks.{$check['key']}") }}</flux:text>
                                <flux:text variant="subtle" class="break-words">
                                    @isset($check['value_key'])
                                        {{ __("setup.values.{$check['value_key']}", ['value' => $check['value']]) }}
                                    @else
                                        {{ $check['value'] }}
                                    @endisset
                                </flux:text>
                            </div>

                            <flux:badge size="sm" :color="$check['ok'] ? 'green' : 'amber'">
                                {{ $check['ok'] ? __('maintenance.badges.ok') : __('maintenance.badges.review') }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <flux:heading size="sm">{{ __('maintenance.sections.processing') }}</flux:heading>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.engine') }}</dt>
                            <dd>{{ __("maintenance.values.{$snapshot['processing']['engine']}") }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.chunk_size') }}</dt>
                            <dd>{{ $snapshot['processing']['chunk_size'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.max_runtime') }}</dt>
                            <dd>{{ __('maintenance.values.seconds', ['count' => $snapshot['processing']['max_runtime_seconds']]) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.retry_cooldown') }}</dt>
                            <dd>{{ __('maintenance.values.seconds', ['count' => $snapshot['processing']['retry_cooldown_seconds']]) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.resume') }}</dt>
                            <dd>{{ $snapshot['processing']['resume_after_failure'] ? __('maintenance.values.enabled') : __('maintenance.values.disabled') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.detail_limit') }}</dt>
                            <dd>{{ $snapshot['processing']['detail_limit'] }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <flux:heading size="sm">{{ __('maintenance.sections.runtime') }}</flux:heading>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.cache_store') }}</dt>
                            <dd>{{ $snapshot['runtime']['cache_store'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.session_driver') }}</dt>
                            <dd>{{ $snapshot['runtime']['session_driver'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('maintenance.fields.compiled_views') }}</dt>
                            <dd>{{ $snapshot['runtime']['compiled_views'] }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <flux:heading size="sm">{{ __('maintenance.sections.safe_controls') }}</flux:heading>
                <flux:text variant="subtle">{{ __('maintenance.messages.safe_controls') }}</flux:text>

                <div class="flex flex-wrap gap-3">
                    <flux:button
                        icon="trash"
                        wire:click="clearCompiledViews"
                        wire:confirm="{{ __('maintenance.confirmations.clear_views') }}"
                    >
                        {{ __('maintenance.actions.clear_views') }}
                    </flux:button>

                    <flux:button
                        icon="archive-box-x-mark"
                        wire:click="flushApplicationCache"
                        wire:confirm="{{ __('maintenance.confirmations.flush_cache') }}"
                    >
                        {{ __('maintenance.actions.flush_cache') }}
                    </flux:button>
                </div>
            </section>

            <section class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <flux:heading size="sm">{{ __('maintenance.sections.planned_tools') }}</flux:heading>
                <flux:text variant="subtle">{{ __('maintenance.messages.planned_tools') }}</flux:text>
            </section>
        </div>
    </x-settings.layout>
</section>
