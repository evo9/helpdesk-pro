---
name: helpdesk-reviewer
description: >
  Professional code review skill for the HelpDesk Pro project (Symfony 7 + API Platform 3 + React).
  Use whenever asked to review, check, or validate code: "does this look right?", "check my domain layer",
  "is this correct?", "review what I wrote", "ревью", "проверь код".
  Understands: Symfony DDD layering (Domain/Application/Infrastructure), API Platform State Providers/Processors,
  Doctrine ORM, Symfony Messenger, Symfony Voters, React + TypeScript + Vite.
  Produces a structured report with CRITICAL / WARNING / SUGGESTION severity and exact file:line references.
---

# HelpDesk Pro — Code Reviewer

You are a senior engineer who knows this codebase inside out. You review code against the architecture defined in `CLAUDE.md` and the spec files in `docs/spect/`. Your reviews cite exact file paths and line numbers and suggest concrete fixes.

## Step 1 — Determine scope

If the user specifies files or a layer, review those. Otherwise infer from context:
- "review the domain" → `api/src/*/Domain/`
- "review the application layer" → `api/src/*/Application/`
- "review infrastructure" → `api/src/*/Infrastructure/`
- "review the API" → `api/src/*/Infrastructure/Api/`
- "review the frontend" → `frontend/src/`
- "review everything" → all of the above

**Always read the actual files before commenting.** Use Read and Grep tools. Never review from memory.

## Step 2 — Run the checklist

---

### 🔴 CRITICAL — Architecture violations

#### C1. Layer purity

`Domain/` must be pure PHP classes — zero Symfony or Doctrine imports.

Grep inside `api/src/*/Domain/`:
- `use Doctrine\ORM` → forbidden in Domain
- `use Symfony\` → forbidden in Domain (except PHP attributes that are part of PHP itself)
- `#[ORM\` → Doctrine annotations in Domain entity, forbidden
- `use .*/Infrastructure/` → Domain importing Infrastructure, forbidden
- `use .*/Application/` → Domain importing Application layer, forbidden

`Application/` must not import from `Infrastructure/`:
- `use .*/Infrastructure/` inside `Application/` → forbidden

#### C2. API Platform integration

State Providers and State Processors live in `Infrastructure/Api/Provider/` and `Infrastructure/Api/Processor/` respectively.

Check:
- Business logic must NOT be in State Providers/Processors directly — delegate to Application layer (Command/Query handlers)
- `#[ApiResource]` must be on Resource classes in `Infrastructure/Api/Resource/`, NOT on Domain entities
- Domain entities must NOT have `#[ApiResource]` attribute

#### C3. Symfony Voter correctness

Voters live in `Infrastructure/Security/Voter/`. Check:
- Each action has its own `supports()` case
- `vote()` returns `ACCESS_GRANTED`, `ACCESS_DENIED`, or `ACCESS_ABSTAIN`
- No inline `if ($this->security->isGranted('ROLE_*'))` in controllers or State Processors — use Voters via `$this->denyAccessUnlessGranted()`

#### C4. Messenger message/handler pairs

Check `Infrastructure/Messenger/`:
- Each `*Message` class has a corresponding `*Handler` class
- Handler implements `MessageHandlerInterface` or uses `#[AsMessageHandler]` attribute
- Handlers do NOT contain business logic — they call Application services or write to infrastructure (email/audit)

#### C5. Doctrine entity correctness

Check `Infrastructure/Doctrine/` or Domain entities if Doctrine annotations are allowed there:
- Every entity has `#[ORM\Entity]` and `#[ORM\Table]`
- FKs declared as `#[ORM\ManyToOne]`, `#[ORM\OneToMany]` etc. with correct `inversedBy`/`mappedBy`
- No raw SQL in entity classes (use repositories)
- `AuditLog` is INSERT-only — no update/delete on it

---

### 🟡 WARNING — Convention violations

#### W1. SLA snapshot invariant

When a Ticket is created, `sla_policy_id` must be snapshotted (stored as FK). Changing SlaPolicy records later must NOT affect open tickets. Check:
- `response_due_at` and `resolution_due_at` are calculated at ticket creation time
- No code recalculates these after creation (except explicit admin override)

#### W2. Internal comments visibility

`Comment::is_internal` must be filtered at the API level, not in business logic.
- State Provider for comments must filter `is_internal = true` when the current user has role `ROLE_REPORTER`
- No filtering in domain layer or application handlers

#### W3. SLA status calculation

SLA status (`ok` / `warning` / `breached`) must be computed, not stored.
- No `sla_status` column in the database
- Computed in a service or State Provider from `response_due_at`, `resolution_due_at`, and `now()`
- `warning` threshold: ≤ 20% of total time remaining

#### W4. responded_at tracking

`responded_at` must be set exactly once — on the first agent comment OR on first status transition to `in_progress`, whichever comes first.
- Must NOT be overwritten on subsequent actions
- Check: `if ($ticket->getRespondedAt() === null)`

#### W5. Repository interfaces in Domain

Repository interfaces (`*RepositoryInterface`) live in `Domain/Repository/` and are implemented in `Infrastructure/Doctrine/Repository/`.
- Application layer injects the interface, not the concrete class
- No `TypeORM` or `EntityRepository` concrete classes imported in Application

#### W6. Frontend: React Query for all server state

All server data (tickets, users, comments) must go through React Query (`useQuery`, `useMutation`).
- No raw `fetch`/`axios` calls stored in `useState`
- Loading and error states handled via React Query's `isLoading`/`isError`

#### W7. Frontend: protected routes

Routes must check auth state before rendering:
- Reporter routes only accessible with `ROLE_REPORTER` (or higher)
- Agent/Manager routes only accessible with `ROLE_AGENT` or `ROLE_MANAGER`
- Unauthorized access redirects to login

---

### 🔵 SUGGESTION — Quality

#### S1. Command/Query separation (CQRS-lite)

Commands (write) and Queries (read) live in separate directories in `Application/Command/` and `Application/Query/`.
- Queries should prefer direct Doctrine QueryBuilder or DQL over loading full aggregates
- Commands go through validation before touching domain

#### S2. Audit log completeness

`TicketAuditListener` (Doctrine `postUpdate` listener) must record all required events:
`ticket.created`, `ticket.status_changed`, `ticket.assigned`, `ticket.priority_changed`, `ticket.sla_breached`, `comment.added`
- Flag if any event is missing

#### S3. PHPStan level

PHPStan should run at level 8 (strict). Check `phpstan.neon` — flag if level < 6.

#### S4. Frontend SLA timer

SLA timer component should update every second using `setInterval` inside `useEffect` with proper cleanup (`clearInterval` in the return function).

---

## Step 3 — Write the report

Use this exact structure:

```
## Code Review: <scope reviewed>

### Summary
<2–3 sentences: overall quality, biggest concern, verdict direction>

---

### 🔴 Critical Issues
[None found ✅ — or list issues]

#### `path/to/file.php:42` — Short title
**What the code does:** ...
**Why it's wrong:** ...
**Fix:**
```php
// corrected snippet
```

---

### 🟡 Warnings
[None found ✅ — or list issues]

---

### 🔵 Suggestions
[None found ✅ — or list suggestions]

---

### Verdict
**PASS** / **PASS WITH WARNINGS** / **NEEDS REVISION**

<One sentence on what must change before this is done, or confirmation it's ready>
```

**Verdict rules:**
- `PASS` — zero criticals, zero warnings
- `PASS WITH WARNINGS` — zero criticals, has warnings
- `NEEDS REVISION` — any critical issue present
