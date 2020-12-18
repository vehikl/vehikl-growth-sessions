FROM php:7-cli

RUN apt-get update && apt-get install -y unzip git

RUN pecl install xdebug

COPY ./ /app/
WORKDIR /app
