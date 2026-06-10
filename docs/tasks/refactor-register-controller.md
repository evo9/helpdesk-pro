# Рефакторинг RegisterController

## Контекст

`src/User/Infrastructure/Api/Controller/RegisterController.php` содержит бизнес-логику, которой там не место:
- валидация формата входных данных (Assert\Collection прямо в контроллере)
- проверка уникальности email (`userRepository->findByEmail`)
- хеширование пароля
- создание сущности `User`

Контроллер должен быть тонким: получить данные → передать команду → вернуть ответ.

## Текущее состояние

Файл: `api/src/User/Infrastructure/Api/Controller/RegisterController.php`

Контроллер напрямую зависит от `UserRepository`, `UserPasswordHasherInterface`, `ValidatorInterface` и содержит всю логику регистрации.

## Что нужно сделать

### 1. Создать команду `api/src/User/Application/Command/RegisterUser/RegisterUserCommand.php`

```php
final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $fullName,
    ) {}
}
```

### 2. Создать хендлер `api/src/User/Application/Command/RegisterUser/RegisterUserCommandHandler.php`

Хендлер должен:
- Проверить что email не занят (через `UserRepositoryInterface`) — бросить `UserAlreadyExistsException` если занят
- Захешировать пароль через `UserPasswordHasherInterface`
- Создать `User` с ролью `UserRole::REPORTER`
- Сохранить через репозиторий
- Вернуть созданного `User`

Зависит от `UserRepositoryInterface` (интерфейс из Domain), а не от конкретного `UserRepository`.

### 3. Создать исключение `api/src/User/Domain/Exception/UserAlreadyExistsException.php`

```php
final class UserAlreadyExistsException extends \DomainException {}
```

### 4. Создать интерфейс репозитория `api/src/User/Domain/Repository/UserRepositoryInterface.php`

Если ещё не существует:
```php
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
}
```

Убедиться что `UserRepository` реализует этот интерфейс.

### 5. Облегчить контроллер `RegisterController`

После рефакторинга контроллер должен выглядеть так:

```php
final class RegisterController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    #[Route('/api/auth/register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $envelope = $this->commandBus->dispatch(new RegisterUserCommand(
                email: $data['email'] ?? '',
                password: $data['password'] ?? '',
                fullName: $data['fullName'] ?? '',
            ));

            $user = $envelope->last(HandledStamp::class)->getResult();
        } catch (UserAlreadyExistsException) {
            return new JsonResponse(
                ['errors' => ['email: This email is already registered.']],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return new JsonResponse(['token' => $this->jwtManager->create($user)], Response::HTTP_CREATED);
    }
}
```

Валидация формата (NotBlank, Email, Length) переносится на `RegisterUserCommand` через `#[Assert\*]` атрибуты. Symfony Validator проверяет их автоматически при диспатче через командную шину.

### 6. Проверить

```bash
# Успешная регистрация
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123","fullName":"Test User"}'
# → 201 {"token": "..."}

# Повторная регистрация с тем же email
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123","fullName":"Test User"}'
# → 422 {"errors": ["email: This email is already registered."]}

# Невалидные данные
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"not-an-email","password":"123","fullName":""}'
# → 422 с ошибками валидации
```

## Правила архитектуры

- `RegisterUserCommandHandler` импортирует только из `Domain` — никаких Symfony-зависимостей кроме `UserPasswordHasherInterface` (через интерфейс)
- Контроллер не знает про `UserRepository`, `ValidatorInterface`, `UserPasswordHasherInterface`
- `UserAlreadyExistsException` живёт в `Domain/Exception/` — это доменная ошибка
