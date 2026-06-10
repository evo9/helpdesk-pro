# Рефакторинг: Lazy Loading роутов

## Контекст

В `frontend/src/router.tsx` все страницы импортируются статически — весь код попадает в один бандл и загружается при первом открытии приложения. Пользователь с ролью reporter скачивает код дашборда менеджера, которым никогда не воспользуется.

## Текущие статические импорты (все 13 страниц)

```ts
import DashboardPage from '@/pages/DashboardPage'
import SettingsPage from '@/pages/SettingsPage'
import NotFoundPage from '@/pages/NotFoundPage'
import MyTicketsPage from '@/pages/reporter/MyTicketsPage'
import CreateTicketPage from '@/pages/reporter/CreateTicketPage'
import TicketDetailPage from '@/pages/reporter/TicketDetailPage'
import AgentQueuePage from '@/pages/agent/QueuePage'
import MyAssignedPage from '@/pages/agent/MyAssignedPage'
import AgentTicketDetailPage from '@/pages/agent/TicketDetailPage'
import AllTicketsPage from '@/pages/manager/AllTicketsPage'
import LoginPage from '@/pages/LoginPage'
import RegisterPage from '@/pages/RegisterPage'
```

## Что нужно сделать

### 1. Заменить статические импорты на `lazy()`

```ts
import { lazy, Suspense } from 'react'

// Публичные страницы — тоже lazy, грузятся только при переходе
const LoginPage = lazy(() => import('@/pages/LoginPage'))
const RegisterPage = lazy(() => import('@/pages/RegisterPage'))

// Reporter
const MyTicketsPage = lazy(() => import('@/pages/reporter/MyTicketsPage'))
const CreateTicketPage = lazy(() => import('@/pages/reporter/CreateTicketPage'))
const ReporterTicketDetailPage = lazy(() => import('@/pages/reporter/TicketDetailPage'))

// Agent
const AgentQueuePage = lazy(() => import('@/pages/agent/QueuePage'))
const MyAssignedPage = lazy(() => import('@/pages/agent/MyAssignedPage'))
const AgentTicketDetailPage = lazy(() => import('@/pages/agent/TicketDetailPage'))

// Manager
const DashboardPage = lazy(() => import('@/pages/DashboardPage'))
const AllTicketsPage = lazy(() => import('@/pages/manager/AllTicketsPage'))
const SettingsPage = lazy(() => import('@/pages/SettingsPage'))

// Общие
const NotFoundPage = lazy(() => import('@/pages/NotFoundPage'))
```

`AppLayout` и guard-компоненты (`RequireAuth`, `RequireManager`, `RequireReporter`) — оставить статическими, они нужны сразу.

### 2. Создать компонент `PageLoader`

Создать `frontend/src/components/PageLoader.tsx` — заглушка на время загрузки чанка:

```tsx
export default function PageLoader() {
  return (
    <div className="flex h-screen items-center justify-center">
      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
    </div>
  )
}
```

### 3. Обернуть роуты в `<Suspense>`

Добавить `Suspense` с `PageLoader` на уровне `AppLayout`-обёртки в роутере:

```tsx
{
  element: (
    <RequireAuth>
      <Suspense fallback={<PageLoader />}>
        <AppLayout />
      </Suspense>
    </RequireAuth>
  ),
  children: [...],
}
```

И отдельно для публичных роутов (login/register):

```tsx
{
  path: '/login',
  element: (
    <Suspense fallback={<PageLoader />}>
      <LoginPage />
    </Suspense>
  ),
},
```

### 4. Проверить результат

```bash
cd frontend && npm run build
```

В выводе Vite должны появиться отдельные чанки для каждой страницы:

```
dist/assets/DashboardPage-Xxxxx.js      12.50 kB
dist/assets/MyTicketsPage-Xxxxx.js       8.30 kB
dist/assets/AgentQueuePage-Xxxxx.js      9.10 kB
...
```

Вместо одного большого `index-Xxxxx.js`.

Также проверить в браузере: открыть DevTools → Network → при переходе между страницами должны подгружаться новые JS-чанки.

## Ожидаемый эффект

- Начальный бандл уменьшается — загружается только общий код (layout, auth, router)
- Каждая роль загружает только свои страницы
- Пользователь видит спиннер (`PageLoader`) на ~100–200ms при первом переходе на страницу
