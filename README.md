# Game Object Project Display

## Quick Start

`docker compose up -d` starts all services locally.

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
