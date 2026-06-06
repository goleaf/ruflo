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

## UI

The protected `todos.recurring` page at `/todos/recurring` lists the current
user's rules and provides a Flux form for creating, editing, pausing, enabling,
deleting, and processing rules. Task detail pages also show a compact
recurrence card so a single task can be configured without leaving
`/todos/{todo}`.

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

## Restricted Hosting

Recurring task generation uses `App\Actions\Processing\RunManualWebProcess`
through `GenerateRecurringOccurrencesProcess`. Each browser action processes a
bounded owner-scoped chunk, updates `last_generated_until`, and can be retried
by pressing the same button again.

Step 058 requires no cron, queue worker, supervisor, shell access, Artisan
command, paid API, hosted calendar service, or email provider during normal
usage. Exact-time generation is not promised; users see generated future work
when they open the recurrence page or press the generation button.

## Demo Data

`TodoRecurrenceRuleFactory` covers daily, weekly, monthly, paused, ending-on,
after-occurrence, and generated-through states. `TodoFactory` also exposes a
`generatedOccurrence()` state for generated task rows. `TodoRecurrenceRuleSeeder`
adds three idempotent demo rules for each safe demo user and runs the same
web-safe generator through a short demo window so `/todos/recurring`, task
detail pages, and the main task list have immediate local data on
`https://ruflo.test`.
