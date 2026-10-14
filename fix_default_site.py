# Let's write a python script to overwrite /etc/nginx/sites-available/default if we can or check how to fix default site
import os
print("Checking sites-available")
try:
    with open('/etc/nginx/sites-available/default', 'w') as f:
        f.write('''
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    server_name _;
    root /home/ngemis/cms-tsm/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
''')
    print("Successfully wrote default site")
except Exception as e:
    print(e)
