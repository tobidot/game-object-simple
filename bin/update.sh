# Update the project
git pull
# install composer dependencies
/opt/RZphp85/bin/php-cli /mnt/web506/e1/17/511267217/htdocs/composer.phar --working-dir="$(pwd)" install --no-dev --no-interaction --prefer-dist --optimize-autoloader
# run the migrations
/opt/RZphp85/bin/php-cli artisan migrate --force
# Frontend assets are not built here: Strato has no Node.js.
# They are built locally with `npm run build` and committed in public/build.
