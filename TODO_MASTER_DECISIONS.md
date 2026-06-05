# TODO Master Decisions

Record important architecture/product decisions here.

| Date | Area | Decision | Reason |
|---|---|---|---|
| 2026-06-05 | Prompt location | Use the root `MASTER_PROMPT.md` and `steps/` files because `docs/todo-master-plan/MASTER_PROMPT.md` is not present in this checkout. | The prompt pack exists at the repository root and contains the complete 15-step sequence. |
| 2026-06-05 | Runtime | Code against the installed Laravel Boost runtime: Laravel 13.14, Livewire 4.3, Flux 2.14, Tailwind 4.3, Pest 4.7, PHP CLI 8.4. | Local installed versions are authoritative for tests and generated code. |
| 2026-06-05 | Step continuation | Treat existing commits `b69ac76`, `e53b67c`, `2149412`, and `b461fae` as completed Step 01-04 baseline work. | Docs, changelog, tests, and git history already describe and verify Steps 01-04. |
