# Custom Validation Rules

RuFlo uses reusable Laravel rule objects for business validation that appears in more than one request boundary or that needs owner-scoped database checks.

## Current Rules

The current committed app has tag-name and todo workspace ownership rules:

- `App\Rules\Tags\TagName`
- `App\Rules\Goals\GoalTitle`
- `App\Rules\Goals\MilestoneTitle`
- `App\Rules\Todos\BoardStatus`
- `App\Rules\Todos\CalendarMonth`
- `App\Rules\Todos\ChecklistItemTitle`
- `App\Rules\Todos\DueDate`
- `App\Rules\Todos\InboxCaptureTitle`
- `App\Rules\Todos\OwnedActiveProject`
- `App\Rules\Todos\OwnedTag`
- `App\Rules\Todos\OwnedTodo`
- `App\Rules\Todos\PomodoroDuration`
- `App\Rules\Todos\SavedViewName`
- `App\Rules\Todos\TemplateChecklistItems`
- `App\Rules\Todos\TemplateName`
- `App\Rules\Todos\TimeEntryDuration`

`TagName` validates that a submitted tag name still has visible content after
normalization (`squish()` + lower-case). It prevents whitespace-only labels
from being persisted if a form input contains only spaces.

`GoalTitle` validates that submitted goal titles contain visible text after
whitespace normalization and stay within the current 120-character title limit.

`MilestoneTitle` validates that submitted milestone titles contain visible text
after whitespace normalization and stay within the current 120-character title
limit.

`BoardStatus` validates Kanban target columns. It accepts only Active,
Completed, and Archived so the board cannot move cards into Trash or an unknown
state through a forged Livewire call.

`CalendarMonth` validates URL-backed calendar month state. It accepts only
canonical `YYYY-MM` strings parsed in the configured app timezone, so malformed
month input cannot reach the calendar query.

`ChecklistItemTitle` validates contained checklist item titles. It rejects
non-string or whitespace-only values after `squish()` normalization; the action
layer also rejects empty or overlong direct calls before writing.

`DueDate` validates optional task due dates. Empty values are allowed so
`nullable` form fields can clear a date, but provided values must be canonical
`Y-m-d` date strings parsed in the configured app timezone.

`InboxCaptureTitle` validates quick-captured task titles. It squishes
whitespace, rejects non-string or whitespace-only values, and caps normalized
captured text at 120 characters. `CaptureInboxTodo` and `TriageInboxTodo`
repeat the guard so direct action calls cannot persist blank or overlong inbox
titles when Livewire validation is bypassed.

`OwnedActiveProject` validates that a project id belongs to the authenticated user and is not archived. It is used for task project assignment and bulk move targets.

`OwnedTag` validates that a tag id belongs to the authenticated user. It is used for task tag assignment.

`OwnedTodo` validates that a selected todo id belongs to the authenticated user. It is used by bulk task actions before any mutation runs.

`PomodoroDuration` validates Focus timer duration choices. It accepts only the
free, browser-native options supported by the UI: 15, 25, or 50 minutes.
`StartPomodoroSession` also uses the rule so direct action calls cannot create
unexpected timer durations.

`SavedViewName` validates that a saved task-view name contains visible text
after whitespace normalization. Per-user uniqueness is enforced at the
Livewire validation boundary and by the database unique index.

`TemplateName` validates template names, generated task titles, and project
template names for visible text after whitespace normalization.

`TemplateChecklistItems` validates reusable template checklist arrays. It
normalizes blank rows away, allows at most 10 visible checklist items, caps
each item at 120 characters, and requires at least one item for checklist and
routine templates.

`TimeEntryDuration` validates manual time-entry duration values. It accepts
only whole minutes from 1 to 1440, so a forged Livewire request cannot create a
zero-length or multi-day manual time entry.

The action layer still re-scopes ids to the current user before writing. The rule objects improve request feedback; the action layer remains the defense-in-depth boundary.

## Translation

Rule failure messages live in `lang/en/todos.php` under `todos.validation` and
`lang/en/goals.php` under `goals.validation`.

## Future Domains

Invite token, recurrence, reminder time, file upload, import/export, settings, and role validation rules should be added with their feature steps when the corresponding stable models and request surfaces exist. Do not create placeholder rules for future domains without a concrete caller and test.

## 2026-06-06 Recheck

Step 015 was rechecked from `steps/step-015-reusable-custom-validation-rules.md`.

Confirmed and updated:

- The implemented custom rule inventory is tracked explicitly in the current
  rules list above.
