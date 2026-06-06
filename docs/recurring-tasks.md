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
generation, but Step 057 does not generate new task rows. Occurrence generation,
exceptions, and edit-this-occurrence versus edit-series behavior remain the
next dedicated recurrence steps.

## UI

The protected `todos.recurring` page at `/todos/recurring` lists the current
user's rules and provides a Flux form for creating, editing, pausing, enabling,
and deleting rules. Task detail pages also show a compact recurrence card so a
single task can be configured without leaving `/todos/{todo}`.

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

## Restricted Hosting

Step 057 is rule management only. It requires no cron, queue worker, supervisor,
shell access, Artisan command, paid API, hosted calendar service, or email
provider during normal usage.

The `last_generated_until` column is reserved for the web-triggered occurrence
generation step so later processing can stay idempotent and resumable without a
background scheduler.

## Demo Data

`TodoRecurrenceRuleFactory` covers daily, weekly, monthly, paused, ending-on,
after-occurrence, and generated-through states. `TodoRecurrenceRuleSeeder`
adds three idempotent demo rules for each safe demo user so `/todos/recurring`
and task detail pages have immediate local data on `https://ruflo.test`.
