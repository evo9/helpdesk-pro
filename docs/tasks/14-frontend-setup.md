# 14 — Frontend: Project Setup

Требует: 04 (Auth API работает).

Стек: React 19 + TypeScript + Vite + React Query + React Router + shadcn/ui + Tailwind CSS.

## Subtasks

- [ ] **14.1** Установить зависимости:
  ```bash
  npm install @tanstack/react-query react-router-dom axios
  npm install -D tailwindcss @tailwindcss/vite
  ```
- [ ] **14.2** Инициализировать Tailwind CSS (`tailwind.config.ts`, добавить в `vite.config.ts`)
- [ ] **14.3** Установить и инициализировать shadcn/ui (`npx shadcn@latest init`)
- [ ] **14.4** Создать API-клиент `src/api/client.ts`:
  - axios instance с `baseURL = import.meta.env.VITE_API_URL`
  - Interceptor: добавляет `Authorization: Bearer <token>` из localStorage
  - Interceptor response: при 401 — редирект на `/login`
- [ ] **14.5** Создать `src/api/` — функции для каждого ресурса (tickets, comments, users, categories, slaPolices, dashboard)
- [ ] **14.6** Создать `src/contexts/AuthContext.tsx`:
  - Хранит JWT-токен и данные текущего пользователя
  - `login(email, password)` → вызывает API, сохраняет токен
  - `logout()` → очищает токен, редиректит на `/login`
  - `currentUser` — объект с ролью для проверки доступа
- [ ] **14.7** Настроить React Router `src/router.tsx`:
  - Публичные роуты: `/login`, `/register`
  - Приватные роуты: все остальное — редирект на `/login` если не авторизован
  - Role-based роуты: `/dashboard`, `/settings/*` — только manager
- [ ] **14.8** Настроить `QueryClient` в `src/main.tsx` с дефолтными настройками (staleTime, retry)
- [ ] **14.9** Создать базовый layout `src/layouts/AppLayout.tsx` — sidebar + header + main content area
- [ ] **14.10** Проверить `npm run build` без ошибок
