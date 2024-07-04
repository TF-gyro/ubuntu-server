# change the following line manually and run "bash install.sh"
servername="j0.wildfire.world"

# use following line in "incrontab -e"
# /var/www/html/docker-tribe-slugs.txt  IN_MODIFY bash /var/www/html/nginx-tribe-setup.sh

# no changes after this line
# --------

export DEBIAN_FRONTEND=noninteractive

apt-get -yq update
apt-get -yq upgrade

apt-get -yq install swapspace lsb-release ca-certificates curl php php-cgi php-fpm apt-transport-https software-properties-common nginx python3-pip python3-certbot-nginx net-tools zip unzip p7zip-full build-essential curl s3cmd htop imagemagick ffmpeg poppler-utils inotify-tools incron

ufw allow OpenSSH
ufw allow Postfix
ufw allow 80
ufw allow 8080
ufw allow 3000
ufw allow 443
ufw allow 587
yes | ufw enable

install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null
apt-get update
apt-get -yq install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
docker pull tribeframework/tribe:latest

mkdir /var/www/html/logs;
chown www-data: /var/www/html -R
chmod 755 /var/www/html

echo "root" >> /etc/incron.allow
systemctl start incron.service

rm /etc/nginx/sites-available/default
rm /etc/nginx/sites-enabled/default

echo "server {" >> /etc/nginx/sites-available/$servername
echo "    listen 80;" >> /etc/nginx/sites-available/$servername
echo "" >> /etc/nginx/sites-available/$servername
echo "    server_name $servername;" >> /etc/nginx/sites-available/$servername
echo "" >> /etc/nginx/sites-available/$servername
echo "    root /var/www/html;" >> /etc/nginx/sites-available/$servername
echo "" >> /etc/nginx/sites-available/$servername
echo "    access_log  /var/www/html/logs/access.log;" >> /etc/nginx/sites-available/$servername
echo "    error_log  /var/www/html/logs/error.log;" >> /etc/nginx/sites-available/$servername
echo "" >> /etc/nginx/sites-available/$servername
echo "    index index.html index.htm index.php;" >> /etc/nginx/sites-available/$servername
echo "" >> /etc/nginx/sites-available/$servername
echo "    location ~ /\.(?!well-known).* {" >> /etc/nginx/sites-available/$servername
echo "        deny all;" >> /etc/nginx/sites-available/$servername
echo "        access_log off;" >> /etc/nginx/sites-available/$servername
echo "        log_not_found off;" >> /etc/nginx/sites-available/$servername
echo "    }" >> /etc/nginx/sites-available/$servername
echo "" >> /etc/nginx/sites-available/$servername
echo "    location / {" >> /etc/nginx/sites-available/$servername
echo "        include /etc/nginx/mime.types;" >> /etc/nginx/sites-available/$servername
echo "        try_files $uri $uri.html $uri/ @extensionless-php;" >> /etc/nginx/sites-available/$servername
echo "    }" >> /etc/nginx/sites-available/$servername
echo "" >> /etc/nginx/sites-available/$servername
echo "    location @extensionless-php {" >> /etc/nginx/sites-available/$servername
echo "        rewrite ^(.*)$ $1.php last;" >> /etc/nginx/sites-available/$servername
echo "    }" >> /etc/nginx/sites-available/$servername
echo "" >> /etc/nginx/sites-available/$servername
echo "    location ~ \.php$ {" >> /etc/nginx/sites-available/$servername
echo "        include snippets/fastcgi-php.conf;" >> /etc/nginx/sites-available/$servername
echo "        fastcgi_pass unix:/var/run/php/php-fpm.sock;" >> /etc/nginx/sites-available/$servername
echo "    }" >> /etc/nginx/sites-available/$servername
echo "}" >> /etc/nginx/sites-available/$servername

ln -s /etc/nginx/sites-available/$servername /etc/nginx/sites-enabled/$servername;
nginx -s reload;
certbot --agree-tos --no-eff-email --email tech@wildfire.world --nginx -d $servername;
nginx -s reload;