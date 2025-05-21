# change the following line manually and run "bash install.sh"
if [[ $EUID -ne 0 ]]; then
  echo "This script must be run as root."
  exit 1
fi

read -p "Enter server name (eg - d1.domain.tld): " servername
if [[ -z "$servername" ]]; then
  echo "Error: Server name cannot be empty."
  exit 1
fi

# old code
# servername="j0.wildfire.world"

# no changes after this line
# --------

export DEBIAN_FRONTEND=noninteractive

# Remove packages that can conflict with docker
apt-get remove docker.io docker-doc docker-compose docker-compose-v2 podman-docker containerd runc apache2

apt-get -yq update
apt-get -yq upgrade

# Start setting up the system
apt-get -yq install swapspace lsb-release ca-certificates curl php php-cgi php-fpm apt-transport-https software-properties-common nginx net-tools zip unzip p7zip-full build-essential curl s3cmd htop imagemagick ffmpeg poppler-utils inotify-tools incron zsh tmux python3 python3-venv libaugeas0

## CERTBOT
# Setup python venv
python3 -m venv /opt/certbot/
/opt/certbot/bin/pip install --upgrade pip

# Install certbot
/opt/certbot/bin/pip install certbot certbot-nginx

# prepare certbot
sudo ln -s /opt/certbot/bin/certbot /usr/bin/certbot

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

# create slug tracking files and change their ownership
touch /var/www/html/docker-tribe-slugs.txt
touch /var/www/html/docker-tribe-destroy-slugs.txt
chown www-data: /var/www/html/docker-tribe-slugs.txt /var/www/html/docker-tribe-destroy-slugs.txt

echo "root" >> /etc/incron.allow
systemctl start incron.service

rm /etc/nginx/sites-available/default
rm /etc/nginx/sites-enabled/default

cp ./nginx/default.conf /etc/nginx/sites-available/$servername
sed -i "s/%servername%/${servername}/g" /etc/nginx/sites-available/$servername

ln -s /etc/nginx/sites-available/$servername /etc/nginx/sites-enabled/$servername;
nginx -t && nginx -s reload;

certbot --agree-tos --no-eff-email --email tech@wildfire.world --nginx -d $servername;
nginx -t && nginx -s reload;
