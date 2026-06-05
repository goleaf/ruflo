# Custom Validation Rules

RuFlo uses reusable Laravel rule objects for business validation that appears in more than one request boundary or that needs owner-scoped database checks.

## Current Rules

The current committed app has task, project, and tag ownership rules:

- `App\Rules\Todos\OwnedActiveProject`
- `App\Rules\Todos\OwnedTag`
- `App\Rules\Todos\OwnedTodo`

`OwnedActiveProject` validates that a project id belongs to the authenticated user and is not archived. It is used for task project assignment and bulk move targets.

`OwnedTag` validates that a tag id belongs to the authenticated user. It is used for task tag assignment.

`OwnedTodo` validates that a selected todo id belongs to the authenticated user. It is used by bulk task actions before any mutation runs.

The action layer still re-scopes ids to the current user before writing. The rule objects improve request feedback; the action layer remains the defense-in-depth boundary.

## Translation

Rule failure messages live in `lang/en/todos.php` under `todos.validation`.

## Future Domains

Invite token, recurrence, reminder time, file upload, import/export, settings, and role validation rules should be added with their feature steps when the corresponding stable models and request surfaces exist. Do not create placeholder rules for future domains without a concrete caller and test.

## 2026-06-06 Recheck

Step 015 was rechecked from `steps/step-015-reusable-custom-validation-rules.md`.

Confirmed and updated:

- The current implemented custom rule inventory is exactly the three todo ownership rules listed above.
- Every current custom rule implements Laravel's `ValidationRule` contract and fails with a translated message.
- Removed the unused `ReminderAtIsActionable` placeholder rule because it had an empty `validate()` body and no concrete caller.
- Added architecture coverage so future custom rules cannot be silently committed as empty placeholders.
- Future reminder, invite, recurrence, upload, import/export, settings, and role rules remain deferred until their feature steps add real request surfaces and tests.
