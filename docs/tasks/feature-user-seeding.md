# Feature: CreateUser Console Command + Dev Fixtures

## Контекст

Регистрация через `POST /api/auth/register` создаёт только `reporter`. Создать первого менеджера или агента не через кого — замкнутый круг. Нужны два инструмента:

1. **Console command** — для первоначального создания пользователя с любой ролью (продакшен и dev)
2. **Fixtures** — для быстрого сброса dev-окружения с набором тестовых пользователей

## Существующий код

- `api/src/User/Domain/Entity/User.php` — конструктор принимает `email`, `passwordHash`, `fullName`, `UserRole`
- `api/src/User/Domain/Enum/UserRole.php` — варианты: `reporter`, `agent`, `manager`
- `api/src/User/Infrastructure/Doctrine/Repository/UserRepository.php` — метод `save(User $user)`
- `doctrine/doctrine-bundle` установлен, `doctrine/fixtures-bundle` — нет

---

## Часть 1: Console Command

### Создать `api/src/User/Infrastructure/Console/CreateUserCommand.php`

```php
#[AsCommand(name: 'app:user:create', description: 'Create a user with a specific role')]
class CreateUserCommand extends Command
```

Команда принимает аргументы:
- `email` — обязательный аргумент
- `password` — обязательный аргумент
- `--role` — опция, значения: `reporter`, `agent`, `manager`, по умолчанию `reporter`
- `--full-name` — опция, по умолчанию `"New User"`

Логика:
1. Проверить что email ещё не занят — если занят, вывести ошибку и выйти с кодом 1
2. Захешировать пароль через `UserPasswordHasherInterface`
3. Создать `User` с переданными данными
4. Сохранить через `UserRepository`
5. Вывести подтверждение: `User created: admin@example.com (manager)`

Пример использования:
```bash
docker compose exec php php bin/console app:user:create \
  admin@example.com secret123 --role=manager --full-name="Admin User"

docker compose exec php php bin/console app:user:create \
  agent@example.com secret123 --role=agent --full-name="Support Agent"
```

---

## Часть 2: Dev Fixtures

### Установить пакет

```bash
docker compose exec php composer require --dev doctrine/doctrine-fixtures-bundle
```

### Создать `api/src/DataFixtures/AppFixtures.php`

Создать следующих пользователей (пароль у всех: `password`):

| Email | Роль | Full Name |
|---|---|---|
| `manager@example.com` | manager | Alice Manager |
| `agent1@example.com` | agent | Bob Agent |
| `agent2@example.com` | agent | Carol Agent |
| `reporter1@example.com` | reporter | Dave Reporter |
| `reporter2@example.com` | reporter | Eve Reporter |

Класс должен реализовывать `FixtureInterface`. Использовать `UserPasswordHasherInterface` для хеширования.

### Добавить команду в Makefile

```makefile
fixtures:
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

Пример запуска:
```bash
make fixtures
# Careful, database will be purged. Do you want to continue? → --no-interaction пропускает вопрос
```

---

## Проверка

```bash
# Console command
docker compose exec php php bin/console app:user:create \
  test@example.com pass1234 --role=manager --full-name="Test Manager"
# → User created: test@example.com (manager)

# Повторный запуск с тем же email → ошибка
docker compose exec php php bin/console app:user:create \
  test@example.com pass1234 --role=manager
# → [ERROR] User with email test@example.com already exists.

# Fixtures
make fixtures
# Войти на http://localhost:8080 с manager@example.com / password → успешно
# Войти с agent1@example.com / password → успешно
```

## Архитектурные замечания

- `CreateUserCommand` живёт в `Infrastructure/Console/` — это инфраструктурный слой (точка входа через CLI)
- Команда зависит от `UserRepository` напрямую (не через Application layer) — допустимо для CLI-утилиты без сложной бизнес-логики
- `AppFixtures` живёт в `src/DataFixtures/` — стандартное расположение для Symfony
- Fixtures только для `dev` окружения (`require --dev`)
