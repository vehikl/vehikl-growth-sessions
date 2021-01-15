# Vehikl Growth Sessions

This application allows users to share and schedule their Growth Sessions.

To create / join a Growth Session, the user must login.

To avoid the registration step, this application uses oauth. 

## Local Setup

### Prerequisites

 - PHP 7.4
 - Composer
 - Node >= 12 LTS
 - Yarn
 - Mailtrap

### Setup

#### Initial configuration

```sh
# PHP Dependencies
composer install
# NPM Dependencies
yarn
# Setup env
cp .env.example .env
php artisan key:generate
```

#### Configuring the database

For ease of local development you can set the database to sqlite:

```diff
diff --git a/.env b/.env
index b0303cd..d7983fd 100644
--- a/.env.example
+++ b/.env.example
@@ -6,12 +6,7 @@ APP_URL=http://localhost

LOG_CHANNEL=stack

-DB_CONNECTION=mysql
-DB_HOST=127.0.0.1
-DB_PORT=3306
-DB_DATABASE=laravel
-DB_USERNAME=root
-DB_PASSWORD=
+DB_CONNECTION=sqlite

BROADCAST_DRIVER=log
CACHE_DRIVER=file
```

Once that is set up:

```sh
touch database/database.sqlite
php artisan migrate && php artisan db:seed --class FakeDatabaseSeeder (To have some pre-made fake mobs in your calendar)
```

OBS: For production, the initial migration and seeding can be done with the following command
```sh
php artisan migrate --seed
```

## Docker Setup

### Prerequisites

 - Docker for Mac: https://www.docker.com/products/docker-desktop
 - Mutagen: https://mutagen.io/

### Setup

#### Initial configuration

```sh
sh scripts/create.sh
```

#### Run

The site should be available at http://localhost:8000


## Optional

#### Setup Github OAuth:

1. Go to github, and open your settings page
2. Open *Developer settings*
3. Create a new *OAuth App*
4. Set the homepage to `http://localhost:8000`
5. Set the callback url to `http://localhost:8000/oauth/github/callback`
6. Save it
7. Copy the *Client ID* to `./.env#GITHUB_CLIENT_ID` and *Client Secret* to `./.env#GITHUB_CLIENT_SECRET`

#### Setup Google OAuth:

1. Go to your [Google Developer console](https://console.developers.google.com)
2. Select a Project to add OAuth to (or create a new project)
3. Configure your Oauth Consent Screen
4. Create OAuth Credentials for the project
    1. Select `Web Application` as the _Application Type_
    2. Give your application a _Name_
    3. Add `http://localhost:8000/oauth/google/callback` as a _Redirect URI_
    4. Click _Create_
5. Copy the *Client ID* to `./.env#GOOGLE_CLIENT_ID` and *Client Secret* to `./.env#GOOGLE_CLIENT_SECRET`
6. Set `./.env#GOOGLE_REDIRECT_URL` to `http://localhost:8000/oauth/google/callback`

For more information, see:
 - [Laravel socialite](https://laravel.com/docs/7.x/socialite#configuration)

