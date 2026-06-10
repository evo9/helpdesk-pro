# 10 — Backend: Async Messages (Messenger)

Требует: 05 (Tickets), 06 (Comments).

Все сообщения используют транспорт `doctrine://default` (таблица `messenger_messages`).

## Subtasks

### TicketCreated
- [ ] **10.1** Создать `src/Ticket/Infrastructure/Messenger/Message/TicketCreatedMessage.php`
- [ ] **10.2** Создать `src/Ticket/Infrastructure/Messenger/Handler/SendTicketCreatedEmailHandler.php`:
  - Email репортеру: подтверждение создания
  - Email всем активным агентам: новый тикет в очереди

### TicketAssigned
- [ ] **10.3** Создать `TicketAssignedMessage.php`
- [ ] **10.4** Создать handler: email назначенному агенту

### TicketStatusChanged
- [ ] **10.5** Создать `TicketStatusChangedMessage.php`
- [ ] **10.6** Создать handler: email репортеру о смене статуса

### CommentAdded
- [ ] **10.7** Создать `CommentAddedMessage.php`
- [ ] **10.8** Создать handler: email второй стороне (агент пишет → email репортеру; репортер пишет → email агенту)
  - Только публичные комментарии (`is_internal = false`)

### SlaViolated
- [ ] **10.9** Создать `SlaViolatedMessage.php` (тип нарушения: response | resolution)
- [ ] **10.10** Создать `HandleSlaViolatedHandler.php`:
  - Запись в AuditLog (`ticket.sla_breached`)
  - Email агенту и менеджеру

### Проверка
- [ ] **10.11** Настроить Mailpit и проверить получение писем после создания тикета:
  ```bash
  # Создать тикет → открыть http://localhost:8025 → убедиться что письма пришли
  make logs   # смотреть вывод контейнера messenger
  ```
