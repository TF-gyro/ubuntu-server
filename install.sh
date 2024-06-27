export DEBIAN_FRONTEND=noninteractive

apt-get -yq update
apt-get -yq upgrade
apt-get -yq install swapspace lsb-release ca-certificates curl apt-transport-https software-properties-common nginx python3-pip python3-certbot-nginx net-tools zip unzip p7zip-full build-essential curl s3cmd htop imagemagick ffmpeg poppler-utils
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
apt-get -yq install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin