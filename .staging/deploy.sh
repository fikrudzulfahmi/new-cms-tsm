#!/usr/bin/env bash
# Deploy staging cms-tsm (container: staging-tsm) -> https://tsm.backupmate.biz.id
# Pemakaian: ./.staging/deploy.sh [--no-pull] [--seed]
set -euo pipefail
DIR="$(cd "$(dirname "$0")/.." && pwd)"; cd "$DIR"
NAME=tsm
CONTAINER="staging-${NAME}"
IMG="cms-${NAME}:staging"
DB="staging_cms_${NAME}"
DOMAIN="${NAME}.backupmate.biz.id"

MYSQL_ROOT_PASSWORD=$(grep -m1 '^MYSQL_ROOT_PASSWORD=' /opt/homelab/.env | cut -d= -f2-)
PULL=1; SEED=0
for a in "$@"; do [ "$a" = --no-pull ] && PULL=0; [ "$a" = --seed ] && SEED=1; done

[ "$PULL" = 1 ] && git pull --ff-only
KEYF="$DIR/.staging/app.key"
[ -f "$KEYF" ] || { printf 'base64:%s\n' "$(head -c 32 /dev/urandom | base64 | tr -d '\n')" > "$KEYF"; chmod 600 "$KEYF"; }

echo "==> build $IMG"
docker build -f Dockerfile.staging -t "$IMG" .

echo "==> database $DB"
docker exec shared-mysql mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" \
  -e "CREATE DATABASE IF NOT EXISTS \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

mkdir -p "$DIR/.staging/storage-app/public" "$DIR/.staging/storage-app/private"
docker rm -f "$CONTAINER" >/dev/null 2>&1 || true

echo "==> run $CONTAINER"
docker run -d --name "$CONTAINER" --restart unless-stopped \
  --network staging_net \
  --network-alias "staging-${NAME}" \
  --memory 192m \
  -v "$DIR/.staging/storage-app:/app/storage/app" \
  -e APP_NAME="CMS TSM" -e APP_ENV=staging -e APP_DEBUG=false \
  -e APP_KEY="$(cat "$KEYF")" -e APP_URL="https://$DOMAIN" \
  -e APP_LOCALE=id -e APP_TIMEZONE=Asia/Jakarta \
  -e LOG_CHANNEL=stderr -e LOG_LEVEL=warning \
  -e DB_CONNECTION=mysql -e DB_HOST=shared-mysql -e DB_PORT=3306 \
  -e DB_DATABASE="$DB" -e DB_USERNAME=root -e DB_PASSWORD="$MYSQL_ROOT_PASSWORD" \
  -e SESSION_DRIVER=database -e CACHE_STORE=database -e QUEUE_CONNECTION=sync \
  -e TRUSTED_PROXY_IPS=172.16.0.0/12 \
  -e "TRUSTED_HOSTS=${DOMAIN}" \
  "$IMG" >/dev/null

for i in $(seq 1 30); do [ "$(docker inspect -f '{{.State.Running}}' "$CONTAINER" 2>/dev/null)" = true ] && break; sleep 2; done
docker exec -w /app "$CONTAINER" chmod -R 777 storage/app
docker exec -w /app "$CONTAINER" php artisan storage:link --force
docker exec -w /app "$CONTAINER" php artisan migrate --force
if [ "$SEED" = 1 ] || [ "$(docker exec shared-mysql mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM \`$DB\`.users" 2>/dev/null)" = 0 ]; then
  docker exec -w /app "$CONTAINER" php artisan db:seed --force
fi
docker exec -w /app "$CONTAINER" php artisan optimize
echo "==> cek tsm: $(curl -s -o /dev/null -w '%{http_code}' -H "Host: $DOMAIN" http://127.0.0.1/) https://$DOMAIN"
