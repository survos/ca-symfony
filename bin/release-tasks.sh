#wget https://github.com/collectiveaccess/providence/archive/1.7.9.zip && unzip 1.7.9.zip -d public
#https://github.com/collectiveaccess/providence/archive/1.7.9.zip
#git clone --depth 1 git@github.com:collectiveaccess/providence public/providence
#cp config/setup.php vendor/collectiveaccess/providence/setup.php
#rm -Rf vendor/collectiveaccess/providence/app/tmp
#exit 1;
# from https://github.com/FriendsOfPHP/PHP-CS-Fixer
#mkdir --parents tools/php-cs-fixer
#composer require --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer

mkdir -p vendor/collectiveaccess/providence/media/collectiveaccess
chmod +rw vendor/collectiveaccess/providence/media/collectiveaccess
#cd vendor/collectiveaccess/providence/ && git checkout app && cd ../../..
# hide the vendor directory (@todo: find with exclude)
# make sure line with class is in the first column
# find vendor/collectiveaccess/providence -type f -path vendor/collectiveaccess/providence/vendor -prune -false -exec php -r 'file_put_contents($argv[1], preg_replace("/\n\s+((abstract |final |public )?(trait |class |interface ))/", "\n$1", file_get_contents($argv[1])));' {} \;
#cd vendor/collectiveaccess/providence
#find app import install support themes -type f -exec php -r 'file_put_contents($argv[1], preg_replace("/\n\s+((abstract |final |public )?(trait |class |interface ))/", "\n$1", file_get_contents($argv[1])));' {} \;
#~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix vendor/collectiveaccess/providence/app
cd vendor/collectiveaccess/providence/ && git checkout app && cd ../../..
# hide the vendor directory (@todo: find with exclude)

# make sure line with class is in the first column
# find vendor/collectiveaccess/providence -type f -path vendor/collectiveaccess/providence/vendor -prune -false -exec php -r 'file_put_contents($argv[1], preg_replace("/\n\s*((abstract |final |public )?(trait |class |interface ))/", "\n$1", file_get_contents($argv[1])));' {} \;
cd vendor/collectiveaccess/providence
  #find app import install support themes -name "*.php" -exec php -r '$fn = $argv[1]; echo $fn."\n"; file_put_contents($fn, preg_replace("/\n\s*((abstract |final |public )?(trait |class |interface ))/", "\n\n$1", file_get_contents($fn)));' {} \;
find app themes support -name "*.php" -exec php -r '$fn = $argv[1]; echo $fn."\n"; file_put_contents($fn, preg_replace("/\n\s*((abstract |final |public )?(function |trait |class |interface ))/", "\n\n$1", file_get_contents($fn)));' {} \;
#find app/lib/Utils -name "*.php" -exec php -r '$fn = $argv[1]; echo $fn."\n"; file_put_contents($fn, preg_replace("/\n\s*((abstract |final |public )?(trait |class |interface ))/", "\n\n$1", file_get_contents($fn)));' {} \;
~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix app -vvv
~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix support -vvv
~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix themes -vvv
~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix ca -vvv
#exit 1;
#find vendor/collectiveaccess/providence/app/lib/Export/Base*.php -type f -exec sed  -e ':a;N;$!ba;s/\n *((final|abstract)? +class)/\n$1/g' {} \;
# moves end of class } to the first column, to match beginning (above).  Can probably do more here, too.
#bin/console c:c

#cd ../../..
bin/console app:fix vendor/collectiveaccess/providence/app -vv
=======
#bin/console app:fix vendor/collectiveaccess/providence/app -vv
#tools/php-cs-fixer/vendor/bin/php-cs-fixer fix vendor/collectiveaccess/providence/app/ --rules=no_unused_imports
#vendor/bin/phpstan analyze  vendor/collectiveaccess/providence/app --memory-limit=1G --level=0 --autoload-file=config/setup.php

#sed -i 's/MediaUrl\Plugins/Plugins\MediaUrl/g' vendor/collectiveaccess/providence/app/lib/Plugins/MediaUrl/*.php 
#sed -i 's/namespace CA/namespace CA\lib/g' vendor/collectiveaccess/providence/app/*.php 
#grep -rl oldtext . | xargs sed -i 's/oldtext/newtext/g'
#grep -rl "namespace CA" vendor/collectiveaccess/providence/

