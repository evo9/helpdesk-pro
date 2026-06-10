# Feature: Categories Management (Frontend)

## Контекст

Бэкенд полностью готов:
- `GET/POST/PATCH/DELETE /api/categories` — работает
- `api/src/Sla/Infrastructure/Api/Resource/CategoryResource.php` — все операции
- `frontend/src/api/categories.ts` — API-клиент есть (`getCategories`, `createCategory`, `updateCategory`, `deleteCategory`)

Не хватает только UI. `SettingsPage` сейчас — заглушка `<div>Settings</div>`.

## Что нужно сделать

### 1. Создать страницу `frontend/src/pages/manager/CategoriesPage.tsx`

Страница управления категориями для менеджера. Требования:

- `useQuery(['categories'], getCategories)` — загрузить список категорий
- Таблица с колонками: **Name**, **Description**, **Status** (Active/Inactive), **Actions**
- Кнопка **"Add Category"** — открывает модальное окно для создания
- В строке таблицы — кнопки **Edit** и **Delete**
- Деактивированные категории (`isActive: false`) отображаются с визуальным отличием (серый текст или badge "Inactive")

**Модальное окно создания/редактирования:**
- Поля: Name (обязательное), Description (необязательное)
- При редактировании — поле-переключатель Active/Inactive (`isActive`)
- `useMutation` → `createCategory` или `updateCategory`
- После успеха: закрыть модалку, инвалидировать `['categories']`

**Удаление:**
- Confirm-диалог перед удалением ("Are you sure?")
- `useMutation` → `deleteCategory`
- После успеха: инвалидировать `['categories']`

Использовать существующие shadcn/ui компоненты из `frontend/src/components/ui/`.

### 2. Обновить `frontend/src/pages/SettingsPage.tsx`

Заменить заглушку на layout с навигацией по разделам настроек (сейчас только Categories, в будущем SLA Policies):

```
Settings
├── Categories     ← /settings/categories
└── SLA Policies   ← /settings/sla-policies (placeholder)
```

Использовать вложенную навигацию (tabs или sidebar-меню внутри страницы).

### 3. Обновить роутер `frontend/src/router.tsx`

Добавить вложенные роуты внутри `settings/*`:

```tsx
{ path: 'settings', element: <SettingsPage /> children: [
  { index: true, element: <Navigate to="settings/categories" replace /> },
  { path: 'categories', element: <CategoriesPage /> },
]}
```

### 4. Добавить ссылку в навигацию

В `frontend/src/layouts/AppLayout.tsx` — добавить пункт **"Settings"** в sidebar/меню, видимый только для `ROLE_MANAGER`. Ссылка ведёт на `/settings/categories`.

## Проверка

1. Войти как менеджер → в меню появился пункт Settings
2. `/settings/categories` — отображается список категорий из БД
3. Создать новую категорию → появляется в списке без перезагрузки страницы
4. Редактировать название → изменение отражается в списке
5. Деактивировать категорию → отображается как Inactive
6. Удалить категорию → исчезает из списка

## Типы (уже есть в `frontend/src/api/types.ts`)

Убедиться что тип `Category` содержит поля:
```typescript
interface Category {
  id: string
  name: string
  description: string | null
  isActive: boolean
}
```
