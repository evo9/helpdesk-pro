# HelpDesk Pro

Internal service desk system for small and medium companies. Employees submit support tickets, agents process them, and managers monitor SLA compliance and team workload.

> Portfolio project demonstrating production-grade Symfony + React architecture.

---

## Tech Stack

### Backend

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-8.1-000000?style=flat-square&logo=symfony&logoColor=white)
![API Platform](https://img.shields.io/badge/API_Platform-3-4285F4?style=flat-square&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0id2hpdGUiIGQ9Ik0xMiAyQzYuNDggMiAyIDYuNDggMiAxMnM0LjQ4IDEwIDEwIDEwIDEwLTQuNDggMTAtMTBTMTcuNTIgMiAxMiAyem0tMSAxNy45M1Y0LjA3YzMuOTMuNDkgNyAzLjg1IDcgNy45M3MtMy4wNyA3LjQ0LTcgNy45M3oiLz48L3N2Zz4=&logoColor=white)
![Doctrine ORM](https://img.shields.io/badge/Doctrine_ORM-3-FC6A31?style=flat-square&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-LexikBundle-000000?style=flat-square&logo=jsonwebtokens&logoColor=white)
![Messenger](https://img.shields.io/badge/Symfony_Messenger-async-000000?style=flat-square&logo=symfony&logoColor=white)
![Scheduler](https://img.shields.io/badge/Symfony_Scheduler-cron-000000?style=flat-square&logo=symfony&logoColor=white)

### Frontend

![React](https://img.shields.io/badge/React-19-61DAFB?style=flat-square&logo=react&logoColor=black)
![TypeScript](https://img.shields.io/badge/TypeScript-5.6-3178C6?style=flat-square&logo=typescript&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-6-646CFF?style=flat-square&logo=vite&logoColor=white)
![React Query](https://img.shields.io/badge/TanStack_Query-5-FF4154?style=flat-square&logo=reactquery&logoColor=white)
![React Router](https://img.shields.io/badge/React_Router-7-CA4245?style=flat-square&logo=reactrouter&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)
![shadcn/ui](https://img.shields.io/badge/shadcn%2Fui-latest-000000?style=flat-square&logoColor=white)

### Infrastructure & Tooling

![Docker](https://img.shields.io/badge/Docker_Compose-v2-2496ED?style=flat-square&logo=docker&logoColor=white)
![Nginx](https://img.shields.io/badge/Nginx-1.25-009639?style=flat-square&logo=nginx&logoColor=white)
![Mailpit](https://img.shields.io/badge/Mailpit-SMTP_dev-FF6C37?style=flat-square&logoColor=white)
![GitHub Actions](https://img.shields.io/badge/GitHub_Actions-CI-2088FF?style=flat-square&logo=githubactions&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-level_8-8892BF?style=flat-square&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-13-1A7BB9?style=flat-square&logoColor=white)
![Vitest](https://img.shields.io/badge/Vitest-4-6E9F18?style=flat-square&logo=vitest&logoColor=white)

---

## Architecture

The backend follows **feature-based DDD** with three layers per feature. The `Domain` layer has zero framework dependencies — pure PHP classes and interfaces only.

```
api/src/
├── Ticket/
│   ├── Domain/          # Entities, Enums, Events, Repository interfaces
│   ├── Application/     # Commands, Queries, Handlers
│   └── Infrastructure/  # Doctrine, Messenger, Scheduler, Security Voters, API Platform
├── User/
├── Sla/
└── Dashboard/
```

**Layer rules (enforced by PHPStan + deptrac):**

```
Domain  ←  Application  ←  Infrastructure / Api
  ↑                               ↓
pure PHP                  Symfony, Doctrine, API Platform
```

### Key design decisions

- **API Platform State Providers/Processors** — business logic lives in Application layer, processors only delegate
- **Symfony Voters** — every ticket and comment action checked through a dedicated Voter
- **SLA snapshot** — `response_due_at` / `resolution_due_at` calculated at ticket creation; changing SLA rules never affects open tickets
- **Async messaging** — all email notifications dispatched via Symfony Messenger (Doctrine transport in dev)
- **Internal comments** — filtered at the API layer (State Provider), invisible to reporters

---

## Roles

| Role | Access |
|---|---|
| **Reporter** | Submit tickets, view own tickets, add public comments, reopen resolved tickets within 72h |
| **Agent** | View queue and assigned tickets, change status, add public and internal comments |
| **Manager** | All agent permissions + view all tickets, assign agents, manage categories/SLA/users, view dashboard |

---

## Services

| Service | URL |
|---|---|
| App (React SPA) | http://localhost:8080 |
| REST API | http://localhost:8080/api |
| Swagger UI | http://localhost:8080/api/docs |
| Mailpit (email preview) | http://localhost:8025 |
| pgAdmin | http://localhost:5050 |
| PostgreSQL | localhost:5432 |

---

## Getting Started

### Prerequisites

- Docker Desktop or Docker Engine + Compose v2
- Git

### First run

```bash
git clone <repo-url> helpdesk-pro
cd helpdesk-pro

# Copy env file
cp .env.docker .env

# Build and start all services
docker compose build
docker compose up -d

# Run database migrations (auto-runs via entrypoint, but can run manually)
make migrate

# Create the first manager user
docker compose exec php php bin/console app:user:create \
  admin@example.com secret123 --role=manager --full-name="Admin"

# Or load dev fixtures (all roles, password: "password")
make fixtures
```

Open http://localhost:8080 and log in.

---

## Development

### Common commands

```bash
make up          # Start all services
make down        # Stop all services
make build       # Rebuild images

make sh          # PHP container shell
make fe          # Frontend container shell

make migrate     # Run pending migrations
make diff        # Generate migration from entity changes

make test        # Run PHPUnit tests
make lint        # PHPStan + PHP-CS-Fixer dry-run
make cs-fix      # Auto-fix code style

make fixtures    # Load dev fixtures (resets database)
make logs        # Follow logs: php, messenger, scheduler, frontend
```

### Running a single test

```bash
docker compose exec php php bin/phpunit tests/Unit/Sla/SlaCalculatorTest.php
```

### Frontend dev

The Vite dev server runs inside Docker with HMR through nginx — just edit files in `frontend/src/` and changes hot-reload automatically at http://localhost:8080.

```bash
# Install a new npm package
make fe
npm install <package>
exit
```

---

## Project Structure

```
helpdesk-pro/
├── api/                        # Symfony project
│   ├── src/
│   │   ├── Ticket/             # Ticket, Comment, AuditLog
│   │   ├── User/               # User management
│   │   ├── Sla/                # Categories, SLA policies, calculator
│   │   └── Dashboard/          # Manager analytics
│   ├── tests/
│   │   ├── Unit/
│   │   ├── Integration/
│   │   └── Functional/
│   └── config/
├── frontend/                   # React project
│   └── src/
│       ├── api/                # API client functions
│       ├── components/         # Shared UI components
│       ├── contexts/           # AuthContext
│       ├── layouts/            # AppLayout
│       └── pages/              # Route pages
│           ├── reporter/
│           └── manager/
├── docker/
│   ├── php/                    # Dockerfile, php.ini, xdebug.ini, entrypoint.sh
│   └── nginx/                  # dev.conf, prod.conf
├── docs/
│   ├── spect/                  # Technical specifications
│   └── tasks/                  # Implementation task breakdown
├── docker-compose.yml
├── docker-compose.override.yml # Xdebug dev overrides
├── Makefile
└── .env.docker                 # Default env values
```

---

## API

Full OpenAPI documentation is available at **http://localhost:8080/api/docs** (Swagger UI, auto-generated by API Platform).

### Authentication

```bash
# Register (creates reporter)
POST /api/auth/register
{ "email": "...", "password": "...", "fullName": "..." }

# Login
POST /api/auth/login
{ "email": "...", "password": "..." }
# → { "token": "<jwt>" }

# Use token
Authorization: Bearer <token>
```

### Main endpoints

```
GET    /api/tickets                   List tickets (filtered by role)
POST   /api/tickets                   Create ticket (reporter)
GET    /api/tickets/{id}              Ticket detail
PATCH  /api/tickets/{id}              Update status / assignee / priority
DELETE /api/tickets/{id}              Delete (manager only)

GET    /api/tickets/{id}/comments     List comments
POST   /api/tickets/{id}/comments     Add comment

GET    /api/tickets/{id}/audit        Audit log (agent/manager)

GET    /api/categories                List categories
POST   /api/categories                Create (manager)
PATCH  /api/categories/{id}           Update (manager)
DELETE /api/categories/{id}           Delete (manager)

GET    /api/sla-policies              List SLA policies
POST   /api/sla-policies              Create (manager)

GET    /api/users                     List users (manager)
POST   /api/users                     Create user (manager)
PATCH  /api/users/{id}                Update role/status (manager)

GET    /api/dashboard/summary         Ticket stats (manager)
GET    /api/dashboard/agents          Agent workload (manager)
```

---

## SLA

Each ticket gets SLA deadlines calculated at creation from the **Category × Priority** policy matrix. Deadlines are snapshotted — editing SLA rules never affects open tickets.

| Status | Meaning |
|---|---|
| 🟢 **ok** | More than 20% of time remaining |
| 🟡 **warning** | 20% or less of time remaining |
| 🔴 **breached** | Deadline passed |

The scheduler checks for SLA violations every 15 minutes and notifies the assigned agent and manager by email.

---

## Async Messaging

All email notifications are processed asynchronously via Symfony Messenger (Doctrine transport in dev, swap for Redis/RabbitMQ in prod):

| Message | Trigger | Action |
|---|---|---|
| `TicketCreatedMessage` | Ticket created | Email to reporter + all agents |
| `TicketAssignedMessage` | Agent assigned | Email to agent |
| `TicketStatusChangedMessage` | Status changed | Email to reporter |
| `CommentAddedMessage` | Public comment added | Email to other party |
| `SlaViolatedMessage` | SLA breached (scheduler) | Email to agent + manager, audit log entry |

---

## CI

Three jobs run on every pull request:

| Job | What it checks |
|---|---|
| `lint` | PHPStan level 8 + PHP-CS-Fixer |
| `test` | PHPUnit against a real PostgreSQL database |
| `frontend` | TypeScript + Vite production build |

---

## What this project demonstrates

| Skill | How |
|---|---|
| Symfony DI & service layer | Application layer with Command/Query handlers |
| Doctrine ORM | Complex relations, migrations, Event Listeners |
| Symfony Messenger | Async messages, handlers, Doctrine transport |
| Symfony Scheduler | Cron-like SLA violation checks |
| Symfony Security / Voters | Context-aware permissions (not just role checks) |
| API Platform 3 | REST API with OpenAPI, State Providers/Processors |
| Clean Architecture | Domain / Application / Infrastructure separation |
| React 19 + TypeScript | Two interfaces, React Query, role-based routing |
| Docker | `docker compose up` for full local environment |
| CI/CD | GitHub Actions: lint, tests, frontend build |
