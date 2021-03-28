# This script assumes composer has been run and installed providence in vendor/collectiveaccess
# for testing, this can also be used, reset the ca constant in .env.local
#      git clone --depth 1 git@github.com:collectiveaccess/providence public/providence

# Make sure fixer is installed:  https://github.com/FriendsOfPHP/PHP-CS-Fixer
#mkdir --parents tools/php-cs-fixer
#composer require --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer

# Makes sure the media directory exists and is writable.  In production, this should be a symlink
mkdir -p vendor/collectiveaccess/providence/media/collectiveaccess
chmod +rw vendor/collectiveaccess/providence/media/collectiveaccess

# develop-tac includes a setup.php, but the develop branch does not, use this if necessary.
#cp config/setup.php vendor/collectiveaccess/providence/setup.php

#cd vendor/collectiveaccess/providence/ && git checkout app && cd ../../..

# find vendor/collectiveaccess/providence -type f -path vendor/collectiveaccess/providence/vendor -prune -false -exec php -r 'file_put_contents($argv[1], preg_replace("/\n\s+((abstract |final |public )?(trait |class |interface ))/", "\n$1", file_get_contents($argv[1])));' {} \;

#cd vendor/collectiveaccess/providence
#find app import install support themes -type f -exec php -r 'file_put_contents($argv[1], preg_replace("/\n\s+((abstract |final |public )?(trait |class |interface ))/", "\n$1", file_get_contents($argv[1])));' {} \;
#~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix vendor/collectiveaccess/providence/app

cd vendor/collectiveaccess/providence/ && git checkout app && cd ../../..

# make sure line with class is in the first column
# find vendor/collectiveaccess/providence -type f -path vendor/collectiveaccess/providence/vendor -prune -false -exec php -r 'file_put_contents($argv[1], preg_replace("/\n\s*((abstract |final |public )?(trait |class |interface ))/", "\n$1", file_get_contents($argv[1])));' {} \;
cd vendor/collectiveaccess/providence
  #find app import install support themes -name "*.php" -exec php -r '$fn = $argv[1]; echo $fn."\n"; file_put_contents($fn, preg_replace("/\n\s*((abstract |final |public )?(trait |class |interface ))/", "\n\n$1", file_get_contents($fn)));' {} \;
find app themes support -name "*.php" -exec php -r '$fn = $argv[1]; echo $fn."\n"; file_put_contents($fn, preg_replace("/\n\s*((abstract |final |public )?(function |trait |class |interface ))/", "\n\n$1", file_get_contents($fn)));' {} \;

cd vendor/collectiveaccess/providence/ && git checkout app && cd ../../..
cd vendor/collectiveaccess/providence

# get rid of spaces before classes, so that cs-fixer works.
find app themes support -name "*.php" -exec php -r '$fn = $argv[1]; echo $fn."\n"; file_put_contents($fn, preg_replace("/\n\s*((abstract |final |public )?(function |trait |class |interface ))/", "\n\n$1", file_get_contents($fn)));' {} \;

#find app/lib/Utils -name "*.php" -exec php -r '$fn = $argv[1]; echo $fn."\n"; file_put_contents($fn, preg_replace("/\n\s*((abstract |final |public )?(trait |class |interface ))/", "\n\n$1", file_get_contents($fn)));' {} \;
~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix vendor/collectiveaccess/providence/support -vvv
~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix vendor/collectiveaccess/providence/app -vvv
~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix vendor/collectiveaccess/providence/install -vv
~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix themes -vvv
#~/tools/php-cs-fixer/vendor/bin/php-cs-fixer fix ca -vvv
#exit 1;
#find app/lib/Export/Base*.php -type f -exec sed  -e ':a;N;$!ba;s/\n *((final|abstract)? +class)/\n$1/g' {} \;
#find vendor/collectiveaccess/providence/app/lib/Export/Base*.php -type f -exec sed  -e ':a;N;$!ba;s/\n *((final|abstract)? +class)/\n$1/g' {} \;
# moves end of class } to the first column, to match beginning (above).  Can probably do more here, too.
#bin/console c:c

#cd ../../..
bin/console app:fix vendor/collectiveaccess/providence/app -vv
#bin/console app:fix vendor/collectiveaccess/providence/app -vv
#tools/php-cs-fixer/vendor/bin/php-cs-fixer fix vendor/collectiveaccess/providence/app/ --rules=no_unused_imports
#vendor/bin/phpstan analyze  vendor/collectiveaccess/providence/app --memory-limit=1G --level=0 --autoload-file=config/setup.php

#sed -i 's/MediaUrl\Plugins/Plugins\MediaUrl/g' vendor/collectiveaccess/providence/app/lib/Plugins/MediaUrl/*.php 
#sed -i 's/namespace CA/namespace CA\lib/g' vendor/collectiveaccess/providence/app/*.php 
#grep -rl oldtext . | xargs sed -i 's/oldtext/newtext/g'
#grep -rl "namespace CA" vendor/collectiveaccess/providence/

