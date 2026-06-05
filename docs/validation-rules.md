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
