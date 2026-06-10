# 06 — Backend: Comments API

Требует: 05 (Tickets API).

## Subtasks

### API Resource & Provider
- [ ] **6.1** Создать `src/Ticket/Infrastructure/Api/Resource/CommentResource.php` (вложен в тикет: `/api/tickets/{id}/comments`)
- [ ] **6.2** Создать `src/Ticket/Infrastructure/Api/Provider/CommentStateProvider.php`:
  - Фильтрует `is_internal = true` для роли reporter (они не видят внутренние заметки)
  - Только agent/manager видят внутренние комментарии

### State Processor
- [ ] **6.3** Создать `src/Ticket/Infrastructure/Api/Processor/AddCommentProcessor.php`:
  - reporter может добавлять только `is_internal = false`
  - agent/manager могут добавлять `is_internal = true` (внутренние заметки)
  - При первом публичном комментарии агента — устанавливает `responded_at` на тикете (если ещё не установлен)
  - Диспатчит `CommentAddedMessage`

### Security
- [ ] **6.4** Создать `src/Ticket/Infrastructure/Security/Voter/CommentVoter.php`:
  - `VIEW`: тот же доступ, что к тикету + фильтр internal
  - `CREATE`: участники тикета (reporter тикета + назначенный agent + manager)

### Application
- [ ] **6.5** Создать `src/Ticket/Application/Command/AddComment.php` + handler

### Tests
- [ ] **6.6** Написать тесты: reporter не видит внутренние комментарии
- [ ] **6.7** Написать тест: первый комментарий агента выставляет `responded_at`
