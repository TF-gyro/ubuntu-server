read -p "Domain name (www.mydomain.com): " servername

if [ -z "$servername" ]; then
  echo "Domain name not provided, cannot continue."
  exit 1
fi

read -p "Contact email for certbot: " contact_email

if [ -z "$servername" ]; then
  echo "Email not provided, cannot continue."
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

apt-get -yq update
apt-get -yq upgrade

# Start setting up the system
apt-get -yq install swapspace lsb-release ca-certificates php php-cgi php-fpm apt-transport-https software-properties-common nginx net-tools zip unzip p7zip-full build-essential curl s3cmd htop imagemagick ffmpeg poppler-utils inotify-tools incron zsh tmux python3 python3-venv libaugeas0 ufw

rm -r /var/www/html
apt-get purge -yq apache2
apt-get autoremove -yq

systemctl start nginx

## CERTBOT
# Setup python venv
python3 -m venv /opt/certbot/
/opt/certbot/bin/pip install --upgrade pip

# Install certbot
/opt/certbot/bin/pip install certbot certbot-nginx

# prepare certbot
ln -s /opt/certbot/bin/certbot /usr/bin/certbot

ufw allow OpenSSH
ufw allow Postfix
ufw allow 80
ufw allow 8080
ufw allow 3000
ufw allow 443
ufw allow 587
yes | ufw enable

# setting up docker
install -m 0755 -d /etc/apt/keyrings

# add docker's GPG keys
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add -

# add docker's repo
add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu focal stable"

apt-get update
apt-get -yq install docker-ce

APP_DIR="/var/www/$servername"

mkdir -p $APP_DIR/logs;
touch $APP_DIR/logs/{access,error}.log
chown www-data: $APP_DIR -R
chmod 755 $APP_DIR

echo "root" >> /etc/incron.allow
systemctl start incron.service

cp ./nginx/default.conf /etc/nginx/sites-available/$servername
sed -i "s/%servername%/${servername}/g" /etc/nginx/sites-available/$servername

ln -sf /etc/nginx/sites-available/$servername /etc/nginx/sites-enabled/$servername;
nginx -t && nginx -s reload;

certbot --agree-tos --no-eff-email --email $contact_email --nginx -d $servername;
nginx -t && nginx -s reload;
