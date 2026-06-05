# Factory Coverage

Step 011 covers the tracked application models:

- `App\Models\User`
- `App\Models\Project`
- `App\Models\Tag`
- `App\Models\Todo`

The interrupted reminder scaffold is still untracked and is not part of the committed model inventory yet.

## User Factory

`UserFactory` covers:

- default verified users with the shared test password,
- unverified users,
- custom passwords,
- confirmed two-factor authentication state,
- configured primary and secondary demo users.

Demo user factory states read from `config/demo.php` so the login panel, seeders, and tests stay aligned.

## Project And Tag Factories

`ProjectFactory` covers:

- default active projects,
- archived projects,
- explicit active state,
- named and color states,
- `work()` and `home()` demo states.

`TagFactory` covers:

- default user-owned tags,
- named and color states,
- `urgent()` and `waiting()` demo states.

## Todo Factory

`TodoFactory` covers:

- active, completed, archived, archived-completed, and soft-deleted lifecycle states,
- low, normal, high, and urgent priority shortcuts,
- due today, overdue, upcoming, no due date, explicit due date, and max-length title states,
- project ownership helper through `forProject()`,
- tag ownership helpers through `forTag()` and `withTags()`.

The tag helpers avoid cross-user attachment by attaching only tags that share the todo owner.

## Verification

`tests/Feature/FactoryCoverageTest.php` creates each tracked model through its factory and verifies the important states and ownership boundaries.
