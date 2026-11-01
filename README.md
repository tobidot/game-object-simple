# Game Object Project Display

## Quick Start

`docker compose up -d` starts all services locally. The app uses four PHP development-server workers by default; set `PHP_CLI_SERVER_WORKERS` in `.env` to change the count.

The app runs with Laravel's server reload watcher disabled so PHP workers can serve requests concurrently. Restart the app after changing `.env` values, and recreate it after changing Compose configuration:

```bash
docker compose restart app
docker compose up -d --force-recreate app
```

CLI OPcache is enabled with timestamp validation and immediate revalidation, so PHP changes remain visible during local development.

## Deploy

The project is synced to git and the server just pulls the latest version.

### Quick Deploy

Go to Strato server and run the update script at `./bin/update.sh`.

### Server Details

The update script
```bash
git pull && composer install --no-dev && php artisan migrate --force
```

- php cli is at `/opt/RZphp85/bin/php-cli`
- to use composer run `/opt/RZphp85/bin/php-cli /mnt/web506/e1/17/511267217/htdocs/composer.phar --working-dir="$(pwd)"`

### Frontend assets

The Strato server has no Node.js, so assets are **not** built on the server.
Build them locally and commit the output before pushing:

```bash
npm run build
git add public/build
```

Forgetting this deploys stale (or missing) JS/CSS, since the server serves `public/build` as committed.

## Notes

Thanks to for sharing how to install node in a strato managed server:
https://www.tricd.de/server/node-und-npm-auf-einem-strato-managed-server-installieren/
