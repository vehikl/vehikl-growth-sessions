#!/bin/sh

realpath() {
    [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
}

FILE_PATH=$(realpath $0)
DIR_PATH=$(dirname $FILE_PATH)
PROJECT_PATH=$(dirname $DIR_PATH)

cd $PROJECT_PATH

cp .env.example .env

# Set up mutagen.io file share
docker-compose up -d files
mutagen sync create --name=growth-app \
    --ignore-vcs \
    --ignore=".idea" \
    --default-directory-mode=777 \
    --default-file-mode=666 \
    ./ docker://growth-app-files/project

# Turn on the containers
docker-compose up --build -d app
docker-compose up -d db nginx

# PHP Dependencies
docker-compose run --rm composer install

# Setup env
docker-compose run --rm artisan key:generate

# NPM Dependencies
docker-compose run --rm yarn
docker-compose run --rm yarn dev

# Database stuff
docker-compose run --rm artisan migrate
docker-compose run --rm artisan db:seed --class FakeDatabaseSeeder
