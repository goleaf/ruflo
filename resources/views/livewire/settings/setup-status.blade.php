<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('setup.pages.status.title') }}</flux:heading>

    <x-settings.layout :heading="__('setup.pages.status.heading')" :subheading="__('setup.pages.status.subheading')">
        <div class="space-y-6" data-test="setup-status">
            <flux:callout
                :icon="$status['ready'] ? 'check-circle' : 'exclamation-triangle'"
                variant="secondary"
            >
                <flux:callout.heading>
                    {{ $status['ready'] ? __('setup.messages.ready') : __('setup.messages.needs_attention') }}
                </flux:callout.heading>

                <flux:callout.text>
                    {{ __('setup.messages.status_only') }}
                </flux:callout.text>
            </flux:callout>

            <div class="space-y-3">
                @foreach ($status['checks'] as $check)
                    <div wire:key="setup-check-{{ $check['key'] }}" class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 p-3 dark:border-white/10">
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
                            {{ $check['ok'] ? __('setup.badges.ok') : __('setup.badges.review') }}
                        </flux:badge>
                    </div>
                @endforeach
            </div>

            @if ($status['database_error'])
                <flux:callout icon="x-circle" variant="danger">
                    <flux:callout.heading>{{ __('setup.messages.database_error') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('setup.messages.database_error_detail') }}</flux:callout.text>
                </flux:callout>
            @endif

            @if ($status['pending_migrations'] !== [])
                <div class="space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-400/20 dark:bg-amber-400/10">
                    <flux:heading size="sm">{{ __('setup.pending.heading') }}</flux:heading>
                    <flux:text variant="subtle">{{ __('setup.pending.description') }}</flux:text>

                    <ul class="space-y-1 text-sm text-amber-900 dark:text-amber-100">
                        @foreach ($status['pending_migrations'] as $migration)
                            <li wire:key="pending-migration-{{ $migration }}" class="break-all">{{ $migration }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button wire:click="refreshStatus" icon="arrow-path">
                    {{ __('setup.actions.refresh') }}
                </flux:button>
            </div>
        </div>
    </x-settings.layout>
</section>
