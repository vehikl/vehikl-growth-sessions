#!/bin/sh

cp .env.example .env

# Set up mutagen.io file share
docker-compose up -d files
mutagen sync create --name=growth-app ./ docker://growth-app-files/project

docker-compose up --build -d app
docker-compose up -d db nginx

# Setup env
docker-compose run --rm artisan key:generate


# PHP Dependencies
docker-compose run --rm composer install

# NPM Dependencies
docker-compose run --rm yarn
docker-compose run --rm yarn dev

docker-compose run --rm artisan migrate
docker-compose run --rm artisan db:seed --class FakeDatabaseSeeder
