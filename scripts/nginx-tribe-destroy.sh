slug=$( tail -n1 /var/www/html/docker-tribe-destroy-slugs.txt )
rm /etc/nginx/sites-enabled/$slug.truearch.io
rm /etc/nginx/sites-available/$slug.truearch.io
nginx -s reload
php /var/www/html/docker-tribe-remove.php "app_uid=$slug"
