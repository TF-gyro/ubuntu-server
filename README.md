## install core-server using the following script in command line
```bash
curl -s https://raw.githubusercontent.com/tribe-framework/ubuntu-server/master/scripts/bash/install.sh | sudo bash
```

<!-- ### Install server code
- edit first line in install.sh
- bash ./install.sh -->

### Copy folder
- Copy folder at /var/www/html from github repo tribe-framework/ubuntu-server

### incrontab
- chown www-data: for both the .txt files in /var/www/html/
- use following line in "incrontab -e"
- /var/www/html/docker-tribe-slugs.txt  IN_MODIFY bash /var/www/html/nginx-tribe-setup.sh
- /var/www/html/docker-tribe-destroy-slugs.txt  IN_MODIFY bash /var/www/html/nginx-tribe-destroy.sh

### SSL certificates
- use the Cloudflare one for hostnames - junction.express, *.junction.express and *.tribe.junction.express
- Update / generate certificate at [cloudflare]/junction.express/ssl-tls/edge-certificates
- Update / generate certificate at [cloudflare]/junction.express/ssl-tls/origin
- Copy both certificates to /var/www/html/ssl/
