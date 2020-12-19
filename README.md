# Vehikl Growth Sessions

This application allows users to share and schedule their Growth Sessions.

To create / join a Growth Session, the user must login.

To avoid the registration step, this application uses oauth. 

## Local Setup

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


### Optional

#### Setup OAuth:

1. Go to github, and open your settings page
2. Open *Developer settings*
3. Create a new *OAuth App*
4. Set the homepage to `http://localhost:8000`
5. Set the callback url to `http://localhost:8000/oauth/callback`
6. Save it
7. Copy the *Client ID* to `./.env#GITHUB_CLIENT_ID` and *Client Secret* to `./.env#GITHUB_CLIENT_SECRET`

For more information, see:
 - [Laravel socialite](https://laravel.com/docs/7.x/socialite#configuration)

