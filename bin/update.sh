# Update the project
git pull
# install composer dependencies
/opt/RZphp82/bin/php-cli /mnt/web506/e1/17/511267217/htdocs/composer.phar --working-dir="$(pwd)" install
# run the migrations
/opt/RZphp82/bin/php-cli artisan migrate --force
# install npm dependencies
npm install
# build the assets
npm run build