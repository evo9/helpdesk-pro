# 12 — Backend: Audit Log API

Требует: 05 (Tickets), 03 (AuditLog entity).

## Subtasks

- [ ] **12.1** Создать `src/Ticket/Infrastructure/Api/Resource/AuditLogResource.php`
- [ ] **12.2** Endpoint `GET /api/tickets/{id}/audit` (только agent/manager):
  - Возвращает список записей AuditLog для тикета
  - Сортировка: `created_at DESC`
- [ ] **12.3** Убедиться что `TicketAuditListener` корректно фиксирует все события:
  - `ticket.created` — при создании
  - `ticket.status_changed` — старый и новый статус в `payload`
  - `ticket.assigned` — id агента в `payload`
  - `ticket.priority_changed`
  - `ticket.sla_breached` — из `HandleSlaViolatedHandler`
  - `comment.added` — id комментария в `payload`
- [ ] **12.4** Написать тест: создать тикет, сменить статус, добавить комментарий → проверить audit log