- Every current custom rule implements Laravel's `ValidationRule` contract and fails with a translated message.
- Removed the unused `ReminderAtIsActionable` placeholder rule because it had an empty `validate()` body and no concrete caller.
- Added architecture coverage so future custom rules cannot be silently committed as empty placeholders.
- Future reminder, invite, recurrence, upload, import/export, settings, and role rules remain deferred until their feature steps add real request surfaces and tests.

## 2026-06-06 Step 029 Recheck

Step 029 added `App\Rules\Tags\TagName` and wired it into tag creation. The
`CreateTag` action also rejects a normalized empty name so backend callers stay
safe if Livewire validation is bypassed.

## 2026-06-06 Step 031 Recheck

Step 031 added `App\Rules\Todos\DueDate` and wired it into the shared task
create/edit form. The `TodoData` DTO reuses the same parser so invalid direct
due-date input fails with `todos.validation.due_date` instead of reaching the
database cast.

## 2026-06-06 Step 038 Recheck

Step 038 added `App\Rules\Todos\SavedViewName` and wired it into the saved-view
Livewire action. `SavedTodoViewData` also normalizes saved filter criteria so
unsafe tab, project, tag, priority, due, sort, and direction values cannot
persist as executable query state.

## 2026-06-06 Step 040 Recheck

Step 040 added `App\Rules\Todos\BoardStatus` and wired it into the board
movement Livewire action. Project movement reuses `OwnedActiveProject`, so
target projects must belong to the current user and remain active.

## 2026-06-06 Step 041 Recheck

Step 041 added `App\Rules\Todos\CalendarMonth` and wired it into the calendar
month form and URL-state fallback. Invalid `month` values reset safely to the
current month and invalid submitted month values fail with
`todos.validation.calendar_month`.

## 2026-06-06 Step 042 Recheck

Step 042 added `App\Rules\Todos\ChecklistItemTitle` and wired it into the task
detail checklist add/edit flows. `CreateTodoChecklistItem` and
`UpdateTodoChecklistItem` repeat the backend guard so direct action calls cannot
persist blank or overlong checklist titles if Livewire validation is bypassed.

## 2026-06-06 Step 043 Recheck

Step 043 added `App\Rules\Todos\TemplateName` and
`App\Rules\Todos\TemplateChecklistItems` for reusable task templates.
`TodoTemplateData`, `CreateTodoTemplate`, and `UpdateTodoTemplate` repeat the
backend guards so direct action calls cannot persist blank template names,
invalid kinds, invalid visibility, invalid due offsets, project templates
without a project name, or malformed checklist arrays.

## 2026-06-06 Step 044 Recheck

Step 044 added `App\Rules\Todos\InboxCaptureTitle` and wired it into the
quick-capture Livewire form plus `CaptureInboxTodo` and `TriageInboxTodo`.
The rule keeps captured titles visible-text-only, normalized, translated, and
bounded before a captured task can be written or triaged.

## 2026-06-06 Step 046 Recheck

Step 046 added `App\Rules\Goals\GoalTitle` and
`App\Rules\Goals\MilestoneTitle` for the goals page create forms. The
`CreateGoal`, `CreateGoalMilestone`, and `LinkTodoToGoal` actions repeat
backend guards so direct calls cannot persist blank goal/milestone titles,
foreign projects, foreign milestones, mismatched milestones, or archived/trashed
task links.

## 2026-06-06 Step 047 Recheck

Step 047 added `App\Rules\Habits\HabitTitle` and
`App\Rules\Habits\HabitTargetCount` for the habits page create form.
`CreateHabit`, `ToggleHabitCheckIn`, and `LinkTodoToHabit` repeat backend
guards so direct calls cannot persist blank habit titles, invalid daily/weekly
targets, foreign goals, archived habit check-ins, or archived/trashed task
links.

## 2026-06-06 Step 048 Recheck

Step 048 added `App\Rules\Todos\PomodoroDuration` for Focus timer duration
selection. `StartPomodoroSession` reuses the same rule so direct calls cannot
create unsupported timer lengths, and the Livewire page shows translated field
errors beside the Flux duration select.

## 2026-06-06 Step 049 Recheck

Step 049 added `App\Rules\Todos\TimeEntryDuration` for manual time-entry
duration input. The Livewire page uses it for field validation, and
`TimeEntryData` plus `CreateManualTimeEntry` repeat the backend guard so direct
calls cannot persist zero, negative, non-numeric, or over-1440-minute manual
entries.
