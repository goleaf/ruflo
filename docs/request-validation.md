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

## Custom Business Rules

Reusable business validation rules are documented in `docs/validation-rules.md`.

## Localization

Auth validation attributes and custom auth validation messages are translated in `lang/en/auth.php`.
