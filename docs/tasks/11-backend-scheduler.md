# 11 — Backend: Scheduler (SLA checks)

Требует: 09 (SLA mechanics), 10 (Messenger).

## Subtasks

- [ ] **11.1** Создать `src/Ticket/Infrastructure/Scheduler/CheckSlaViolationsSchedule.php`:
  - Реализует `ScheduleProviderInterface`
  - Регистрирует задачу каждые 15 минут (`RecurringMessage::every('15 minutes', ...)`)
- [ ] **11.2** Реализовать `CheckSlaViolationsCommand` (или Message/Handler):
  - Находит тикеты: `response_due_at < NOW()` AND `responded_at IS NULL` (нарушение response SLA)
  - Находит тикеты: `resolution_due_at < NOW()` AND `status NOT IN ('resolved', 'closed')` (нарушение resolution SLA)
  - Для каждого нарушения диспатчит `SlaViolatedMessage` в Messenger
  - Не дублирует уже обработанные нарушения (проверять AuditLog или отдельный флаг)
- [ ] **11.3** Написать интеграционный тест с фиктивным временем (`ClockInterface` mock):
  - Создать тикет с `resolution_due_at` в прошлом
  - Запустить scheduler task вручную
  - Убедиться что `SlaViolatedMessage` попал в очередь
