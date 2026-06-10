# 03 — Backend: Domain Entities

Требует: 02 (Symfony setup).

Все сущности — чистые PHP-классы с Doctrine-аннотациями. **Нет Symfony-импортов в `Domain/`.**

## Subtasks

### User
- [ ] **3.1** Создать `src/User/Domain/Enum/UserRole.php` (reporter, agent, manager)
- [ ] **3.2** Создать `src/User/Domain/Entity/User.php` (id, email, password_hash, full_name, role, is_active, created_at)
- [ ] **3.3** Создать `src/User/Domain/Repository/UserRepositoryInterface.php`
- [ ] **3.4** Создать `src/User/Infrastructure/Doctrine/Repository/UserRepository.php`

### Category & SLA Policy
- [ ] **3.5** Создать `src/Sla/Domain/Entity/Category.php` (id, name, description, is_active)
- [ ] **3.6** Создать `src/Sla/Domain/Entity/SlaPolicy.php` (id, category_id FK, priority, response_hours, resolution_hours)
- [ ] **3.7** Создать `src/Sla/Domain/Enum/TicketPriority.php` (low, medium, high, critical)
- [ ] **3.8** Создать `src/Sla/Domain/Service/SlaCalculator.php` — вычисляет `response_due_at` и `resolution_due_at` по `SlaPolicy` и `created_at`
- [ ] **3.9** Создать репозитории: `CategoryRepository`, `SlaPolicyRepository`

### Ticket
- [ ] **3.10** Создать `src/Ticket/Domain/Enum/TicketStatus.php` (open, in_progress, pending, resolved, closed)
- [ ] **3.11** Создать `src/Ticket/Domain/Entity/Ticket.php` (все поля из спецификации + `sla_policy_id` snapshot)
- [ ] **3.12** Создать `src/Ticket/Domain/Repository/TicketRepositoryInterface.php`
- [ ] **3.13** Создать `src/Ticket/Infrastructure/Doctrine/Repository/TicketRepository.php`

### Comment
- [ ] **3.14** Создать `src/Ticket/Domain/Entity/Comment.php` (id, ticket_id FK, author_id FK, body, is_internal, created_at)

### AuditLog
- [ ] **3.15** Создать `src/Ticket/Domain/Entity/AuditLog.php` (id, ticket_id FK, actor_id FK, action, payload JSON, created_at)
- [ ] **3.16** Создать `src/Ticket/Infrastructure/Doctrine/Listener/TicketAuditListener.php` — `postUpdate` listener, фиксирует все изменения тикета

### Migrations
- [ ] **3.17** Сгенерировать и применить Doctrine-миграции (`make diff` → `make migrate`)
- [ ] **3.18** Проверить схему: `docker compose exec php php bin/console doctrine:schema:validate`

## Проверка

```bash
make diff       # генерирует migration без ошибок
make migrate    # применяется без ошибок
docker compose exec php php bin/console doctrine:schema:validate
```
