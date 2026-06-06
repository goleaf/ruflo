# Request Validation

RuFlo keeps request validation close to the request boundary and keeps Livewire-only state inside Livewire form objects or components.

## HTTP And Fortify Requests

Authentication request rules live in dedicated Form Request classes:

- `App\Http\Requests\Auth\RegisterUserRequest`
- `App\Http\Requests\Auth\ResetUserPasswordRequest`

Laravel Fortify calls the application's `CreateNewUser` and `ResetUserPassword` actions with input arrays instead of type-hinted request instances. Those actions still use the Form Request classes as the canonical rule source through their `baseRules()`, `attributeNames()`, and message helper methods.

This keeps registration and password-reset validation out of action bodies while preserving Fortify's normal throttling, sessions, redirects, token checks, and password broker flow.

## Livewire Forms

Livewire component-only forms should continue to use Livewire form objects or component validation when there is no separate HTTP controller action. The current task form uses `App\Livewire\Forms\Todos\TodoForm` because its state is bound directly to the Livewire task workspace.

When a future feature adds a traditional controller or route action that accepts request input, create a dedicated Form Request and consume `$request->validated()` or `$request->safe()` instead of reading unvalidated payloads.

Step 043 task templates remain a Livewire-only workflow. No controller request
class is introduced because `/todos/templates` is a class-based Livewire page;
the component validates request shape, custom rule objects handle reusable
business rules, and `TodoTemplateData` plus action classes repeat the backend
guards for direct calls.

Step 044 quick capture Inbox also remains a Livewire-only workflow. No HTTP
Form Request is introduced because `/todos/inbox` has no controller action;
the component validates `captureTitle`, `InboxCaptureTitle` handles reusable
visible-text rules, and `CaptureInboxTodo` plus `TriageInboxTodo` repeat the
backend guards for direct action calls.

Step 045 focus mode remains Livewire-only and introduces no free-form request
payload. The only submitted values are task ids from Livewire actions; those
ids are resolved through `TodoFocusQuery::findFor($user, $id)` and then
authorized before complete, defer, or snooze mutations run. No dedicated HTTP
Form Request or custom rule is introduced because there is no controller
request body or repeated free-form validation logic.

Step 046 goals and milestones remain Livewire-only. No HTTP Form Request is
introduced because `/goals` is a class-based Livewire page, not a controller
endpoint. The component validates goal and milestone form state, reuses
`OwnedActiveProject` for project assignment, uses `GoalTitle` and
`MilestoneTitle` for repeated visible-title checks, and passes normalized data
objects into action classes that repeat ownership and spoofing guards for direct
calls.

Step 047 habits remain Livewire-only. No HTTP Form Request is introduced
because `/habits` is a class-based Livewire page, not a controller endpoint.
The component validates habit form state, uses `HabitTitle` and
`HabitTargetCount` for repeated business rules, and passes normalized data into
action classes that repeat owned-goal, check-in, and task-link guards for direct
calls.

Step 048 Pomodoro focus timer remains Livewire-only. No HTTP Form Request is
introduced because `/todos/focus` is a class-based Livewire page, not a
controller endpoint. The only free-form timer input is `durationMinutes`, which
is guarded by `PomodoroDuration` and revalidated inside `StartPomodoroSession`
before a session row is created. Timer state changes resolve the active session
through `PomodoroSessionQuery` and authorize the owner-scoped policy before
mutation.

Step 049 time tracking remains Livewire-only. No HTTP Form Request is
introduced because `/todos/time` is a class-based Livewire page, not a
controller endpoint. The component validates task/project context, tracked
date, notes, and manual duration; `TimeEntryDuration` handles reusable duration
rules; `TimeEntryData` and action classes repeat backend guards so direct calls
cannot persist unsupported durations, future dates, foreign context, duplicate
active timers, or running-entry deletion.

Step 050 task dependencies remain Livewire-only. No HTTP Form Request is
introduced because dependency add/remove actions live on the class-based
`/todos/{todo}` Livewire page and `/todos/blocked` is read-only. The component
validates the blocker picker with `AcyclicTodoDependency`; `AddTodoDependency`
and `TodoDependencyQuery` repeat owner, active-state, duplicate, self-reference,
and cycle guards for direct calls.

Step 051 cleanup views remain Livewire-only and read-only. No HTTP Form Request
or new custom validation rule is introduced because `/todos/cleanup` has no
controller endpoint and writes no data. URL-backed cleanup state is bounded by
`TodoCleanupFilters`, invalid views fail closed to an empty result, and invalid
sort/direction values fall back to safe query defaults.

Step 052 automation rules remain Livewire-only. No HTTP Form Request is
introduced because `/todos/automations` is a class-based Livewire page, not a
controller endpoint. Rule creation uses `AutomationRuleName` plus owner-scoped
unique validation, submitted rule ids are resolved through `AutomationRuleQuery`
before toggling/testing/running, and action classes repeat owner and disabled
rule guards before writing tasks or run logs.

Step 053 manual web processing introduces no HTTP controller endpoint and no
new user-submitted form fields. The reusable engine receives already-authorized
feature-owned process objects. Feature Livewire components remain responsible
for validating user input before they trigger a process, while
`ManualWebProcess::query()` implementations must reapply owner scope before
records can be processed.

Step 054 reminders remain Livewire-only. No HTTP Form Request is introduced
because `/todos/reminders` is a class-based Livewire page, not a controller
endpoint. The component validates the selected task with `OwnedTodo` and the
reminder timestamp with `ReminderAt`; `SyncTodoReminder` and
`ProcessDueRemindersProcess` repeat owner, active-task, preference, and
actionable-task guards before writing reminder state or database notifications.

Step 057 recurring task rules remain Livewire-only. No HTTP Form Request is
introduced because `/todos/recurring` and `/todos/{todo}` are class-based
Livewire pages, not controller endpoints. The components validate task
selection with `OwnedActiveTodo`, validate schedule shape with `RecurrenceRule`,
normalize input through `RecurrenceRuleData`, and action classes repeat owner
and active-task guards before saving, pausing, enabling, or deleting rules.

## Custom Business Rules

Reusable business validation rules are documented in `docs/validation-rules.md`.

## Localization

Auth validation attributes and custom auth validation messages are translated in `lang/en/auth.php`.

## 2026-06-06 Recheck

Step 014 was rechecked from `steps/step-014-dedicated-request-classes.md`.

Confirmed and updated:

- The only request-driven application input boundary is Fortify authentication; there are no conventional application controllers accepting create/update/delete payloads yet.
- `RegisterUserRequest` and `ResetUserPasswordRequest` remain the canonical rule, attribute, and message sources for Fortify registration and password reset actions.
- Livewire-only task and settings forms stay in Livewire form objects/components because their state is not handled by controller methods.
- Added feature coverage for the Form Request classes themselves, including authorization, rule keys, translated attributes, and the duplicate-email message.
- Request validation tests that assert translations run in the feature suite because the unit suite does not boot Laravel's translator container.
