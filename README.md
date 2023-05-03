# Game Object Project Display

## Deploy

The project is synced to git and the server just pulls the latest version.

### Quick Deploy

Go to strato server and run the update script at `./bin/update.sh`.

### Server Details

The update script
```bash
bash
git pull && composer install && php artisan migrate --force && npm install && npm run build
```

php cli ist `/opt/RZphp82/bin/php-cli`
to user composer run `/opt/RZphp82/bin/php-cli /mnt/web506/e1/17/511267217/htdocs/composer.phar --working-dir="$(pwd)"`


Thanks to for sharing how to install node in a strato managed server:
https://www.tricd.de/server/node-und-npm-auf-einem-strato-managed-server-installieren/
