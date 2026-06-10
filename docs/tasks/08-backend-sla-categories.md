# 08 — Backend: Categories & SLA Policies API

Требует: 03 (Domain entities).

## Subtasks

### Categories
- [ ] **8.1** Создать `src/Sla/Infrastructure/Api/Resource/CategoryResource.php`
- [ ] **8.2** CRUD-операции для категорий (GET collection/item — все; POST/PATCH/DELETE — только manager)
- [ ] **8.3** Создать `src/Sla/Application/Command/CreateCategory.php` + handler
- [ ] **8.4** Создать `src/Sla/Application/Command/UpdateCategory.php` + handler

### SLA Policies
- [ ] **8.5** Создать `src/Sla/Infrastructure/Api/Resource/SlaPolicyResource.php`
- [ ] **8.6** CRUD-операции для SLA-политик (GET — agent/manager; POST/PATCH/DELETE — только manager)
- [ ] **8.7** Создать `src/Sla/Application/Command/CreateSlaPolicy.php` + handler
- [ ] **8.8** Создать `src/Sla/Application/Command/UpdateSlaPolicy.php` + handler
- [ ] **8.9** Написать тест: изменение SlaPolicy не влияет на уже созданные тикеты (snapshot-инвариант)
