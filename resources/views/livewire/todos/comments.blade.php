<flux:card class="space-y-5" data-test="task-comments">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <flux:subheading>{{ __('todos.comments.label') }}</flux:subheading>
            <flux:heading size="lg">{{ __('todos.comments.heading') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('todos.comments.description') }}
            </flux:text>
        </div>

        <flux:badge size="sm" color="zinc" icon="chat-bubble-left-right">
            {{ trans_choice('todos.comments.count', $this->totalComments, ['count' => $this->totalComments]) }}
        </flux:badge>
    </div>

    @if (! $this->canComment())
        <flux:callout icon="lock-closed" variant="secondary" data-test="comments-read-only">
            <flux:callout.heading>{{ __('todos.comments.locked.heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('todos.comments.locked.description') }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="create" class="space-y-3" data-test="comment-create-form">
        <flux:field>
            <flux:label>{{ __('todos.comments.fields.body') }}</flux:label>
            <flux:textarea
                wire:model.live.debounce.300ms="body"
                rows="4"
                maxlength="2000"
                :placeholder="__('todos.comments.fields.body_placeholder')"
                :disabled="! $this->canComment()"
            />
            <flux:error name="body" />
        </flux:field>

        @if ($this->canComment())
            <div
                class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-white/5"
                data-test="comment-mention-picker"
            >
                <flux:field>
                    <flux:label>{{ __('todos.comments.mentions.fields.search') }}</flux:label>
                    <flux:input
                        wire:model.live.debounce.250ms="mentionSearch"
                        type="search"
                        icon="at-symbol"
                        :placeholder="__('todos.comments.mentions.fields.search_placeholder')"
                    />
                    <flux:description>{{ __('todos.comments.mentions.description') }}</flux:description>
                </flux:field>

                @if ($this->selectedMentions->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2" data-test="comment-selected-mentions">
                        @foreach ($this->selectedMentions as $mention)
                            <flux:button
                                wire:key="comment-selected-mention-{{ $mention['id'] }}"
                                type="button"
                                size="xs"
                                variant="ghost"
                                icon="x-mark"
                                wire:click="removeMention({{ $mention['id'] }})"
                            >
                                {{ $mention['token'] }}
                            </flux:button>
                        @endforeach
                    </div>
                @endif

                @if ($this->mentionCandidates->isNotEmpty())
                    <div class="mt-3 grid gap-2 sm:grid-cols-2" data-test="comment-mention-candidates" role="listbox" aria-label="{{ __('todos.comments.mentions.suggestions.label') }}">
                        @foreach ($this->mentionCandidates as $candidate)
                            <flux:button
                                wire:key="comment-mention-candidate-{{ $candidate['id'] }}"
                                type="button"
                                size="sm"
                                variant="ghost"
                                icon="at-symbol"
                                wire:click="addMention({{ $candidate['id'] }})"
                                aria-label="{{ __('todos.comments.mentions.actions.insert', ['name' => $candidate['name']]) }}"
                                class="justify-start"
                            >
                                <span class="flex min-w-0 flex-col items-start">
                                    <span class="truncate">{{ $candidate['name'] }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $candidate['token'] }} · {{ $candidate['role'] }}</span>
                                </span>
                            </flux:button>
                        @endforeach
                    </div>
                @elseif (filled($this->mentionSearch))
                    <flux:text class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('todos.comments.mentions.empty') }}
                    </flux:text>
                @endif
            </div>
        @endif

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('todos.comments.plain_text_notice') }}
            </flux:text>

            <flux:button
                type="submit"
                variant="primary"
                icon="paper-airplane"
                wire:loading.attr="disabled"
                :disabled="! $this->canComment()"
            >
                {{ __('todos.comments.actions.post') }}
            </flux:button>
        </div>
    </form>

    <flux:separator />

    @if ($this->comments->isEmpty())
        <x-ui.empty-state
            icon="chat-bubble-left-right"
            :title="__('todos.comments.empty.title')"
            :description="__('todos.comments.empty.description')"
        />
    @else
        <div class="space-y-4" data-test="comment-list">
            @foreach ($this->comments as $comment)
                <article
                    wire:key="todo-comment-{{ $comment->id }}"
                    class="rounded-lg border border-zinc-200 p-4 dark:border-white/10"
                    data-test="todo-comment-{{ $comment->id }}"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:heading size="sm">{{ $this->authorName($comment) }}</flux:heading>

                                @if ($comment->trashed())
                                    <flux:badge size="sm" color="zinc" icon="trash">{{ __('todos.comments.status.deleted') }}</flux:badge>
                                @elseif ($comment->edited_at)
                                    <flux:badge size="sm" color="amber" icon="pencil-square">{{ __('todos.comments.status.edited') }}</flux:badge>
                                @endif
                            </div>

                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('todos.comments.meta.posted_at', ['time' => $comment->created_at->diffForHumans()]) }}
                            </flux:text>
                        </div>

                        @if (! $comment->trashed() && ($this->canUpdate($comment) || $this->canDelete($comment)))
                            <div class="flex flex-wrap gap-1 sm:justify-end">
                                @if ($this->canUpdate($comment))
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        icon="pencil-square"
                                        wire:click="startEditing({{ $comment->id }})"
                                    >
                                        {{ __('todos.comments.actions.edit') }}
                                    </flux:button>
                                @endif

                                @if ($this->canDelete($comment))
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="delete({{ $comment->id }})"
                                        wire:confirm="{{ __('todos.comments.confirmations.delete') }}"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ __('todos.comments.actions.delete') }}
                                    </flux:button>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($this->editingCommentId === $comment->id)
                        <form wire:submit="update" class="mt-4 space-y-3">
                            <flux:field>
                                <flux:label>{{ __('todos.comments.fields.body') }}</flux:label>
                                <flux:textarea wire:model.live.debounce.300ms="editingBody" rows="4" maxlength="2000" />
                                <flux:error name="editingBody" />
                            </flux:field>

                            @if ($this->editingCommentId !== null)
                                <div
                                    class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-white/5"
                                    data-test="comment-edit-mention-picker"
                                >
                                    <flux:field>
                                        <flux:label>{{ __('todos.comments.mentions.fields.search') }}</flux:label>
                                        <flux:input
                                            wire:model.live.debounce.250ms="editingMentionSearch"
                                            type="search"
                                            icon="at-symbol"
                                            :placeholder="__('todos.comments.mentions.fields.search_placeholder')"
                                        />
                                        <flux:description>{{ __('todos.comments.mentions.description') }}</flux:description>
                                    </flux:field>

                                    @if ($this->editingSelectedMentions->isNotEmpty())
                                        <div class="mt-3 flex flex-wrap gap-2" data-test="comment-edit-selected-mentions">
                                            @foreach ($this->editingSelectedMentions as $mention)
                                                <flux:button
                                                    wire:key="comment-edit-selected-mention-{{ $mention['id'] }}"
                                                    type="button"
                                                    size="xs"
                                                    variant="ghost"
                                                    icon="x-mark"
                                                    wire:click="removeEditingMention({{ $mention['id'] }})"
                                                >
                                                    {{ $mention['token'] }}
                                                </flux:button>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($this->editingMentionCandidates->isNotEmpty())
                                        <div class="mt-3 grid gap-2 sm:grid-cols-2" data-test="comment-edit-mention-candidates" role="listbox" aria-label="{{ __('todos.comments.mentions.suggestions.label') }}">
                                            @foreach ($this->editingMentionCandidates as $candidate)
                                                <flux:button
                                                    wire:key="comment-edit-mention-candidate-{{ $candidate['id'] }}"
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="at-symbol"
                                                    wire:click="addEditingMention({{ $candidate['id'] }})"
                                                    aria-label="{{ __('todos.comments.mentions.actions.insert', ['name' => $candidate['name']]) }}"
                                                    class="justify-start"
                                                >
                                                    <span class="flex min-w-0 flex-col items-start">
                                                        <span class="truncate">{{ $candidate['name'] }}</span>
                                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $candidate['token'] }} · {{ $candidate['role'] }}</span>
                                                    </span>
                                                </flux:button>
                                            @endforeach
                                        </div>
                                    @elseif (filled($this->editingMentionSearch))
                                        <flux:text class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('todos.comments.mentions.empty') }}
                                        </flux:text>
                                    @endif
                                </div>
                            @endif

                            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                <flux:button type="button" variant="ghost" icon="x-mark" wire:click="cancelEditing">
                                    {{ __('todos.comments.actions.cancel') }}
                                </flux:button>

                                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                                    {{ __('todos.comments.actions.save') }}
                                </flux:button>
                            </div>
                        </form>
                    @elseif ($comment->trashed())
                        <flux:text class="mt-4 text-sm italic text-zinc-500 dark:text-zinc-400">
                            {{ __('todos.comments.deleted_body') }}
                        </flux:text>
                    @else
                        <div class="mt-4 whitespace-pre-line break-words text-sm text-zinc-700 dark:text-zinc-200" data-test="comment-body-{{ $comment->id }}">{{ $comment->body }}</div>

                        @if ($comment->mentions->isNotEmpty())
                            <div
                                class="mt-3 flex flex-wrap gap-2"
                                data-test="comment-mentions-{{ $comment->id }}"
                                aria-label="{{ trans_choice('todos.comments.mentions.count', $comment->mentions->count(), ['count' => $comment->mentions->count()]) }}"
                            >
                                @foreach ($comment->mentions as $mention)
                                    <flux:badge wire:key="todo-comment-mention-{{ $mention->id }}" size="sm" color="blue" icon="at-symbol">
                                        {{ $this->mentionHandle($mention) }} · {{ $this->mentionName($mention) }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </article>
            @endforeach
        </div>

        @if ($this->hasMore)
            <div class="flex justify-center">
                <flux:button
                    type="button"
                    variant="ghost"
                    icon="arrow-down"
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    data-test="comments-load-more"
                >
                    {{ __('todos.comments.actions.load_more') }}
                </flux:button>
            </div>
        @endif
    @endif
</flux:card>
