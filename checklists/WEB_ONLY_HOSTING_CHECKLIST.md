# Web Only Hosting Checklist.Md

- [ ] no cron dependency
- [ ] no queue worker dependency
- [ ] no artisan dependency for normal usage
- [ ] no terminal dependency
- [ ] web maintenance center exists
- [ ] imports are chunked via Livewire
- [ ] exports are chunked via Livewire
- [ ] reminders process on demand
- [ ] recurring tasks generate on demand
- [ ] cleanup runs from web UI
- [ ] health check runs from web UI
- [ ] progress/retry/resume exists
