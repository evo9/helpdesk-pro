#!/bin/sh
# docker/php/entrypoint.sh
set -e

MAX_WAIT=30
count=0

until php -r "
\$url = parse_url(getenv('DATABASE_URL') ?: 'postgresql://helpdesk:helpdesk@postgres:5432/helpdesk');
\$dsn = 'pgsql:host=' . \$url['host'] . ';port=' . (\$url['port'] ?? 5432) . ';dbname=' . ltrim(\$url['path'], '/');
try { new PDO(\$dsn, \$url['user'], \$url['pass']); exit(0); } catch (Exception \$e) { exit(1); }
" 2>/dev/null; do
  count=$((count + 1))
  if [ "$count" -ge "$MAX_WAIT" ]; then
    echo "Database not ready after ${MAX_WAIT} attempts. Aborting."
    exit 1
  fi
  echo "Waiting for database... (attempt $count/$MAX_WAIT)"
  sleep 2
done

if php bin/console list 2>/dev/null | grep -q "doctrine:migrations:migrate"; then
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec "$@"
