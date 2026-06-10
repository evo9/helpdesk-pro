# 19 — CI/CD: GitHub Actions

Требует: все предыдущие задачи завершены.

## Subtasks

### Backend CI
- [ ] **19.1** Создать `.github/workflows/backend.yml`:
  - Trigger: push/PR на main и develop
  - Job `lint`:
    - `php:8.4` image
    - `composer install`
    - `vendor/bin/phpstan analyse`
    - `vendor/bin/php-cs-fixer fix --dry-run`
  - Job `test`:
    - Services: `postgres:16-alpine`
    - Env: `DATABASE_URL=postgresql://test:test@postgres:5432/test`
    - `composer install`
    - `php bin/console doctrine:migrations:migrate --no-interaction`
    - `php bin/phpunit`

### Frontend CI
- [ ] **19.2** Создать `.github/workflows/frontend.yml` (или добавить job в backend.yml):
  - `node:20` image
  - `npm ci`
  - `npm run build` (TypeScript check + Vite build)

### Проверка
- [ ] **19.3** Открыть PR → убедиться что все CI jobs зелёные
- [ ] **19.4** Намеренно сломать PHPStan → убедиться что lint job падает
- [ ] **19.5** Намеренно сломать тест → убедиться что test job падает

## Дополнительно (post-MVP)

- Docker build check (убедиться что образы собираются)
- Dependabot для автоматического обновления зависимостей
