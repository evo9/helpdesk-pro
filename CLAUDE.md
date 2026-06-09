# HelpDesk Pro — Claude Code Guide

Portfolio project: internal service desk for a small company.

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Symfony 8.1 |
| API | API Platform 3 (JSON-LD / JSON:API) |
| ORM | Doctrine ORM 3 |
| Async | Symfony Messenger (Doctrine transport in dev) |
| Scheduler | Symfony Scheduler (SLA checks every 15 min) |
| Auth | LexikJWTAuthenticationBundle |
| Mailer | Symfony Mailer → Mailpit (dev) |
| Frontend | React 19 + TypeScript + Vite + React Query + React Router |
| UI | shadcn/ui + Tailwind CSS |
| Infra | Docker Compose (php, nginx, postgres, mailpit, messenger worker, scheduler worker) |
| CI | GitHub Actions (PHPStan, PHP-CS-Fixer, PHPUnit, frontend build) |

## Project layout

```
helpdesk-pro/
├── api/                    # Symfony project (PHP backend)
│   ├── src/                # Application code (see Architecture below)
│   ├── public/             # Web root (index.php)
│   ├── config/
│   └── tests/
├── frontend/               # React project
│   ├── src/
│   ├── vite.config.ts
│   └── package.json
├── docker/
│   ├── php/                # Dockerfile, php.ini, xdebug.ini, php-fpm.conf, entrypoint.sh
│   └── nginx/              # Dockerfile, dev.conf, prod.conf
├── docs/
│   ├── spect/              # Technical specifications
│   └── tasks/              # Implementation task breakdown
├── docker-compose.yml
├── docker-compose.override.yml   # Dev overrides (Xdebug)
├── Makefile
└── .env.docker             # Default env values (copy to .env)
```

## Backend architecture

Source is grouped **by feature**, each with three layers. `Domain` has zero framework imports.

```
api/src/
├── Ticket/
│   ├── Domain/
│   │   ├── Entity/         # Ticket, Comment, AuditLog
│   │   ├── Enum/           # TicketStatus, TicketPriority
│   │   ├── Event/          # TicketCreated, TicketStatusChanged, …
│   │   └── Repository/     # TicketRepositoryInterface
│   ├── Application/
│   │   ├── Command/        # CreateTicket, ChangeStatus, AssignTicket
│   │   └── Query/          # GetTicketList, GetTicketDetail
│   └── Infrastructure/
│       ├── Doctrine/
│       │   ├── Repository/ # TicketRepository (implements interface)
│       │   └── Listener/   # TicketAuditListener (postUpdate)
│       ├── Messenger/
│       │   ├── Message/    # TicketCreatedMessage, SlaViolatedMessage, …
│       │   └── Handler/    # SendTicketCreatedEmailHandler, …
│       ├── Scheduler/      # CheckSlaViolationsSchedule
│       ├── Security/Voter/ # TicketVoter, CommentVoter
│       └── Api/
│           ├── Resource/   # TicketResource (API Platform DTO)
│           ├── Provider/   # TicketStateProvider
│           └── Processor/  # CreateTicketProcessor, ChangeStatusProcessor, …
├── User/
├── Sla/
└── Dashboard/
```

**Layer rules (enforced by deptrac + PHPStan):**
- `Domain` ← no external imports (no Symfony, no Doctrine)
- `Application` ← imports only `Domain`
- `Infrastructure` ← imports `Application` and `Domain`
- `Api` (Resource/Provider/Processor) ← imports `Application`

## Key decisions

- **API Platform State Providers/Processors** — business logic delegates to Application Command/Query handlers, not inline in processors
- **Snapshot SLA policy** — `response_due_at` / `resolution_due_at` calculated at ticket creation; changing SlaPolicy rules does not affect open tickets
- **Doctrine transport for Messenger** (dev) — no Redis/RabbitMQ dependency for local dev
- **Symfony Voters** — every ticket/comment action checked via a Voter, not `ROLE_*` guards in controllers
- **`is_internal` filtering in State Provider** — reporters never see internal comments; filtered at API layer, not in domain

## Roles

| Role | Symfony security role |
|---|---|
| Reporter | `ROLE_REPORTER` |
| Agent | `ROLE_AGENT` |
| Manager | `ROLE_MANAGER` |

Manager inherits Agent permissions via `role_hierarchy` in `security.yaml`.

## Common commands

```bash
# Start stack
make up

# Stop stack
make down

# PHP shell
make sh

# Frontend shell
make fe

# Run migrations
make migrate

# Generate migration diff
make diff

# Run PHPUnit tests
make test

# PHPStan + PHP-CS-Fixer dry-run
make lint

# Auto-fix code style
make cs-fix

# Follow logs
make logs
```

## Ports (local)

| Service | URL |
|---|---|
| App (nginx) | http://localhost:8080 |
| API | http://localhost:8080/api |
| API Docs (Swagger) | http://localhost:8080/api/docs |
| Mailpit UI | http://localhost:8025 |
| PostgreSQL | localhost:5432 |

## Testing

Backend: **PHPUnit** (`make test`). Tests live in `api/tests/`.
- Unit tests: `api/tests/Unit/` — pure PHP, no database
- Integration tests: `api/tests/Integration/` — hit real database (test schema)
- Functional tests: `api/tests/Functional/` — HTTP-level tests via Symfony WebTestCase

Frontend: **Vitest** (`npm test`). Tests live in `frontend/src/` alongside components.

## CI (GitHub Actions)

Three jobs run on every PR:
1. `lint` — PHPStan level 8 + PHP-CS-Fixer check
2. `test` — PHPUnit with a test database
3. `frontend` — `npm run build` (TypeScript + Vite)

## Git rules

See `.claude/rules/git-operations.md`. Summary: never commit or push automatically — always wait for explicit user request.
