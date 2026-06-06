# Task Comments

Step 072 added plain-text task comment threads to the protected task detail page.
Step 073 adds safe mentions for users who already have access to the parent
task.

## Runtime Contract

- Comments are stored in `todo_comments` and rendered by the class-based
  `App\Livewire\Todos\Comments` component.
- Reads flow through `App\Queries\Todos\TodoCommentListQuery` after the parent
  task is resolved through `TodoListQuery` and authorized for `view`.
- `todo_comments.user_id` is always the parent task owner. `author_id` stores
  the user who wrote the comment, so shared project editors can comment without
  changing workspace ownership.
- Comment create, update, and delete writes delegate to `CreateTodoComment`,
  `UpdateTodoComment`, and `DeleteTodoComment`.
- Comments are plain text. Blade renders comment bodies with escaped output, and
  deleted comments show a translated placeholder instead of the deleted body.
- Mentions are stored in `todo_comment_mentions`, keyed to the comment and the
  mentioned user. The stored `handle` records the resolved plain-text `@handle`
  token used in the comment body.

## Access

- Task owners, project managers, and project editors can post comments when they
  can use the parent task `comment` ability.
- Project viewers can read comments on visible shared tasks but cannot post.
- Only the original comment author can edit or delete an active comment.
- Removed project members lose access because every comment read and mutation
  rechecks parent task visibility.
- Mention candidates are limited to the task owner and active project members
  who already have task access. The actor is excluded from suggestions and
  notification fanout.

## Validation

- `StoreTodoCommentRequest` and `UpdateTodoCommentRequest` are the canonical rule
  sources for comment body rules and translated attributes.
- `TodoCommentBody` rejects empty visible text and bodies over 2000 characters.
- `TodoCommentMentionTargets` rejects tampered selected mention IDs that do not
  belong to the allowed candidate set for the actor and parent task.
- `TodoCommentData` normalizes line endings, strips null bytes, and trims the
  persisted body.

## Mentions

- The comment composer uses a Flux search picker backed by
  `TodoMentionCandidateQuery`. It never displays unrelated users or email
  addresses.
- Clicking an allowed candidate inserts an escaped plain-text `@handle` and
  stores the candidate ID in Livewire state.
- `CreateTodoComment` and `UpdateTodoComment` revalidate selected IDs through
  dedicated request helpers and `TodoCommentMentionTargets` before
  `SyncTodoCommentMentions` persists rows.
- Existing comment mentions are shown as Flux badges beneath the escaped
  comment body. Deleted comments keep the deleted placeholder and do not show
  mention badges.

## Notifications And Activity

- Comment creation dispatches `TodoCommentCreated`; edits and deletes dispatch
  matching update/delete events.
- `RecordTodoActivity` writes synchronous parent-task activity records with a
  bounded comment excerpt.
- When a shared participant comments, only the task owner receives the
  `todo-comment-added` database notification.
- Newly mentioned allowed users receive `todo-comment-mentioned` database
  notifications. The author is never notified about their own mention. On a new
  shared participant comment, the owner keeps the normal owner comment
  notification instead of also receiving a duplicate mention notification.
- No email, queue worker, cron, supervisor, terminal action, Artisan command
  during normal usage, hosted comment service, or paid provider is required.
