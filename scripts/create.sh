#!/bin/sh

# Login in vault
brew list vault || brew install vault
read -p 'This project relies on sensitive credentials, please enter the vault token (Just press ENTER to skip this
step):' token
export VAULT_ADDR='http://159.203.15.136:8205'

if [ -z "$token" ]
then
    echo "Ignoring vault. Secrets will not be assigned."
else
    vault login $token || exit 1
fi

# Make sure mutagen is installed, or install it
brew list mutagen || brew install mutagen-io/mutagen/mutagen

realpath() {
    [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
}

FILE_PATH=$(realpath $0)
DIR_PATH=$(dirname $FILE_PATH)
PROJECT_PATH=$(dirname $DIR_PATH)

cd $PROJECT_PATH

# Create .env file from .env.example if it doesn't already exist
if test -f ".env"; then
    echo "The .env already exists. It won't be overwritten.\n"
else
    cp .env.example .env
fi

# Add github credentials
if [ -z "$token" ]
then
    echo "Skipping Github Key assignment, due to Vault Token missing."
else
    GITHUB_CLIENT_ID=$(vault kv get -field=GITHUB_CLIENT_ID kv/growth-sessions)
    GITHUB_CLIENT_SECRET=$(vault kv get -field=GITHUB_CLIENT_SECRET kv/growth-sessions)

    sed -i '' "s#GITHUB_CLIENT_ID=.*#GITHUB_CLIENT_ID=$GITHUB_CLIENT_ID#"  ./.env
    sed -i '' "s#GITHUB_CLIENT_SECRET=.*#GITHUB_CLIENT_SECRET=$GITHUB_CLIENT_SECRET#"  ./.env
fi

sail up -d
sail composer i
sail artisan key:generate

sail pnpm i
sail pnpm dev

clear

source .env
echo "The application is being served at $APP_URL"
