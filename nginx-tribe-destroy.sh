slug=$( tail -n1 /var/www/html/docker-tribe-destroy-slugs.txt )
rm /etc/nginx/sites-enabled/$slug.junction.express
rm /etc/nginx/sites-available/$slug.junction.express
nginx -s reload
php /var/www/html/docker-compose-down.php "app_uid=$slug"
