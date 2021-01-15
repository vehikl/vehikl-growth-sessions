#!/bin/sh

cd ../

rm -rf node_modules
rm -rf var
rm -rf vendor
rm -rf .env

sleep 5

# Turn off file sharing
mutagen sync terminate growth-app

# Turn off docker
docker-compose down
