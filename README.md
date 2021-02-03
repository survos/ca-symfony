## Deploy CA on Heroku in 5 minutes!

```bash
git clone (this repo) && cd ca-symfony
heroku create my-cool-collection-22
# free, but hard to import
heroku addons:create jawsdb:kitefin
heroku addons:downgrade jawsdb:kitefin
# $10/month!  The free version is too small to load the profile.
heroku addons:create jawsdb:leopard

heroku config:set APP_ENV=prod
heroku config:set APP_SECRET=2442
heroku config:set ADMIN_EMAIL=tacman@gmail.com
heroku config:set APP_DISPLAY_NAME="My Cool Collection"
heroku config:set APP_TIMEZONE='America/New_York'
```

Even faster, just change the first line to something unique, then cut and paste the rest
```bash
# Clone the repo to this directory, it will also be the heroku name, so it must be unique
repo=ca-test2 

git clone git@github.com:survos/ca-symfony $repo && cd $repo && heroku create $repo && heroku addons:create jawsdb:leopard
heroku config:set APP_ENV=prod
heroku config:set APP_SECRET=2442
heroku config:set ADMIN_EMAIL=tacman@gmail.com
heroku config:set APP_DISPLAY_NAME=$repo
heroku config:set APP_TIMEZONE='America/New_York'
git push heroku master
# heroku will give you the url to open.  You'll need to follow the install link.
```

## Testing

First, load the testing database

```bash
bin/console doctrine:database:drop --env=test
bin/console doctrine:database:create --env=test
SETUP=setup-tests.php support/bin/caUtils install --profile-name testing  --admin-email tacman@gmail.com 
# someday
 
# someday


```
