#wget https://github.com/collectiveaccess/providence/archive/1.7.9.zip && unzip 1.7.9.zip -d public
#https://github.com/collectiveaccess/providence/archive/1.7.9.zip
#git clone --depth 1 git@github.com:collectiveaccess/providence public/providence
#cp config/providence-setup.php vendor/collectiveaccess/providence/setup.php
#rm -Rf vendor/collectiveaccess/providence/app/tmp
#exit 1;
# from https://github.com/FriendsOfPHP/PHP-CS-Fixer
#mkdir --parents tools/php-cs-fixer
#composer require --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer

mkdir -p vendor/collectiveaccess/providence/media/collectiveaccess
chmod +rw vendor/collectiveaccess/providence/media/collectiveaccess
cd vendor/collectiveaccess/providence/ && git checkout app && cd ../../..
find vendor/collectiveaccess/providence/app -type f -exec php -r 'file_put_contents($argv[1], preg_replace("/\n *((abstract |final )?class )/", "\n$1", file_get_contents($argv[1])));' {} \;
#exit 1;
#find vendor/collectiveaccess/providence/app/lib/Export/Base*.php -type f -exec sed  -e ':a;N;$!ba;s/\n *((final|abstract)? +class)/\n$1/g' {} \;
tools/php-cs-fixer/vendor/bin/php-cs-fixer fix vendor/collectiveaccess/providence/app/ --rules=no_unused_imports
bin/console c:c
bin/console app:fix
#vendor/bin/phpstan analyze  vendor/collectiveaccess/providence/app --memory-limit=1G --level=0 --autoload-file=config/setup.php

#sed -i 's/MediaUrl\Plugins/Plugins\MediaUrl/g' vendor/collectiveaccess/providence/app/lib/Plugins/MediaUrl/*.php 
#sed -i 's/namespace CA/namespace CA\lib/g' vendor/collectiveaccess/providence/app/*.php 
#grep -rl oldtext . | xargs sed -i 's/oldtext/newtext/g'
#grep -rl "namespace CA" vendor/collectiveaccess/providence/

