.PHONY: up down build sh fe migrate diff test lint cs-fix logs

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

sh:
	docker compose exec php sh

fe:
	docker compose exec frontend sh

migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

diff:
	docker compose exec php php bin/console doctrine:migrations:diff

test:
	docker compose exec php php bin/phpunit

lint:
	docker compose exec php vendor/bin/phpstan analyse
	docker compose exec php vendor/bin/php-cs-fixer fix --dry-run

cs-fix:
	docker compose exec php vendor/bin/php-cs-fixer fix

logs:
	docker compose logs -f php messenger scheduler frontend