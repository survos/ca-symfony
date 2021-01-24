#wget https://github.com/collectiveaccess/providence/archive/1.7.9.zip && unzip 1.7.9.zip -d public
#https://github.com/collectiveaccess/providence/archive/1.7.9.zip
#git clone --depth 1 git@github.com:collectiveaccess/providence public/providence
#cp config/providence-setup.php public/providence-1.7.9/setup.php
rm -Rf vendor/collectiveaccess/providence/app/tmp
mkdir -p vendor/collectiveaccess/providence/media/collectiveaccess
chmod +rw vendor/collectiveaccess/providence/media/collectiveaccess

