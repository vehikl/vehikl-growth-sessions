# Vehikl Growth Sessions

This application allows users to share and schedule their Growth Sessions.

To create / join a Growth Session, the user must login.

To avoid the registration step, this application uses oauth. 

## Docker Setup

### Prerequisites

 - Docker for Mac: https://www.docker.com/products/docker-desktop

### Setup

#### Initial configuration

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

### Setting up the Slack Integration

You will need slack admin privileges, as far as I can tell.

#### Setup Bot OAuth

1. Open Slack `Admin` gear on the furthest right drawer menu (under Home, DMs, Activity, Files, Later, and More button-tabs)
2. Click Apps & Workflows (will open slack in the browser)
3. In the browser, on the top navigation, click `Build`
4. Click `Create New App` and pick `From Scratch`
5. Click `OAuth & Permissions` in the left menu
6. Copy `Bot User OAuth Token` and put it in the env as `SLACK_CHAT_TOKEN`
7. Under `Bot Token Scopes`, you will need to add `chat:write`

#### Get the channel ID

Using `#channel-name` works for posting new messages, but not for updating those messages, so it's better to get the internal slack id.

1. In slack, open the channel you want to use
2. In the vertical ellipsis menu on the top right of the channel view, click `Open channel details`
3. At the very bottom of the modal, there is some text `Channel ID: C________`, copy that ID, and use it in env `SLACK_CHAT_CHANNEL`

#### Send the bot into your channel

If you don't do this step, it's possible your messages will send failing with an error about the channel being invalid

1. In slack, open the channel you want to use
2. In the vertical ellipsis menu on the top right of the channel view, click `Open channel details`
3. In the modal, click the `Integrations` tab
4. In the `Apps` section, click `Add apps`
5. Find your app in the list, and click `Add`

### Authentication in local environment

You can use `php artisan dev-mode:login-as` to generate a login link for a user while in the local development environment.
There is an optional argument that accepts the github nickname, and without the argument, you are presented with a list of current users.

## Additional Notes

- Statistics can be seen on `/statistics`
- By default, the statistics will only reveal which users have a given member has not yet mobbed with.
- Additional statistics can be shown with `/statistics?full-display`
