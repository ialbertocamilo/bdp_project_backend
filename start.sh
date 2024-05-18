#!/usr/bin/env bash

#make an script that runs docker for run laravel app with migrations

# Build the Docker image
docker build -t bdp-app .


# Run the Docker container and apply migrations
docker-compose -f docker-compose.prod.yml up
