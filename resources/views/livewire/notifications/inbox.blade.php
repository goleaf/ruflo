<x-ui.page-container>
    <x-ui.page-header :title="__('notifications.pages.inbox.title')" :description="__('notifications.pages.inbox.description')">
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('dashboard')" wire:navigate variant="ghost" icon="arrow-left">
                {{ __('notifications.actions.back_to_dashboard') }}
            </flux:button>

            @if ($this->summary['unread'] > 0)
                <flux:button type="button" variant="primary" icon="check-circle" wire:click="markAllRead">
                    {{ __('notifications.actions.mark_all_read') }}
                </flux:button>
            @endif
        </div>
    </x-ui.page-header>

    <div class="grid grid-cols-3 gap-3 text-sm">
        <x-ui.stat :label="__('notifications.summary.all')" :value="$this->summary['all']" tone="muted" />
        <x-ui.stat :label="__('notifications.summary.unread')" :value="$this->summary['unread']" tone="warning" />
        <x-ui.stat :label="__('notifications.summary.read')" :value="$this->summary['read']" tone="success" />
    </div>

    <flux:card class="space-y-4" data-test="notification-center">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="lg">{{ __('notifications.list.heading') }}</flux:heading>
                <flux:text class="mt-1 text-sm">{{ __('notifications.list.description') }}</flux:text>
            </div>

            <flux:badge icon="funnel" color="zinc">{{ $this->filterLabel() }}</flux:badge>
        </div>

        <div role="tablist" aria-label="{{ __('notifications.filters.label') }}" class="flex w-full gap-1 overflow-x-auto rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-900">
            <button type="button" role="tab" aria-selected="{{ $filter === 'all' ? 'true' : 'false' }}" wire:click="showAll" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium transition {{ $filter === 'all' ? 'bg-white text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white' : 'text-zinc-600 hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white' }}">
                <span>{{ __('notifications.filters.all') }}</span>
                <flux:badge size="sm" color="zinc">{{ $this->summary['all'] }}</flux:badge>
            </button>

            <button type="button" role="tab" aria-selected="{{ $filter === 'unread' ? 'true' : 'false' }}" wire:click="showUnread" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium transition {{ $filter === 'unread' ? 'bg-white text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white' : 'text-zinc-600 hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white' }}">
                <span>{{ __('notifications.filters.unread') }}</span>
                <flux:badge size="sm" color="amber">{{ $this->summary['unread'] }}</flux:badge>
            </button>

            <button type="button" role="tab" aria-selected="{{ $filter === 'read' ? 'true' : 'false' }}" wire:click="showRead" class="inline-flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium transition {{ $filter === 'read' ? 'bg-white text-zinc-950 shadow-sm dark:bg-white/10 dark:text-white' : 'text-zinc-600 hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white' }}">
                <span>{{ __('notifications.filters.read') }}</span>
                <flux:badge size="sm" color="green">{{ $this->summary['read'] }}</flux:badge>
            </button>
        </div>

        <div class="space-y-3" data-test="notification-list">
            @forelse ($this->notifications as $notification)
                <article wire:key="notification-{{ $notification->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-zinc-950">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge size="sm" :color="$notification->read_at === null ? 'amber' : 'zinc'" :icon="$notification->read_at === null ? 'bell' : 'check-circle'">
                                    {{ $notification->read_at === null ? __('notifications.status.unread') : __('notifications.status.read') }}
                                </flux:badge>

                                <flux:badge size="sm" color="blue" icon="tag">{{ $this->notificationKind($notification) }}</flux:badge>

                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $notification->created_at?->diffForHumans() }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <h2 class="break-words text-base font-semibold text-zinc-950 dark:text-white">
                                    {{ $this->notificationTitle($notification) }}
                                </h2>

                                <p class="break-words text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $this->notificationMessage($notification) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            @if ($actionUrl = $this->actionUrl($notification))
                                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="$actionUrl" wire:navigate>
                                    {{ __('notifications.actions.open') }}
                                </flux:button>
                            @endif

                            @if ($notification->read_at === null)
                                <flux:button type="button" size="sm" variant="ghost" icon="check" wire:click="markRead('{{ $notification->id }}')">
                                    {{ __('notifications.actions.mark_read') }}
                                </flux:button>
                            @else
                                <flux:button type="button" size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="markUnread('{{ $notification->id }}')">
                                    {{ __('notifications.actions.mark_unread') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <x-ui.empty-state :title="__('notifications.empty.title')" :description="__('notifications.empty.description')" />
            @endforelse
        </div>

        @if ($this->notifications->hasPages())
            <flux:pagination :paginator="$this->notifications" />
        @endif
    </flux:card>
</x-ui.page-container>
