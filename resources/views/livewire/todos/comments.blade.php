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
                wire:model="body"
                rows="4"
                maxlength="2000"
                :placeholder="__('todos.comments.fields.body_placeholder')"
                :disabled="! $this->canComment()"
            />
            <flux:error name="body" />
        </flux:field>

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
                                <flux:textarea wire:model="editingBody" rows="4" maxlength="2000" />
                                <flux:error name="editingBody" />
                            </flux:field>

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
