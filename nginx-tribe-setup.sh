server {
    listen 80;

    server_name  tribe.exn2g3j1s.junction.express;

    location / {
        proxy_pass http://localhost:8082/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

server {
    listen 80;

    server_name  exn2g3j1s.junction.express;

    location / {
        proxy_pass http://localhost:8083/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

ln -s /etc/nginx/sites-available/exn2g3j1s.junction.express /etc/nginx/sites-enabled/exn2g3j1s.junction.express;

certbot --agree-tos --no-eff-email --email tech@wildfire.world --nginx -d exn2g3j1s.junction.express -d tribe.exn2g3j1s.junction.express;