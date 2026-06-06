# Task Comments

Step 072 adds plain-text task comment threads to the protected task detail page.

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

## Access

- Task owners, project managers, and project editors can post comments when they
  can use the parent task `comment` ability.
- Project viewers can read comments on visible shared tasks but cannot post.
- Only the original comment author can edit or delete an active comment.
- Removed project members lose access because every comment read and mutation
  rechecks parent task visibility.

## Validation

- `StoreTodoCommentRequest` and `UpdateTodoCommentRequest` are the canonical rule
  sources for comment body rules and translated attributes.
- `TodoCommentBody` rejects empty visible text and bodies over 2000 characters.
- `TodoCommentData` normalizes line endings, strips null bytes, and trims the
  persisted body.

## Notifications And Activity

- Comment creation dispatches `TodoCommentCreated`; edits and deletes dispatch
  matching update/delete events.
- `RecordTodoActivity` writes synchronous parent-task activity records with a
  bounded comment excerpt.
- When a shared participant comments, only the task owner receives the
  `todo-comment-added` database notification.
- No email, queue worker, cron, supervisor, terminal action, Artisan command
  during normal usage, hosted comment service, or paid provider is required.

## Deferred

`@mention` text is inert in Step 072. Mention suggestions, parsing, links, and
mention notifications belong to Step 073.
