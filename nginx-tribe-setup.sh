vars=$( awk 'END { print }' /var/www/html/docker-tribe-init.txt )
slug=$( awk 'END { print }' /var/www/html/docker-tribe-slugs.txt )
tport=$( awk 'END { print }' /var/www/html/docker-tribe-ports.txt )
jport=$( awk 'END { print }' /var/www/html/docker-junction-ports.txt )
sed -i '$ d' /var/www/html/docker-tribe-init.txt
sed -i '$ d' /var/www/html/docker-tribe-slugs.txt
sed -i '$ d' /var/www/html/docker-tribe-ports.txt
sed -i '$ d' /var/www/html/docker-junction-ports.txt

echo "server {" >> /etc/nginx/sites-available/$slug.junction.express
echo "    listen 80;" >> /etc/nginx/sites-available/$slug.junction.express
echo "" >> /etc/nginx/sites-available/$slug.junction.express
echo "    server_name  tribe.$slug.junction.express;" >> /etc/nginx/sites-available/$slug.junction.express
echo "" >> /etc/nginx/sites-available/$slug.junction.express
echo "    location / {" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_pass http://localhost:$tport/;" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_set_header Host \$host;" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_set_header X-Real-IP \$remote_addr;" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_set_header X-Forwarded-Proto \$scheme;" >> /etc/nginx/sites-available/$slug.junction.express
echo "    }" >> /etc/nginx/sites-available/$slug.junction.express
echo "}" >> /etc/nginx/sites-available/$slug.junction.express
echo "" >> /etc/nginx/sites-available/$slug.junction.express
echo "server {" >> /etc/nginx/sites-available/$slug.junction.express
echo "    listen 80;" >> /etc/nginx/sites-available/$slug.junction.express
echo "" >> /etc/nginx/sites-available/$slug.junction.express
echo "    server_name  $slug.junction.express;" >> /etc/nginx/sites-available/$slug.junction.express
echo "" >> /etc/nginx/sites-available/$slug.junction.express
echo "    location / {" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_pass http://localhost:$jport/;" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_set_header Host \$host;" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_set_header X-Real-IP \$remote_addr;" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;" >> /etc/nginx/sites-available/$slug.junction.express
echo "        proxy_set_header X-Forwarded-Proto \$scheme;" >> /etc/nginx/sites-available/$slug.junction.express
echo "    }" >> /etc/nginx/sites-available/$slug.junction.express
echo "}" >> /etc/nginx/sites-available/$slug.junction.express

ln -s /etc/nginx/sites-available/$slug.junction.express /etc/nginx/sites-enabled/$slug.junction.express;
nginx -s reload;
certbot --agree-tos --no-eff-email --email tech@wildfire.world --nginx -d $slug.junction.express -d tribe.$slug.junction.express;
nginx -s reload;

php /var/www/html/docker-tribe-setup.php "$vars"