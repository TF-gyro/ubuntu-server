#!/usr/bin/env bash

# This script needs to be run as sudo;
# Make sure you have correct permissions to access restricted directories

# Function to display usage
usage() {
    echo "Usage: $0 [--jport=PORT] [--tport=PORT] [--slug=SLUG] [--root=working-dir-root] [--ssl-dir=ssl-dir-path]"
    exit 1
}

# Check if no arguments are provided
if [ "$#" -eq 0 ]; then
    echo "No arguments provided."
    usage
fi

# Parse named arguments
for arg in "$@"; do
    case $arg in
        --jport=*)
            jport="${arg#*=}"  # Extract the value after '='
            ;;
        --tport=*)
            tport="${arg#*=}"  # Extract the value after '='
            ;;
        --slug=*)
            slug="${arg#*=}"  # Extract the value after '='
            ;;
        --root=*)
            root="${arg#*=}"  # Extract the value after '='
            ;;
        --ssl-dir=*)
            sslDir="${arg#*=}"  # Extract the value after '='
            ;;
        --help)
            usage
            ;;
        *) # Unknown option
            echo "Unknown option: $arg"
            usage
            ;;
    esac
done

cd $root

# Setup nginx config
nginx_conf="${slug}.junction.express.conf"
cp ./nginx/tribe_server.conf /etc/nginx/sites-available/$nginx_conf
sed -i "s/$__SLUG/${slug}/g" /etc/nginx/sites-available/$nginx_conf
sed -i "s/$__JUNCTION_PORT/${jport}/g" /etc/nginx/sites-available/$nginx_conf
sed -i "s/$__TRIBE_PORT/${tport}/g" /etc/nginx/sites-available/$nginx_conf
sed -i "s/$__SSL_PATH/${sslDir}/g" /etc/nginx/sites-available/$nginx_conf

ln -fs /etc/nginx/sites-available/$nginx_conf /etc/nginx/sites-enabled/$nginx_conf;
nginx -t && nginx -s reload;
