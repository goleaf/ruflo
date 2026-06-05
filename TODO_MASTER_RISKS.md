# TODO Master Risks

Record blockers, risks, limitations, and unresolved issues here.

| Date | Risk | Severity | Status | Notes |
|---|---|---|---|---|
| 2026-06-05 | Requested prompt path is missing. | Low | Mitigated | The same master prompt pack is present at repository root; progress records this path adjustment. |
| 2026-06-05 | Prompt lists PHP 8.5, but Boost reports local PHP CLI 8.4. | Low | Open | Laravel 13 tests pass on the installed PHP 8.4 runtime; avoid PHP features unavailable locally. |
| 2026-06-05 | Invite, export, notification, and protected-download feature routes are not implemented yet. | Low | Open | Step 009 establishes the URL contract those later steps must use; concrete link surfaces remain scheduled for their dedicated steps. |
| 2026-06-05 | Demo credentials rely on correct environment configuration. | Medium | Mitigated | The panel and seeder are gated to local/testing/demo and can be disabled with `RUFLO_DEMO_LOGIN_PANEL=false`; production must not run as a safe demo environment. |
| 2026-06-05 | Reminder scaffold remains untracked. | Low | Open | Step 011 intentionally excludes untracked reminder files; factory coverage must be revisited when the reminder model is implemented in its planned step. |
| 2026-06-05 | Future-domain seeders are not implemented. | Low | Open | Reminders, recurrence, comments, attachments, settings, activity, and invites will need seeders when their models are introduced in later steps. |
| 2026-06-05 | Demo-login prompt mentions username/email, but this app currently has email-only authentication. | Low | Mitigated | Step 013 documents and tests email as the visible login identifier; a username field should be introduced only through a dedicated future auth/profile step. |
| 2026-06-05 | Form Request helper names can collide with Laravel framework internals. | Low | Mitigated | Step 014 uses `baseRules`, `attributeNames`, and `messageLines` instead of overriding framework methods such as `validationRules`. |
