#!/bin/sh

# Turn off file sharing
mutagen sync terminate growth-app

# Turn off docker
docker-compose down
