# Рефакторинг Dashboard: перенос handlers в Application, добавление Domain layer

## Проблема

Три handler-класса живут в `Infrastructure/Doctrine/Query/` — это нарушение архитектуры. Handlers — это Application layer, они оркестрируют выборку данных. Использование Doctrine DBAL внутри — деталь реализации, которая должна быть скрыта за интерфейсом репозитория.

## Текущие файлы (переместить / переработать)

```
api/src/Dashboard/Infrastructure/Doctrine/Query/GetDashboardSummaryHandler.php
api/src/Dashboard/Infrastructure/Doctrine/Query/GetAgentWorkloadHandler.php
api/src/Dashboard/Infrastructure/Doctrine/Query/GetTicketsByCategoryHandler.php
```

Все три напрямую зависят от `Doctrine\DBAL\Connection` — инфраструктурная зависимость в неправильном слое.

## Целевая структура

```
api/src/Dashboard/
├── Domain/
│   └── Repository/
│       └── DashboardRepositoryInterface.php
├── Application/
│   └── Query/
│       ├── DashboardSummary.php               (не трогать)
│       ├── AgentWorkloadItem.php              (не трогать)
│       ├── TicketsByCategoryItem.php          (не трогать)
│       ├── GetDashboardSummaryHandler.php     ← перенести сюда
│       ├── GetAgentWorkloadHandler.php        ← перенести сюда
│       └── GetTicketsByCategoryHandler.php   ← перенести сюда
└── Infrastructure/
    ├── Doctrine/
    │   └── Repository/
    │       └── DashboardRepository.php        ← новый файл, SQL здесь
    └── Api/...                                (не трогать)
```

## Что нужно сделать

### 1. Создать `api/src/Dashboard/Domain/Repository/DashboardRepositoryInterface.php`

```php
namespace App\Dashboard\Domain\Repository;

use App\Dashboard\Application\Query\AgentWorkloadItem;
use App\Dashboard\Application\Query\DashboardSummary;
use App\Dashboard\Application\Query\TicketsByCategoryItem;

interface DashboardRepositoryInterface
{
    public function getSummary(): DashboardSummary;

    /** @return AgentWorkloadItem[] */
    public function getAgentWorkload(): array;

    /** @return TicketsByCategoryItem[] */
    public function getTicketsByCategory(): array;
}
```

### 2. Создать `api/src/Dashboard/Infrastructure/Doctrine/Repository/DashboardRepository.php`

Перенести весь SQL из трёх handler-классов сюда. Реализует `DashboardRepositoryInterface`. Зависит от `Doctrine\DBAL\Connection`.

### 3. Переписать handlers в `Application/Query/`

Каждый handler теперь зависит от `DashboardRepositoryInterface`, а не от `Connection` напрямую:

```php
// Application/Query/GetDashboardSummaryHandler.php
final class GetDashboardSummaryHandler
{
    public function __construct(
        private readonly DashboardRepositoryInterface $repository,
    ) {}

    public function __invoke(): DashboardSummary
    {
        return $this->repository->getSummary();
    }
}
```

Аналогично для `GetAgentWorkloadHandler` и `GetTicketsByCategoryHandler`.

### 4. Удалить старые файлы

```bash
rm api/src/Dashboard/Infrastructure/Doctrine/Query/GetDashboardSummaryHandler.php
rm api/src/Dashboard/Infrastructure/Doctrine/Query/GetAgentWorkloadHandler.php
rm api/src/Dashboard/Infrastructure/Doctrine/Query/GetTicketsByCategoryHandler.php
rmdir api/src/Dashboard/Infrastructure/Doctrine/Query/
```

### 5. Обновить Providers

Проверить `Infrastructure/Api/Provider/DashboardSummaryProvider.php`, `AgentWorkloadProvider.php`, `TicketsByCategoryProvider.php` — если они напрямую инжектируют старые handlers, обновить на новые из Application layer.

### 6. Проверить

```bash
make lint   # PHPStan не должен ругаться на зависимости слоёв
docker compose exec php php bin/console cache:clear

# API должен отвечать
curl http://localhost:8080/api/dashboard/summary \
  -H "Authorization: Bearer <manager_token>"
```

## Правила после рефакторинга

- `Application/Query/*Handler` зависит только от `Domain` (интерфейс репозитория)
- `Infrastructure/Doctrine/Repository/DashboardRepository` зависит от `Connection` — и это единственное место в Dashboard, где есть Doctrine
- Providers в `Infrastructure/Api/` инжектируют handlers из Application layer
