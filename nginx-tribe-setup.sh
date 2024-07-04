server {
    listen 80;

    server_name  tribe.43dbvv4nj.junction.express;

    location / {
        proxy_pass http://localhost:4000/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

server {
    listen 80;

    server_name  43dbvv4nj.junction.express;

    location / {
        proxy_pass http://localhost:4001/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

ln -s /etc/nginx/sites-available/43dbvv4nj.junction.express /etc/nginx/sites-enabled/43dbvv4nj.junction.express;

certbot --agree-tos --no-eff-email --email tech@wildfire.world --nginx -d 43dbvv4nj.junction.express -d tribe.43dbvv4nj.junction.express;