#!/usr/bin/env bash
set -euo pipefail

APP="${APP_CONTAINER:-docker-app-1}"
NGINX="${NGINX_CONTAINER:-docker-nginx-1}"
DB="${DB_CONTAINER:-docker-db-1}"
REDIS="${REDIS_CONTAINER:-docker-redis-1}"
PMA="${PMA_CONTAINER:-docker-phpmyadmin-1}"

is_running() {
  docker ps --format '{{.Names}}' | grep -qx "$1"
}

pick_shell() {
  local c="$1"
  if docker exec "$c" sh -lc 'command -v bash >/dev/null 2>&1'; then
    echo bash
  else
    echo sh
  fi
}

enter() {
  local c="$1"
  if ! is_running "$c"; then
    echo "❌ Container não está rodando: $c"
    return 1
  fi
  local sh
  sh="$(pick_shell "$c")"
  echo "➡️ Entrando em $c ($sh)..."
  docker exec -it "$c" "$sh"
}

logs() {
  local c="$1"
  if ! is_running "$c"; then
    echo "❌ Container não está rodando: $c"
    return 1
  fi
  docker logs --tail=200 -f "$c"
}

mysql_cli() {
  if ! is_running "$DB"; then
    echo "❌ Container do banco não está rodando: $DB"
    return 1
  fi
  echo "➡️ Abrindo MySQL (vai pedir senha do root)..."
  docker exec -it "$DB" mysql -uroot -p
}

menu() {
  cat <<MENU

=== Docker Menu ===
1) Acessar APP (Laravel/PHP)     [$APP]
2) Acessar NGINX                [$NGINX]
3) Acessar DB (shell)           [$DB]
4) Acessar MySQL (cliente)      [$DB]
5) Acessar REDIS                [$REDIS]
6) Acessar phpMyAdmin (shell)   [$PMA]

7) Logs APP
8) Logs NGINX
9) Logs DB
10) docker ps

0) Sair
MENU
}

while true; do
  menu
  read -rp "Escolha: " opt
  case "${opt:-}" in
    1) enter "$APP" ;;
    2) enter "$NGINX" ;;
    3) enter "$DB" ;;
    4) mysql_cli ;;
    5) enter "$REDIS" ;;
    6) enter "$PMA" ;;
    7) logs "$APP" ;;
    8) logs "$NGINX" ;;
    9) logs "$DB" ;;
    10) docker ps ;;
    0) exit 0 ;;
    *) echo "Opção inválida." ;;
  esac
done
