# HelpDesk Pro — Task Map

Полная разбивка ТЗ на реализуемые блоки. Каждый файл — независимый рабочий блок с подзадачами-чекбоксами.

## Порядок реализации

```
01 → 02 → 03 → 04 → 05 → 06 → 07 → 08
 ↓                   ↓
Docker           Tickets API
                    ↓
              09 (SLA mechanics)
              10 (Messenger)
              11 (Scheduler)
              12 (Audit API)
              13 (Dashboard)
              ↓
         14 (Frontend setup)
         15 → 16 → 17 → 18
              ↓
           19 (CI/CD)
```

| Файл | Блок | Зависит от |
|---|---|---|
| [01](01-docker-infrastructure.md) | Docker Infrastructure | — |
| [02](02-backend-setup.md) | Backend: Symfony setup | 01 |
| [03](03-backend-domain.md) | Backend: Domain entities | 02 |
| [04](04-backend-auth.md) | Backend: Auth (JWT) | 03 |
| [05](05-backend-tickets.md) | Backend: Tickets API | 03, 04 |
| [06](06-backend-comments.md) | Backend: Comments API | 05 |
| [07](07-backend-users.md) | Backend: Users API | 04 |
| [08](08-backend-sla-categories.md) | Backend: Categories & SLA Policies API | 03 |
| [09](09-backend-sla-mechanics.md) | Backend: SLA mechanics | 05, 08 |
| [10](10-backend-messenger.md) | Backend: Async (Messenger) | 05, 06 |
| [11](11-backend-scheduler.md) | Backend: Scheduler (SLA checks) | 09, 10 |
| [12](12-backend-audit.md) | Backend: Audit log API | 05 |
| [13](13-backend-dashboard.md) | Backend: Dashboard API | 05, 07 |
| [14](14-frontend-setup.md) | Frontend: Project setup | 04 |
| [15](15-frontend-auth.md) | Frontend: Auth pages | 14 |
| [16](16-frontend-reporter.md) | Frontend: Reporter portal | 15 |
| [17](17-frontend-agent.md) | Frontend: Agent panel | 15 |
| [18](18-frontend-manager.md) | Frontend: Manager section | 15 |
| [19](19-ci-cd.md) | CI/CD: GitHub Actions | все |
