# 09 — Backend: SLA Mechanics

Требует: 05 (Tickets), 08 (SLA Policies).

## Subtasks

### SlaCalculator
- [ ] **9.1** Реализовать `src/Sla/Domain/Service/SlaCalculator.php`:
  - Принимает `SlaPolicy` и `\DateTimeImmutable $createdAt`
  - Возвращает `response_due_at = createdAt + response_hours`
  - Возвращает `resolution_due_at = createdAt + resolution_hours`
- [ ] **9.2** Написать unit-тесты для `SlaCalculator`

### SLA status
- [ ] **9.3** Реализовать сервис/метод вычисления SLA-статуса тикета:
  - `ok` — до дедлайна > 20% от total time
  - `warning` — до дедлайна ≤ 20% от total time
  - `breached` — дедлайн прошёл
  - Возвращает статус отдельно для response и resolution
- [ ] **9.4** Интегрировать SLA-статус в `TicketStateProvider` (вычисляется при каждом запросе, не хранится)
- [ ] **9.5** Написать unit-тесты для вычисления SLA-статуса

### responded_at
- [ ] **9.6** Убедиться, что `responded_at` выставляется:
  - При первом комментарии агента (см. задачу 6.3)
  - При первой смене статуса на `in_progress` (в `ChangeTicketStatus` handler)
  - Только один раз — проверка `if ($ticket->getRespondedAt() === null)`
- [ ] **9.7** Написать тест: повторные действия не перезаписывают `responded_at`
