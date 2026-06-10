# 04 — Backend: Authentication (JWT)

Требует: 03 (Domain entities).

## Subtasks

- [ ] **4.1** Настроить `config/packages/security.yaml`:
  - `providers`: UserProvider из Doctrine по `email`
  - `firewalls`: `api` — stateless, JWT authenticator
  - `access_control`: `/api/auth/**` — public, остальное — требует JWT
- [ ] **4.2** Сгенерировать JWT-ключи: `php bin/console lexik:jwt:generate-keypair`
- [ ] **4.3** Реализовать `POST /api/auth/login` — стандартный LexikJWT endpoint
- [ ] **4.4** Реализовать `POST /api/auth/refresh` — refresh token (через `gesdinet/jwt-refresh-token-bundle` или кастомный endpoint)
- [ ] **4.5** Реализовать `POST /api/auth/register` — создание пользователя с ролью reporter
  - Хешировать пароль через `UserPasswordHasherInterface`
  - Вернуть JWT сразу после регистрации
- [ ] **4.6** Написать функциональный тест: регистрация → логин → защищённый эндпоинт

## Проверка

```bash
# Регистрация
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123","full_name":"Test User"}'

# Логин
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
# → {"token": "..."}

# Защищённый endpoint с токеном
curl http://localhost:8080/api/tickets \
  -H "Authorization: Bearer <token>"
# → 200 (пустой список)
```
