#!/usr/bin/env bash

cd /var/www/html

# load all variables for this instance
slug=$( tail -n1 ./docker-tribe-slugs.txt )
vars=$( head -n1 ./logs/$slug-tribe-init.txt )
tport=$( head -n1 ./logs/$slug-tribe-port.txt )
jport=$( head -n1 ./logs/$slug-junction-port.txt )

# Setup nginx config
nginx_conf="${slug}.junction.express"
cp ./nginx/nginx.conf /etc/nginx/sites-available/$nginx_conf
sed -i "s/JUNCTION_SLUG/${slug}/g" /etc/nginx/sites-available/$nginx_conf
sed -i "s/JUNCTION_PORT/${jport}/g" /etc/nginx/sites-available/$nginx_conf
sed -i "s/TRIBE_PORT/${tport}/g" /etc/nginx/sites-available/$nginx_conf

ln -fs /etc/nginx/sites-available/$nginx_conf /etc/nginx/sites-enabled/$nginx_conf;
nginx -s reload;

# Run php script to setup docker
php /var/www/html/docker-compose-up.php "$vars"

#/usr/bin/certbot --agree-tos --no-eff-email --email tech@wildfire.world --nginx -d $slug.junction.express -d $slug.tribe.junction.express;
#nginx -s reload;

# Let the server know that process has completed
curl "https://tribe.junction.express/custom/cloudflare/dns/setup-progress.php?step=finished&slug=$slug" >/dev/null 2>&1
