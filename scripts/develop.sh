#!/bin/sh

# Setup env
cp .env.example .env

docker-compose build app
docker-compose up -d app files db nginx

mutagen sync create --name=growth-app ./ docker://growth-app-files/app

# Wait for files to propagate
# (this could be done by checking if they are there)
sleep 10

docker-compose run artisan key:generate

# PHP Dependencies
docker-compose run --rm composer install

# NPM Dependencies
docker-compose run yarn
docker-compose run --rm yarn dev

docker-compose run artisan migrate
docker-compose run --rm artisan db:seed --class FakeDatabaseSeeder
