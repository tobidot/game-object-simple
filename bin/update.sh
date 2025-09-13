# Update the project
git pull
# install composer dependencies
/opt/RZphp84/bin/php-cli /mnt/web506/e1/17/511267217/htdocs/composer.phar --working-dir="$(pwd)" install --no-dev --no-interaction --prefer-dist --optimize-autoloader
# run the migrations
/opt/RZphp84/bin/php-cli artisan migrate --force
# install npm dependencies
npm install --save false
# build the assets
npm run build
