SafeWhisper: Native installation
================================


## Requirements:
- HTTP webserver
- PHP 7.3+
- Redis
- Composer
- npm
- git


## Instructions:

```
git clone https://github.com/yani/safewhisper safewhisper
cd safewhisper
composer install --no-dev --optimize-autoloader
npm install --production
```

Rename `.env.example` to `.env` and setup the configuration variables in this file.
Most variables will be good already although `APP_REDIS_URI` might need to be changed depending on your redis server.

Then setup your webserver to route everything to `/public/index.php`.


## Nginx Configuration Example

```
server {
        listen 80;
        listen [::]:80;

        server_name  safewhisper.domain.tld;

        root   /var/www/safewhisper/public;
        index  index.php;

        access_log off;
        log_not_found off;

        location / {
                try_files $uri /index.php$is_args$args;
        }

        location ~ \.php$ {
                try_files $uri =404;
                fastcgi_pass 127.0.0.1:9000;
                fastcgi_param SCRIPT_FILENAME /public_html/public$fastcgi_script_name;
                include fastcgi_params;
        }
}
```

This is an nginx configuration for a non HTTPS domain. It's best to setup SSL/HTTPS for extra security.
