#!/bin/sh

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
docker-compose run --rm -u 0 composer install

# Setup env
docker-compose run --rm artisan key:generate

# NPM Dependencies
docker-compose run --rm yarn
docker-compose run --rm yarn dev

# Database stuff
docker-compose run --rm artisan migrate
docker-compose run --rm artisan db:seed --class FakeDatabaseSeeder
