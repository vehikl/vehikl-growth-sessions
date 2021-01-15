#!/bin/sh

realpath() {
    [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
}

FILE_PATH=$(realpath $0)
DIR_PATH=$(dirname $FILE_PATH)
PROJECT_PATH=$(dirname $DIR_PATH)

cd $PROJECT_PATH

rm -rf node_modules
rm -rf var
rm -rf vendor
rm -rf .env

sleep 5

# Turn off file sharing
mutagen sync terminate growth-app

# Turn off docker
docker-compose down
