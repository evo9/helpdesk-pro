# 05 — Backend: Tickets API

Требует: 03 (Domain), 04 (Auth).

## Subtasks

### API Resource
- [ ] **5.1** Создать `src/Ticket/Infrastructure/Api/Resource/TicketResource.php` — DTO для API Platform с операциями GET (collection + item), POST, PATCH, DELETE

### State Provider
- [ ] **5.2** Создать `src/Ticket/Infrastructure/Api/Provider/TicketStateProvider.php`:
  - Для collection: фильтрует по роли (reporter видит только свои, agent — свои + очередь, manager — все)
  - Параметры фильтрации: `status`, `priority`, `assignee_id`, `sla_status`
  - `sla_status` вычисляется на лету (не хранится в БД)

### State Processors
- [ ] **5.3** Создать `src/Ticket/Infrastructure/Api/Processor/CreateTicketProcessor.php`:
  - Валидирует входные данные
  - Вызывает `SlaCalculator` для расчёта дедлайнов
  - Сохраняет тикет
  - Диспатчит `TicketCreatedMessage`
- [ ] **5.4** Создать `src/Ticket/Infrastructure/Api/Processor/UpdateTicketProcessor.php` (PATCH):
  - Смена статуса (через допустимые переходы)
  - Назначение агента
  - Смена приоритета
  - При каждом изменении пишет в AuditLog через listener
- [ ] **5.5** Создать `src/Ticket/Infrastructure/Api/Processor/DeleteTicketProcessor.php` — только для manager

### Security
- [ ] **5.6** Создать `src/Ticket/Infrastructure/Security/Voter/TicketVoter.php`:
  - `VIEW`: reporter видит только свои; agent — свои + очередь; manager — все
  - `CREATE`: только reporter
  - `UPDATE_STATUS`: agent (свои), manager (все)
  - `ASSIGN`: только manager
  - `DELETE`: только manager
  - `REOPEN`: reporter, если тикет resolved и прошло < 72 часов

### Application layer
- [ ] **5.7** Создать `src/Ticket/Application/Command/CreateTicket.php` + handler
- [ ] **5.8** Создать `src/Ticket/Application/Command/ChangeTicketStatus.php` + handler (проверяет допустимые переходы: open→in_progress, in_progress→pending, pending→resolved, resolved→closed, resolved→open)
- [ ] **5.9** Создать `src/Ticket/Application/Command/AssignTicket.php` + handler
- [ ] **5.10** Создать `src/Ticket/Application/Query/GetTicketList.php` + handler
- [ ] **5.11** Создать `src/Ticket/Application/Query/GetTicketDetail.php` + handler

### Tests
- [ ] **5.12** Написать unit-тесты на TicketVoter
- [ ] **5.13** Написать функциональные тесты на все CRUD операции (все роли)

## Проверка

```bash
# Создать тикет (reporter)
curl -X POST http://localhost:8080/api/tickets \
  -H "Authorization: Bearer <reporter_token>" \
  -H "Content-Type: application/json" \
  -d '{"title":"Broken keyboard","description":"Keys not working","category":"/api/categories/1","priority":"medium"}'

# Получить список (agent)
curl http://localhost:8080/api/tickets \
  -H "Authorization: Bearer <agent_token>"
```
