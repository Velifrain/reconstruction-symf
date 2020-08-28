#!/usr/bin/env bash
docker-compose down \
    && docker-compose up -d --build \
    && docker-compose exec php bash