# Recurring Tasks

## Step 057 Recurring Task Rules

Step 057 adds owner-scoped recurrence rule definitions. A rule belongs to one
active task and one user through `todo_recurrence_rules`; each user/task pair
can have only one current rule.

Supported rule shapes:

- daily rules with an interval from 1 to 30 days,
- weekly rules with one or more selected weekdays,
- monthly rules on a day from 1 to 31,
- ending never, on a date, or after a bounded occurrence count.

Rules can be enabled or paused. Paused rules stay visible for review and future
generation.

## Step 058 Recurring Occurrence Generation

Step 058 adds duplicate-safe, on-demand task generation for enabled recurrence
rules. The original task remains the source/first occurrence; generated task
rows start after `starts_on` and receive recurrence metadata on `todos`:

- `recurrence_rule_id`,
- `recurrence_source_todo_id`,
- `recurrence_occurs_on`,
- `recurrence_sequence`.

Generated occurrences inherit the owner's scope, title context, priority, due
date, project, goal, milestone, habit, tags, checklist item titles, and pending
reminder offset from the source task. Checklist rows are reset to pending on
each new occurrence. A source reminder scheduled relative to the source due date
is copied to each generated task at the same relative time.

The database prevents duplicate generation with a unique
`user_id`/`recurrence_rule_id`/`recurrence_occurs_on` key. The generator also
checks existing rows, including soft-deleted generated rows, before creating an
occurrence, so repeated browser runs are safe and deleting an occurrence can act
as an intentional skip for that date.

## Step 059 Recurring Exceptions

Step 059 adds `todo_recurrence_exceptions` for per-occurrence changes without
rewriting the source series. Exceptions are owner-scoped and attached to a
recurrence rule, the generated occurrence when one exists, and the original
series date.

Supported exception types are:

- skipped occurrences, which soft-delete the generated task and stop that
  original date from being recreated,
- moved occurrences, which move the generated task due date while keeping
  `recurrence_occurs_on` as the original series date and recording the adjusted
  date in exception history,
- edited occurrences, which mark that one generated task was intentionally
  edited without changing the source task or recurrence rule.

Moved occurrences also shift pending reminders by the same date offset so
browser-triggered reminder processing keeps the user's intended reminder timing.
Duplicate prevention checks the generated recurrence date, including trashed
rows, before a move is accepted. Generation also skips both the moved
occurrence's original series date and its adjusted due date so a later retry or
expanded generation window does not create a duplicate task on the adjusted
date.

## UI

The protected `todos.recurring` page at `/todos/recurring` lists the current
user's rules and provides a Flux form for creating, editing, pausing, enabling,
deleting, and processing rules. Task detail pages also show a compact
recurrence card so a single task can be configured without leaving
`/todos/{todo}`.

Rule cards now show exception counts, generated occurrence rows, skip controls,
edit markers, move controls, and a move modal. Exception history stays visible
on the same card so skipped, moved, and edited dates can be audited from the
browser UI.

The recurrence forms use translated Flux form controls, buttons, badges,
callouts, cards, and checkboxes while staying on the normal class-based
Livewire component path.

Complete, archived, and deleted tasks keep their recurrence summary visible but
cannot mutate rules until they are active again. This avoids changing lifecycle
meaning: recurrence edits are task updates, not completion, archive, or trash
transitions.

## Validation

`RecurrenceRuleData` normalizes submitted rule payloads before actions write.
`App\Rules\Todos\RecurrenceRule` validates cross-field schedule combinations,
and `App\Rules\Todos\OwnedActiveTodo` rejects missing, foreign, completed,
archived, or deleted task ids with a generic translated message.

The Livewire surfaces validate near the field and the action layer repeats
ownership and active-task checks before saving, toggling, or deleting a rule.

The Generate occurrences button runs an authenticated Livewire action and
reports matched rules, processed rules, created tasks, skipped rules, failures,
remaining rules, and the generated-through date. Each rule card shows its
current generated-task count.

Recurring exception actions re-query generated occurrence ids through the
current user's owner scope before mutating. Invalid, foreign, non-generated, or
duplicate move-date submissions return translated validation errors next to the
related Flux field.

## Restricted Hosting

Recurring task generation uses `App\Actions\Processing\RunManualWebProcess`
through `GenerateRecurringOccurrencesProcess`. Each browser action processes a
bounded owner-scoped chunk, updates `last_generated_until`, and can be retried
by pressing the same button again.

Step 058 requires no cron, queue worker, supervisor, shell access, Artisan
command, paid API, hosted calendar service, or email provider during normal
usage. Exact-time generation is not promised; users see generated future work
when they open the recurrence page or press the generation button.

Step 059 exception changes are synchronous authenticated Livewire actions. They
do not require background jobs, cron, queue workers, shell access, Artisan
commands, email, paid services, or hosted calendar APIs. Retry means reopening
the same page and submitting the same browser action after correcting any
validation error.

## Demo Data

`TodoRecurrenceRuleFactory` covers daily, weekly, monthly, paused, ending-on,
after-occurrence, and generated-through states. `TodoFactory` also exposes a
`generatedOccurrence()` state for generated task rows.
`TodoRecurrenceExceptionFactory` covers skipped, moved, and edited exception
states plus owner-safe rule/occurrence helpers. `TodoRecurrenceRuleSeeder` adds
three idempotent demo rules for each safe demo user, runs the same web-safe
generator through a short demo window, and records one skipped, one edited, and
one moved occurrence where enough generated demo tasks exist. `/todos/recurring`,
task detail pages, and the main task list therefore have immediate local data on
`https://ruflo.test`.
