# 01 — Docker Infrastructure

Spec: `docs/spect/helpdesk-pro-docker-spec.md`
Detailed plan: `docs/superpowers/plans/2026-06-09-docker-infrastructure.md`

## Subtasks

- [ ] **1.1** Создать `docker/php/Dockerfile` (multi-stage: base → dev + xdebug → prod)
- [ ] **1.2** Создать `docker/php/php.ini`
- [ ] **1.3** Создать `docker/php/xdebug.ini`
- [ ] **1.4** Создать `docker/php/php-fpm.conf`
- [ ] **1.5** Создать `docker/php/entrypoint.sh` (ждёт БД, прогоняет миграции)
- [ ] **1.6** Создать `docker/nginx/Dockerfile` (build arg `ENV=dev|prod`)
- [ ] **1.7** Создать `docker/nginx/dev.conf` (`/api` → php:9000, `/` → frontend:5173 + HMR WebSocket)
- [ ] **1.8** Создать `docker/nginx/prod.conf` (`/api` → php:9000, `/` → собранная статика)
- [ ] **1.9** Создать `frontend/Dockerfile` (multi-stage: base → dev → prod)
- [ ] **1.10** Создать `docker-compose.yml` (php, nginx, frontend, messenger, scheduler, postgres, mailpit)
- [ ] **1.11** Создать `docker-compose.override.yml` (Xdebug env vars, `host.docker.internal`)
- [ ] **1.12** Создать `.env.docker` (дефолтные значения)
- [ ] **1.13** Создать `Makefile` (up, down, build, sh, fe, migrate, diff, test, lint, cs-fix, logs)

## Проверка

```bash
cp .env.docker .env
docker compose build
docker compose up -d
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/        # 200
curl -s -o /dev/null -w "%{http_code}" http://localhost:8025/        # 200 (Mailpit)
docker compose exec postgres pg_isready -U helpdesk
docker compose down
```

## Ключевые решения

- Контекст сборки PHP-сервисов — корень репозитория (`.`), не `./app`, чтобы COPY-инструкции видели `docker/php/`
- `messenger` и `scheduler` — тот же образ что `php`, разный `command`
- В dev nginx проксирует Vite с `proxy_set_header Upgrade` для HMR WebSocket
