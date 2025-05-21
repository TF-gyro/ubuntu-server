#!/usr/bin/env bash

docker pull tribeframework/tribe

for dir in /mnt/junctions/*; do
    [[ -f $dir/docker-compose.yml ]] && $(cd $dir && docker compose up -d)
done
