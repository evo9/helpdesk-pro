# 13 — Backend: Dashboard API

Требует: 05 (Tickets), 07 (Users).

Только для role manager. Данные читаются напрямую через QueryBuilder — без загрузки полных агрегатов.

## Subtasks

- [ ] **13.1** Создать `src/Dashboard/Application/Query/GetDashboardSummary.php` + handler:
  - Кол-во тикетов по каждому статусу
  - Кол-во нарушений SLA за сегодня (тикеты с `sla_breached` в AuditLog за текущий день)
- [ ] **13.2** Создать `src/Dashboard/Application/Query/GetAgentWorkload.php` + handler:
  - Для каждого активного агента: кол-во назначенных тикетов в работе, кол-во resolved за последние 30 дней
- [ ] **13.3** Создать `src/Dashboard/Infrastructure/Api/Provider/DashboardStateProvider.php`
- [ ] **13.4** Endpoint `GET /api/dashboard/summary` — ответ: `{statuses: {open: N, in_progress: N, ...}, sla_breached_today: N}`
- [ ] **13.5** Endpoint `GET /api/dashboard/agents` — ответ: `[{agent_id, name, active_tickets, resolved_last_30d}]`
- [ ] **13.6** Endpoint `GET /api/dashboard/tickets-by-category` — количество тикетов по категориям за последние 30 дней (для bar chart)
- [ ] **13.7** Написать тест: только manager имеет доступ к `/api/dashboard/*`
