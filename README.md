## Deploy CA on Heroku in 5 minutes!

```bash
git clone (this repo) && cd ca-symfony
heroku create my-cool-collection-22
heroku addons:create jawsdb
heroku config:set APP_ENV=prod
heroku config:set APP_SECRET=2442
heroku config:set ADMIN_EMAIL=tacman@gmail.com
heroku config:set APP_DISPLAY_NAME="My Cool Collection"
heroku config:set APP_TIMEZONE='America/New_York'
```
