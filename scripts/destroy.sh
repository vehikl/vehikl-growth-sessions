#!/bin/sh

# Turn off file sharing
mutagen sync terminate growth-app

# Remove files from the images
docker-compose exec files rm -rf /project/.*
docker-compose exec files rm -rf /project/*

# Turn off docker
docker-compose down
