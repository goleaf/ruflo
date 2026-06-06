<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.page-header :title="__('automation.pages.index.title')" :description="__('automation.pages.index.description')">
        <div class="grid grid-cols-2 gap-3 text-sm sm:min-w-[24rem] sm:grid-cols-3">
            <x-ui.stat :label="__('automation.summary.rules')" :value="$this->rules->count()" />
            <x-ui.stat :label="__('automation.summary.enabled')" :value="$this->rules->where('is_enabled', true)->count()" tone="success" />
            <x-ui.stat :label="__('automation.summary.disabled')" :value="$this->rules->where('is_enabled', false)->count()" tone="muted" />
        </div>
    </x-ui.page-header>

    @if ($lastRunReport)
        <flux:card class="space-y-3 border-green-200 bg-green-50 dark:border-green-400/20 dark:bg-green-400/10">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <flux:subheading>{{ __('automation.run_report.label') }}</flux:subheading>
                    <flux:heading size="lg">{{ __('automation.run_report.heading', ['rule' => $lastRunReport['rule']]) }}</flux:heading>
                    <flux:text>{{ $lastRunReport['message'] }}</flux:text>
                </div>

                <flux:badge color="green" icon="check-circle">{{ $lastRunReport['status'] }}</flux:badge>
            </div>

            <div class="grid gap-2 text-sm sm:grid-cols-4">
                <x-ui.stat :label="__('automation.run_report.matched')" :value="$lastRunReport['matched']" />
                <x-ui.stat :label="__('automation.run_report.changed')" :value="$lastRunReport['changed']" tone="success" />
                <x-ui.stat :label="__('automation.run_report.remaining')" :value="$lastRunReport['skipped']" tone="muted" />
                <x-ui.stat :label="__('automation.run_report.mode')" :value="$lastRunReport['dry_run'] ? __('automation.run_report.dry_run') : __('automation.run_report.live_run')" />
            </div>
        </flux:card>
    @endif

    <flux:card class="space-y-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-1">
                <flux:subheading>{{ __('automation.create.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('automation.create.heading') }}</flux:heading>
                <flux:text>{{ __('automation.create.description') }}</flux:text>
            </div>

            <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                {{ __('todos.actions.back_to_list') }}
            </flux:button>
        </div>

        <form wire:submit="createRule" class="grid gap-3 lg:grid-cols-[1fr_1fr_auto] lg:items-start">
            <div>
                <flux:input
                    wire:model="name"
                    :label="__('automation.fields.name')"
                    :placeholder="__('automation.fields.name_placeholder')"
                    maxlength="80"
                    autocomplete="off"
                />
                <flux:error name="name" />
            </div>

            <div>
                <flux:select wire:model.live="kind" :label="__('automation.fields.kind')">
                    @foreach ($this->kindOptions() as $kindOption)
                        <flux:select.option value="{{ $kindOption->value }}">{{ $kindOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="kind" />
            </div>

            <flux:button type="submit" variant="primary" icon="plus" class="lg:mt-6">
                {{ __('automation.actions.create') }}
            </flux:button>
        </form>

        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-start gap-3">
                <flux:icon :name="$this->kindIcon($kind)" class="mt-0.5 size-4 shrink-0 text-zinc-500 dark:text-zinc-400" />
                <div class="min-w-0 space-y-1">
                    <flux:text class="font-medium">{{ $this->kindLabel($kind) }}</flux:text>
                    <flux:text class="text-sm">{{ $this->kindDescription($kind) }}</flux:text>
                </div>
            </div>
        </div>
    </flux:card>

    <flux:card class="space-y-5">
        <div class="space-y-1">
            <flux:subheading>{{ __('automation.rules.label') }}</flux:subheading>
            <flux:heading size="lg">{{ __('automation.rules.heading') }}</flux:heading>
            <flux:text>{{ __('automation.rules.description') }}</flux:text>
        </div>

        <div class="space-y-3">
            @forelse ($this->rules as $rule)
                <div wire:key="automation-rule-{{ $rule->id }}" class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white px-3 py-3 dark:border-white/10 dark:bg-white/5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge size="sm" :color="$rule->is_enabled ? 'green' : 'zinc'" :icon="$rule->is_enabled ? 'play-circle' : 'pause-circle'">
                                    {{ $rule->is_enabled ? __('automation.rules.enabled') : __('automation.rules.disabled') }}
                                </flux:badge>

                                <flux:badge size="sm" :color="$rule->kind->color()" :icon="$rule->kind->icon()">
                                    {{ $rule->kind->label() }}
                                </flux:badge>

                                <flux:badge size="sm" :color="$this->statusColor($rule->last_status)" :icon="$this->statusIcon($rule->last_status)">
                                    {{ $this->statusLabel($rule->last_status) }}
                                </flux:badge>
                            </div>

                            <div class="space-y-1">
                                <flux:heading size="lg">{{ $rule->name }}</flux:heading>
                                <flux:text>{{ $rule->kind->description() }}</flux:text>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                role="switch"
                                aria-checked="{{ $rule->is_enabled ? 'true' : 'false' }}"
                                wire:click="toggleRule({{ $rule->id }})"
                                @class([
                                    'inline-flex h-9 min-w-24 items-center justify-center gap-2 rounded-lg border px-3 text-sm font-medium transition',
                                    'border-green-200 bg-green-50 text-green-800 dark:border-green-400/20 dark:bg-green-400/10 dark:text-green-100' => $rule->is_enabled,
                                    'border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200' => ! $rule->is_enabled,
                                ])
                            >
                                <flux:icon :name="$rule->is_enabled ? 'check-circle' : 'pause-circle'" class="size-4" />
                                {{ $rule->is_enabled ? __('automation.actions.disable') : __('automation.actions.enable') }}
                            </button>

                            <flux:button size="sm" variant="subtle" icon="beaker" wire:click="testRule({{ $rule->id }})" wire:loading.attr="disabled">
                                {{ __('automation.actions.test') }}
                            </flux:button>

                            <flux:button size="sm" variant="primary" icon="play" wire:click="runRule({{ $rule->id }})" wire:loading.attr="disabled">
                                {{ __('automation.actions.run') }}
                            </flux:button>
                        </div>
                    </div>

                    <div class="grid gap-2 text-sm sm:grid-cols-4">
                        <x-ui.stat :label="__('automation.rules.last_run')" :value="$rule->last_run_at?->diffForHumans() ?? __('automation.rules.never_run')" />
                        <x-ui.stat :label="__('automation.rules.runs')" :value="$rule->runs_count" />
                        <x-ui.stat :label="__('automation.rules.latest_matched')" :value="$rule->latestRun?->matched_count ?? 0" />
                        <x-ui.stat :label="__('automation.rules.latest_changed')" :value="$rule->latestRun?->changed_count ?? 0" tone="success" />
                    </div>

                    @if ($rule->latestRun)
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-white/10 dark:bg-zinc-900">
                            <flux:text class="text-sm">
                                {{ __('automation.rules.latest_summary', [
                                    'matched' => $rule->latestRun->matched_count,
                                    'changed' => $rule->latestRun->changed_count,
                                    'remaining' => $rule->latestRun->skipped_count,
                                ]) }}
                            </flux:text>

                            @if (! empty($rule->latestRun->details['tasks']))
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($rule->latestRun->details['tasks'] as $task)
                                        <flux:badge wire:key="automation-run-task-{{ $rule->id }}-{{ $task['id'] }}" size="sm" color="zinc" icon="check-circle">
                                            {{ $task['title'] }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('automation.empty.title')"
                    :description="__('automation.empty.description')"
                />
            @endforelse
        </div>
    </flux:card>
</section>
