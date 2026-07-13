#!/usr/bin/env sh
set -eu

APP_DIR="/var/www/html"

fix_permissions() {
    mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/bootstrap/cache"

    if [ "$(id -u)" -eq 0 ]; then
        chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true
    fi

    # Keep directories group-writable; files writable by owner/group.
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \; || true
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} \; || true

    touch "$APP_DIR/storage/logs/laravel.log" || true
    chmod 664 "$APP_DIR/storage/logs/laravel.log" || true
}

run_artisan_safe() {
    command="$1"

    if [ ! -f "$APP_DIR/artisan" ]; then
        return 0
    fi

    if [ "$(id -u)" -eq 0 ]; then
        su -s /bin/sh www-data -c "cd $APP_DIR && php artisan $command" || true
    else
        (cd "$APP_DIR" && php artisan "$command") || true
    fi
}

fix_permissions
run_artisan_safe "storage:link"

if [ "${APP_ENV:-local}" != "production" ]; then
    run_artisan_safe "config:clear"
    run_artisan_safe "cache:clear"
fi

exec "$@"
