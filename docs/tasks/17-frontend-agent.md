# 17 — Frontend: Agent Panel

Требует: 15 (Auth pages), 05 (Tickets), 06 (Comments), 12 (Audit).

Доступно агентам и менеджерам.

## Subtasks

### SLA Timer компонент
- [ ] **17.1** Создать `src/components/SlaTimer.tsx`:
  - Принимает `due_at: string | null`
  - Обратный отсчёт до дедлайна (обновляется каждую секунду через `setInterval` в `useEffect`)
  - Цветовая кодировка: зелёный / жёлтый / красный
  - При `breached` — показывает сколько просрочено

### Queue (неназначенные тикеты)
- [ ] **17.2** Создать `src/pages/agent/QueuePage.tsx`:
  - `useQuery` → `GET /api/tickets?assignee=null&status=open`
  - Таблица с SLA-таймерами
  - Кнопка "Take" → `PATCH /api/tickets/{id}` с `assignee = currentUser.id`

### My Tickets (назначенные на меня)
- [ ] **17.3** Создать `src/pages/agent/MyTicketsPage.tsx`:
  - `useQuery` → `GET /api/tickets?assignee={currentUser.id}`
  - Фильтры: статус, приоритет, sla_status
  - SLA-таймеры для каждого тикета

### All Tickets (только manager)
- [ ] **17.4** Создать `src/pages/manager/AllTicketsPage.tsx`:
  - `useQuery` → `GET /api/tickets` (без ограничений)
  - Расширенная фильтрация: статус, приоритет, assignee, категория, sla_status
  - Пагинация

### Ticket Detail (agent/manager view)
- [ ] **17.5** Создать `src/pages/agent/TicketDetailPage.tsx`:
  - Все данные тикета + SLA-таймер
  - **Status control**: select со списком доступных переходов + кнопка "Update Status"
  - **Assign control** (только manager): select агентов + кнопка "Assign"
  - Публичные и внутренние комментарии (внутренние с бейджем "Internal")
  - Форма добавления комментария с переключателем Public/Internal
  - Вкладка "Audit Log": хронология изменений из `GET /api/tickets/{id}/audit`

## Проверка

- Взять тикет из очереди → переходит в My Tickets
- Сменить статус → изменение отражается в UI
- Добавить внутренний комментарий → reporter не видит
- SLA-таймер отсчитывает в реальном времени
