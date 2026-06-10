# 16 — Frontend: Reporter Portal

Требует: 15 (Auth pages), 05 (Tickets API), 06 (Comments API).

Доступно только пользователям с ролью `reporter`.

## Subtasks

### SLA Indicator компонент
- [ ] **16.1** Создать `src/components/SlaIndicator.tsx`:
  - Принимает `response_due_at`, `resolution_due_at`, `responded_at`, `status`
  - Отображает цветной бейдж: зелёный (ok), жёлтый (warning), красный (breached)
  - Для reporter: только индикатор без таймера (таймер с обратным отсчётом — в задаче 17)

### My Tickets
- [ ] **16.2** Создать `src/pages/reporter/MyTicketsPage.tsx`:
  - `useQuery` → `GET /api/tickets` (API возвращает только тикеты репортера)
  - Фильтр по статусу (select: all, open, in_progress, pending, resolved)
  - Таблица/список с колонками: заголовок, категория, приоритет, статус, SLA-индикатор, дата создания
  - Кнопка "New Ticket"
- [ ] **16.3** Создать `src/pages/reporter/CreateTicketPage.tsx`:
  - Форма: title, description, category (select из `/api/categories`), priority (select)
  - `useMutation` → `POST /api/tickets`
  - Редирект на страницу тикета после создания

### Ticket Detail
- [ ] **16.4** Создать `src/pages/reporter/TicketDetailPage.tsx`:
  - Заголовок, описание, статус, приоритет, категория, дата создания
  - SLA-индикатор
  - Список публичных комментариев (внутренние не показывать)
  - Форма добавления комментария (`useMutation` → `POST /api/tickets/{id}/comments`)
  - Кнопка "Reopen" (если статус `resolved` и прошло < 72ч)

## Проверка

- Создать тикет → появляется в My Tickets
- Добавить комментарий → виден в списке
- Фильтр по статусу работает
