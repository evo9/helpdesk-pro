# 02 — Backend: Symfony Project Setup

Требует: запущенного Docker стека (задача 01).

## Subtasks

- [ ] **2.1** Установить Symfony 8.1 skeleton (`composer create-project symfony/skeleton app` или через `docker compose exec php composer require ...`)
- [ ] **2.2** Установить основные пакеты:
  - `api-platform/core` ^3
  - `doctrine/orm` ^3, `doctrine/doctrine-bundle`, `doctrine/doctrine-migrations-bundle`
  - `lexik/jwt-authentication-bundle`
  - `symfony/messenger`
  - `symfony/scheduler`
  - `symfony/mailer`
  - `symfony/security-bundle`
- [ ] **2.3** Установить dev-пакеты:
  - `phpunit/phpunit`, `symfony/test-pack`
  - `phpstan/phpstan`, `phpstan/extension-installer`, `phpstan/phpstan-symfony`, `phpstan/phpstan-doctrine`
  - `friendsofphp/php-cs-fixer`
  - `qossmic/deptrac` (проверка layer dependencies)
- [ ] **2.4** Настроить `config/packages/doctrine.yaml` — PostgreSQL DSN из env
- [ ] **2.5** Настроить `config/packages/api_platform.yaml` — форматы, pagination
- [ ] **2.6** Настроить `config/packages/lexik_jwt_authentication.yaml` — генерация ключей (`make sh` → `php bin/console lexik:jwt:generate-keypair`)
- [ ] **2.7** Настроить `config/packages/messenger.yaml` — транспорт `doctrine://default`, routing для всех Message-классов
- [ ] **2.8** Настроить `config/packages/mailer.yaml`
- [ ] **2.9** Создать `phpstan.neon` (level 8, extensions: symfony, doctrine)
- [ ] **2.10** Создать `.php-cs-fixer.php` (PSR-12 + Symfony rules)
- [ ] **2.11** Создать `deptrac.yaml` — правила зависимостей слоёв (Domain ← Application ← Infrastructure)
- [ ] **2.12** Проверить `make lint` и `make test` — оба проходят на пустом проекте

## Проверка

```bash
make sh
php bin/console debug:router          # должны быть маршруты api_platform
php bin/console doctrine:schema:validate
exit
make lint
make test
```
