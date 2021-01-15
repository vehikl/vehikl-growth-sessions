#!/bin/sh

realpath() {
    [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
}

FILE_PATH=$(realpath $0)
DIR_PATH=$(dirname $FILE_PATH)
PROJECT_PATH=$(dirname $DIR_PATH)

cd $PROJECT_PATH

# Turn off file sharing
mutagen sync terminate growth-app

# Turn off docker
docker-compose down

docker volume rm growth-sessions_project
