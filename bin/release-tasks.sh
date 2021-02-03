#wget https://github.com/collectiveaccess/providence/archive/1.7.9.zip && unzip 1.7.9.zip -d public
#https://github.com/collectiveaccess/providence/archive/1.7.9.zip
#git clone --depth 1 git@github.com:collectiveaccess/providence public/providence
cp config/providence-setup.php vendor/collectiveaccess/providence/setup.php
rm -Rf vendor/collectiveaccess/providence/app/tmp
mkdir -p vendor/collectiveaccess/providence/media/collectiveaccess
chmod +rw vendor/collectiveaccess/providence/media/collectiveaccess

sed -i 's/MediaUrl\Plugins/Plugins\MediaUrl/g' vendor/collectiveaccess/providence/app/lib/Plugins/MediaUrl/*.php 
sed -i 's/namespace CA/namespace CA\lib/g' vendor/collectiveaccess/providence/app/*.php 
grep -rl oldtext . | xargs sed -i 's/oldtext/newtext/g'
grep -rl "namespace CA" vendor/collectiveaccess/providence/

