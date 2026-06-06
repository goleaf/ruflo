<section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <x-ui.page-header :title="$this->project->name" :description="__('todos.projects.show.description')">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge size="sm" :color="$this->isSharedProject ? 'blue' : 'zinc'" icon="user-group">
                {{ $this->isSharedProject ? __('todos.collaboration.scope.shared') : __('todos.collaboration.scope.private') }}
            </flux:badge>

            <flux:badge size="sm" :color="$this->accessRole->color()">
                {{ $this->accessRole->label() }}
            </flux:badge>

            <flux:badge size="sm" :color="$this->project->isArchived() ? 'zinc' : $this->project->color">
                {{ $this->project->isArchived() ? __('todos.status.archived') : __('todos.status.active') }}
            </flux:badge>

            @if ($this->canUseTaskFilter && ! $this->project->isArchived())
                <flux:button :href="route('todos.index', ['project' => $this->project->id])" wire:navigate variant="subtle" icon="funnel">
                    {{ __('todos.projects.actions.filter_tasks') }}
                </flux:button>
            @endif

            <flux:button :href="route('todos.index')" wire:navigate variant="ghost" icon="arrow-left">
                {{ __('todos.actions.back_to_list') }}
            </flux:button>
        </div>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
        <x-ui.stat :label="__('todos.summary.active')" :value="$this->summary['active']" />
        <x-ui.stat :label="__('todos.summary.completed')" :value="$this->summary['completed']" tone="success" />
        <x-ui.stat :label="__('todos.summary.archived')" :value="$this->summary['archived']" tone="muted" />
        <x-ui.stat :label="__('todos.summary.trash')" :value="$this->summary['trash']" tone="danger" />
    </div>

    <flux:card class="space-y-4" data-test="project-members">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <flux:subheading>{{ __('todos.collaboration.members.label') }}</flux:subheading>
                <flux:heading size="lg">{{ __('todos.collaboration.members.heading') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('todos.collaboration.members.description') }}
                </flux:text>
            </div>

            <flux:badge size="sm" :color="$this->isSharedProject ? 'blue' : 'zinc'">
                {{ trans_choice('todos.collaboration.members.count', $this->memberships->count() + 1, ['count' => $this->memberships->count() + 1]) }}
            </flux:badge>
        </div>

        <div class="divide-y divide-zinc-200 overflow-hidden rounded-lg border border-zinc-200 dark:divide-white/10 dark:border-white/10">
            <div class="flex flex-col gap-2 bg-zinc-50 px-3 py-3 sm:flex-row sm:items-center sm:justify-between dark:bg-zinc-900" data-test="project-member-owner">
                <div class="min-w-0">
                    <flux:text class="font-medium text-zinc-950 dark:text-white">{{ $this->project->user->name }}</flux:text>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->project->user->email }}</flux:text>
                </div>

                <flux:badge size="sm" color="blue">{{ \App\Enums\ProjectRole::Owner->label() }}</flux:badge>
            </div>

            @forelse ($this->memberships as $membership)
                <div
                    wire:key="project-member-{{ $membership->id }}"
                    class="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
                    data-test="project-member-{{ $membership->id }}"
                >
                    <div class="min-w-0">
                        <flux:text class="font-medium text-zinc-950 dark:text-white">{{ $membership->user->name }}</flux:text>
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $membership->user->email }}</flux:text>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <flux:badge size="sm" :color="$membership->role->color()">{{ $membership->role->label() }}</flux:badge>

                        @if ($this->canManageMembers)
                            <div class="flex flex-wrap gap-2">
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="subtle"
                                    icon="pencil-square"
                                    wire:click="prepareMemberRoleEdit({{ $membership->id }})"
                                    data-test="project-member-edit-role-{{ $membership->id }}"
                                >
                                    {{ __('todos.collaboration.members.actions.edit_role') }}
                                </flux:button>

                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    icon="user-minus"
                                    wire:click="removeMember({{ $membership->id }})"
                                    wire:confirm="{{ __('todos.collaboration.members.confirm_remove') }}"
                                    wire:loading.attr="disabled"
                                    wire:target="removeMember({{ $membership->id }})"
                                    data-test="project-member-remove-{{ $membership->id }}"
                                >
                                    {{ __('todos.collaboration.members.actions.remove') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-3 py-3">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('todos.collaboration.members.empty') }}
                    </flux:text>
                </div>
            @endforelse
        </div>
    </flux:card>

    @if ($this->canManageMembers)
        <flux:modal name="project-member-role-edit" class="md:w-[26rem]">
            <form wire:submit="updateMemberRole" class="space-y-5" data-test="project-member-role-form">
                <div>
                    <flux:heading size="lg">{{ __('todos.collaboration.members.role_modal.heading') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('todos.collaboration.members.role_modal.description') }}</flux:text>
                </div>

                <flux:select wire:model="memberRole" :label="__('todos.collaboration.members.fields.role')">
                    @foreach ($this->inviteRoleOptions as $option)
                        <flux:select.option :value="$option['value']">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="memberRole" />

                <flux:callout icon="shield-check" variant="secondary">
                    <flux:callout.text>{{ __('todos.collaboration.members.role_modal.warning') }}</flux:callout.text>
                </flux:callout>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('todos.actions.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary" icon="shield-check" wire:loading.attr="disabled" wire:target="updateMemberRole">
                        {{ __('todos.collaboration.members.actions.save_role') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:card class="space-y-4" data-test="project-invites">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <flux:subheading>{{ __('todos.collaboration.invites.label') }}</flux:subheading>
                    <flux:heading size="lg">{{ __('todos.collaboration.invites.heading') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('todos.collaboration.invites.description') }}
                    </flux:text>
                </div>

                <flux:modal.trigger name="project-invite-create">
                    <flux:button variant="primary" icon="link" class="w-full sm:w-auto">
                        {{ __('todos.collaboration.invites.actions.create') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>

            <flux:callout icon="exclamation-triangle" variant="secondary" data-test="project-invite-link-only-warning">
                <flux:callout.heading>{{ __('todos.collaboration.invites.warning.heading') }}</flux:callout.heading>
                <flux:callout.text>{{ __('todos.collaboration.invites.warning.description') }}</flux:callout.text>
            </flux:callout>

            <div class="space-y-3">
                @forelse ($this->invitations as $invitation)
                    <div
                        wire:key="project-invite-{{ $invitation->id }}"
                        class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900"
                        data-test="project-invite-{{ $invitation->status()->value }}"
                    >
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <flux:badge size="sm" :color="$invitation->status()->color()">
                                        {{ $invitation->status()->label() }}
                                    </flux:badge>

                                    <flux:badge size="sm" :color="$invitation->role->color()">
                                        {{ $invitation->role->label() }}
                                    </flux:badge>
                                </div>

                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('todos.collaboration.invites.meta.expires_at', ['date' => $invitation->expires_at->isoFormat('MMM D, YYYY')]) }}
                                </flux:text>

                                @if ($invitation->acceptedBy)
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('todos.collaboration.invites.meta.accepted_by', ['name' => $invitation->acceptedBy->name]) }}
                                    </flux:text>
                                @endif
                            </div>

                            @if ($invitation->isPending())
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    icon="x-mark"
                                    wire:click="cancelInvitation({{ $invitation->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="cancelInvitation({{ $invitation->id }})"
                                    class="w-full sm:w-auto"
                                >
                                    {{ __('todos.collaboration.invites.actions.cancel') }}
                                </flux:button>
                            @endif
                        </div>

                        @if ($invitation->isPending())
                            <div
                                x-data="{ copied: false }"
                                class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"
                                data-test="project-invite-copy"
                            >
                                <flux:input
                                    x-ref="inviteLink"
                                    readonly
                                    :label="__('todos.collaboration.invites.fields.link')"
                                    :value="$invitation->shareUrl()"
                                />

                                <flux:button
                                    type="button"
                                    variant="subtle"
                                    icon="clipboard"
                                    x-on:click="navigator.clipboard.writeText($refs.inviteLink.value).then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                >
                                    <span x-show="! copied">{{ __('todos.collaboration.invites.actions.copy') }}</span>
                                    <span x-show="copied" x-cloak>{{ __('todos.collaboration.invites.actions.copied') }}</span>
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state
                        :title="__('todos.collaboration.invites.empty.title')"
                        :description="__('todos.collaboration.invites.empty.description')"
                    />
                @endforelse
            </div>
        </flux:card>

        <flux:modal name="project-invite-create" class="md:w-[28rem]">
            <form wire:submit="createInvitation" class="space-y-5" data-test="project-invite-form">
                <div>
                    <flux:heading size="lg">{{ __('todos.collaboration.invites.create.heading') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('todos.collaboration.invites.create.description') }}</flux:text>
                </div>

                <flux:select wire:model="inviteRole" :label="__('todos.collaboration.invites.fields.role')">
                    @foreach ($this->inviteRoleOptions as $option)
                        <flux:select.option :value="$option['value']">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="inviteRole" />

                <flux:select wire:model="inviteExpiresInDays" :label="__('todos.collaboration.invites.fields.expires_in_days')">
                    <flux:select.option value="1">{{ __('todos.collaboration.invites.expiration_options.one_day') }}</flux:select.option>
                    <flux:select.option value="7">{{ __('todos.collaboration.invites.expiration_options.seven_days') }}</flux:select.option>
                    <flux:select.option value="14">{{ __('todos.collaboration.invites.expiration_options.fourteen_days') }}</flux:select.option>
                    <flux:select.option value="30">{{ __('todos.collaboration.invites.expiration_options.thirty_days') }}</flux:select.option>
                </flux:select>
                <flux:error name="inviteExpiresInDays" />

                <flux:callout icon="link" variant="secondary">
                    <flux:callout.text>{{ __('todos.collaboration.invites.create.warning') }}</flux:callout.text>
                </flux:callout>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('todos.actions.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary" icon="link" wire:loading.attr="disabled" wire:target="createInvitation">
                        {{ __('todos.collaboration.invites.actions.create') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">{{ __('todos.projects.show.tasks_heading') }}</flux:heading>
            <flux:text>{{ __('todos.projects.show.task_count', ['count' => $this->todos->total()]) }}</flux:text>
        </div>

        <div class="space-y-2">
            @forelse ($this->todos as $todo)
                <div
                    wire:key="project-task-{{ $todo->id }}"
                    class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-ui.status-badge :status="$todo->status()" />

                            @if ($todo->priority->value !== 'normal')
                                <flux:badge size="sm" :color="$todo->priority->color()">{{ $todo->priority->label() }}</flux:badge>
                            @endif

                            @if ($todo->due_date)
                                <flux:badge size="sm" :color="$todo->isOverdue() ? 'red' : ($todo->isDueToday() ? 'amber' : 'zinc')" icon="calendar">
                                    {{ $todo->due_date->isoFormat('MMM D') }}
                                </flux:badge>
                            @endif
                        </div>

                        <a href="{{ route('todos.show', $todo) }}" wire:navigate class="block text-sm font-medium break-words text-zinc-950 hover:underline dark:text-white">
                            {{ $todo->title }}
                        </a>

                        @if ($todo->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($todo->tags as $tagBadge)
                                    <a href="{{ route('todos.index', ['tag' => $tagBadge->id]) }}" wire:navigate>
                                        <flux:badge wire:key="project-task-{{ $todo->id }}-tag-{{ $tagBadge->id }}" size="sm" :color="$tagBadge->color" variant="outline">#{{ $tagBadge->name }}</flux:badge>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    :title="__('todos.empty.project_detail.title')"
                    :description="__('todos.empty.project_detail.description')"
                />
            @endforelse
        </div>

        @if ($this->todos->hasPages())
            <div>{{ $this->todos->links() }}</div>
        @endif
    </flux:card>
</section>
