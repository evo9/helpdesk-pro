# 18 — Frontend: Manager Section

Требует: 15 (Auth), 07 (Users API), 08 (SLA/Categories API), 13 (Dashboard API).

Все страницы доступны только role manager.

## Subtasks

### Dashboard
- [ ] **18.1** Создать `src/pages/manager/DashboardPage.tsx`:
  - Summary cards: open tickets, in progress, SLA breaches today (из `GET /api/dashboard/summary`)
  - Agent workload table: имя агента, активных тикетов, resolved за 30 дней (из `GET /api/dashboard/agents`)
  - Bar chart "Tickets by Category" за последние 30 дней (из `GET /api/dashboard/tickets-by-category`)
  - Для chart — использовать Recharts или Chart.js

### Users Management
- [ ] **18.2** Создать `src/pages/manager/UsersPage.tsx`:
  - Таблица пользователей: имя, email, роль, статус (is_active)
  - Кнопка "Add User" → модалка с формой (full_name, email, password, role)
  - Inline edit роли (select) и is_active (toggle)
  - `useMutation` для создания и обновления

### Categories Management
- [ ] **18.3** Создать `src/pages/manager/CategoriesPage.tsx`:
  - Список категорий
  - Создание/редактирование через модалку
  - Деактивация (toggle `is_active`)

### SLA Policies Management
- [ ] **18.4** Создать `src/pages/manager/SlaPoliciesPage.tsx`:
  - Матрица: строки = категории, столбцы = приоритеты (low/medium/high/critical)
  - В каждой ячейке: response_hours и resolution_hours
  - Inline editing или модалка для редактирования политики

## Проверка

- Dashboard показывает реальные данные
- Создать пользователя → появляется в таблице
- Изменить SLA Policy → не влияет на уже созданные тикеты
