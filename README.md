# Vehikl Growth Sessions

This application allows users to share and schedule their Growth Sessions.

To create / join a Growth Session, the user must login.

To avoid the registration step, this application uses oauth. 

## Docker Setup

### Prerequisites

 - Docker for Mac: https://www.docker.com/products/docker-desktop

### Setup

#### Initial configuration

##### Option 1 - Via the create script

```sh
sh scripts/create.sh
```

> **Note**
> The create script populates OAuth credentials through a secure vault. You can skip this through pressing ENTER when
> prompted.

##### Option 2 - Manually

```sh
# PHP Dependencies (This is important just to have sail)
composer install

# Setup env
cp .env.example .env

# Start sail containers
sail up -d

# Install php dependencies through sail
sail composer i

# Initially App key
sail artisan key:generate

## Initialize database
sail artisan migrate --seed

# Start frontend
sail pnpm i
sail pnpm dev

# The app will be served on http://localhost:8008 by default
# The port may be changed via the APP_PORT env variable  
```

## Running Tests

```sh 
# Frontend
sail pnpm test

# Backend
sail artisan test

# To run these tests through the IDE, remember to configure it to use Remote Interpreters (for both PHP and Node)
```

## Optional

#### Setup Github OAuth:

1. Go to github, and open your settings page
2. Open *Developer settings*
3. Create a new *OAuth App*
4. Set the homepage to `http://localhost:8008`
5. Set the callback url to `http://localhost:8008/oauth/github/callback`
6. Save it
7. Copy the *Client ID* to `./.env#GITHUB_CLIENT_ID` and *Client Secret* to `./.env#GITHUB_CLIENT_SECRET`

#### Setup Google OAuth:

1. Go to your [Google Developer console](https://console.developers.google.com)
2. Select a Project to add OAuth to (or create a new project)
3. Configure your Oauth Consent Screen
4. Create OAuth Credentials for the project
    1. Select `Web Application` as the _Application Type_
    2. Give your application a _Name_
    3. Add `http://localhost:8008/oauth/google/callback` as a _Redirect URI_
    4. Click _Create_
5. Copy the *Client ID* to `./.env#GOOGLE_CLIENT_ID` and *Client Secret* to `./.env#GOOGLE_CLIENT_SECRET`
6. Set `./.env#GOOGLE_REDIRECT_URL` to `http://localhost:8008/oauth/google/callback`

For more information, see:
 - [Laravel socialite](https://laravel.com/docs/7.x/socialite#configuration)

