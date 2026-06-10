# 07 — Backend: Users API (Manager only)

Требует: 04 (Auth).

## Subtasks

- [ ] **7.1** Создать `src/User/Infrastructure/Api/Resource/UserResource.php`
- [ ] **7.2** Создать `src/User/Infrastructure/Api/Provider/UserStateProvider.php` — список пользователей (только manager)
- [ ] **7.3** Создать `src/User/Infrastructure/Api/Processor/CreateUserProcessor.php` — создание пользователя с произвольной ролью (только manager)
- [ ] **7.4** Создать `src/User/Infrastructure/Api/Processor/UpdateUserProcessor.php` — смена роли и `is_active` (только manager)
- [ ] **7.5** Создать `src/User/Infrastructure/Security/Voter/UserVoter.php`
- [ ] **7.6** Создать `src/User/Application/Command/CreateUser.php` + handler (хеширует пароль)
- [ ] **7.7** Создать `src/User/Application/Command/ChangeUserRole.php` + handler
- [ ] **7.8** Написать тесты: reporter/agent не могут вызвать `/api/users`
