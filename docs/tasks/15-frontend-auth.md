# 15 — Frontend: Auth Pages

Требует: 14 (Frontend setup).

## Subtasks

- [ ] **15.1** Создать `src/pages/LoginPage.tsx`:
  - Форма: email + password
  - `useMutation` → вызывает `POST /api/auth/login`
  - При успехе: сохраняет токен в `AuthContext`, редиректит по роли (reporter → `/my-tickets`, agent/manager → `/queue`)
  - Показывает ошибку при неверных credentials
- [ ] **15.2** Создать `src/pages/RegisterPage.tsx`:
  - Форма: full_name + email + password + confirm_password
  - `useMutation` → вызывает `POST /api/auth/register`
  - При успехе: автоматический логин, редирект на `/my-tickets`
- [ ] **15.3** Добавить кнопку "Logout" в AppLayout header

## Проверка

- Регистрация → автологин → редирект
- Неверный пароль → сообщение об ошибке
- Прямой доступ к `/my-tickets` без токена → редирект на `/login`
