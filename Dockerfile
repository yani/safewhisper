FROM alpine:3.21

# Install necessary packages
RUN apk update && apk add --no-cache \
    nginx \
    php83 \
    php83-fpm \
    php83-opcache \
    php83-redis \
    php83-mbstring \
    php83-json \
    redis \
    composer \
    supervisor

# Copy the Nginx configuration file
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Copy the Supervisor configuration file
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Copy safewhisper application files
COPY . /var/www/html

# Set permissions
RUN chown -R nginx:nginx /var/www/html

# Change working directory to /var/www/html
WORKDIR /var/www/html

# Run Composer install to install dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose ports
EXPOSE 80

# Start services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
