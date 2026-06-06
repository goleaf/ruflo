<x-ui.page-container>
    <x-ui.page-header :title="__('reminders.pages.index.title')" :description="__('reminders.pages.index.description')">
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                {{ __('reminders.actions.back_to_tasks') }}
            </flux:button>

            <flux:button type="button" variant="primary" icon="arrow-path" wire:click="processDueReminders" wire:loading.attr="disabled" wire:target="processDueReminders">
                {{ __('reminders.actions.process_now') }}
            </flux:button>
        </div>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 text-sm lg:grid-cols-4">
        <x-ui.stat :label="__('reminders.summary.pending')" :value="$this->summary['pending']" tone="warning" />
        <x-ui.stat :label="__('reminders.summary.due')" :value="$this->summary['due']" tone="danger" />
        <x-ui.stat :label="__('reminders.summary.processed')" :value="$this->summary['processed']" tone="success" />
        <x-ui.stat :label="__('reminders.summary.skipped')" :value="$this->summary['skipped']" tone="muted" />
    </div>

    <flux:callout icon="clock" variant="secondary" data-test="reminder-web-mode-note">
        <flux:callout.heading>{{ __('reminders.web_mode.heading') }}</flux:callout.heading>
        <flux:callout.text>{{ __('reminders.web_mode.description') }}</flux:callout.text>
    </flux:callout>

    @if ($lastRunReport !== null)
        <flux:callout icon="check-circle" variant="secondary" data-test="reminder-run-report">
            <flux:callout.heading>{{ __('reminders.processing.report_heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('reminders.processing.report', $lastRunReport) }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[24rem_1fr]">
        <div class="space-y-4">
            <flux:card class="space-y-5" data-test="reminder-preferences">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <flux:heading size="lg">{{ __('reminders.preferences.heading') }}</flux:heading>
                        <flux:text class="mt-1 text-sm">{{ __('reminders.preferences.description') }}</flux:text>
                    </div>

                    <flux:badge :color="$remindersEnabled ? 'green' : 'zinc'" :icon="$remindersEnabled ? 'bell' : 'pause-circle'">
                        {{ $remindersEnabled ? __('reminders.preferences.enabled') : __('reminders.preferences.disabled') }}
                    </flux:badge>
                </div>

                <label class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-white/10">
                    <flux:checkbox :checked="$remindersEnabled" wire:click="toggleReminderPreference" />
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('reminders.preferences.toggle') }}</span>
                </label>
            </flux:card>

            <flux:card
                class="space-y-5"
                data-test="local-browser-notifications"
                wire:key="local-browser-notifications-{{ $this->localNotificationFingerprint() }}"
                x-data="window.RuFlo.localReminderNotifications({
                    reminders: {{ \Illuminate\Support\Js::from($this->localNotificationReminders) }},
                    labels: {{ \Illuminate\Support\Js::from($this->localNotificationLabels) }},
                })"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <flux:heading size="lg">{{ __('reminders.local.heading') }}</flux:heading>
                        <flux:text class="mt-1 text-sm">{{ __('reminders.local.description') }}</flux:text>
                    </div>

                    <span
                        class="inline-flex shrink-0 items-center rounded-md border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700 dark:border-white/10 dark:text-zinc-200"
                        x-text="statusLabel()"
                    ></span>
                </div>

                <div class="grid gap-2 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-white/10">
                        <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ __('reminders.local.permission') }}</span>
                        <span class="text-right text-zinc-500 dark:text-zinc-400" x-text="permissionLabel()"></span>
                    </div>

                    <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-white/10">
                        <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ __('reminders.local.loaded') }}</span>
                        <span class="text-right text-zinc-500 dark:text-zinc-400" x-text="pendingLabel()"></span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" variant="primary" icon="bell" x-on:click="enable" x-bind:disabled="! canEnable()" x-show="! enabled" x-cloak>
                        {{ __('reminders.local.enable') }}
                    </flux:button>

                    <flux:button type="button" variant="ghost" icon="pause-circle" x-on:click="disable" x-show="enabled" x-cloak>
                        {{ __('reminders.local.disable') }}
                    </flux:button>

                    <flux:button type="button" variant="ghost" icon="bell" x-on:click="sendTest" x-bind:disabled="! canTest()">
                        {{ __('reminders.local.test') }}
                    </flux:button>
                </div>

                <p class="text-sm text-zinc-500 dark:text-zinc-400" x-text="statusMessage"></p>
            </flux:card>

            <flux:card class="space-y-5" data-test="reminder-schedule">
                <div>
                    <flux:heading size="lg">{{ __('reminders.create.heading') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ __('reminders.create.description') }}</flux:text>
                </div>

                <form wire:submit="scheduleReminder" class="space-y-4">
                    <flux:select wire:model="todoId" :label="__('reminders.fields.task')">
                        <flux:select.option value="">{{ __('reminders.fields.choose_task') }}</flux:select.option>
                        @foreach ($this->taskOptions as $taskOption)
                            <flux:select.option value="{{ $taskOption->id }}">{{ $taskOption->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="todoId" />

                    <flux:input type="datetime-local" wire:model="remindAt" :label="__('reminders.fields.remind_at')" />
                    <flux:error name="remindAt" />

                    <flux:button type="submit" variant="primary" icon="bell" wire:loading.attr="disabled" wire:target="scheduleReminder">
                        {{ __('reminders.actions.schedule') }}
                    </flux:button>
                </form>
            </flux:card>
        </div>

        <flux:card class="space-y-5" data-test="reminder-list">
            <div>
                <flux:heading size="lg">{{ __('reminders.list.heading') }}</flux:heading>
                <flux:text class="mt-1 text-sm">{{ __('reminders.list.description') }}</flux:text>
            </div>

            <div class="space-y-2">
                @forelse ($this->reminders as $reminder)
                    <div wire:key="reminder-{{ $reminder->id }}" class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge size="sm" :color="$reminder->status->color()" :icon="$reminder->status->icon()">{{ $reminder->status->label() }}</flux:badge>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $reminder->remind_at?->isoFormat('MMM D, YYYY h:mm A') }}</span>
                            </div>

                            <div class="break-words text-sm font-medium text-zinc-950 dark:text-white">
                                {{ $reminder->todo?->title ?? __('reminders.processing.unknown_task') }}
                            </div>

                            @if ($reminder->skipped_reason)
                                <flux:text class="text-xs">{{ __('reminders.processing.skipped_reason', ['reason' => __('reminders.processing.skipped.'.$reminder->skipped_reason)]) }}</flux:text>
                            @endif
                        </div>

                        @if ($reminder->isPending() && $reminder->todo)
                            <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="clearReminder({{ $reminder->todo->id }})">
                                {{ __('reminders.actions.clear') }}
                            </flux:button>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state :title="__('reminders.empty.title')" :description="__('reminders.empty.description')" />
                @endforelse
            </div>
        </flux:card>
    </div>
</x-ui.page-container>
