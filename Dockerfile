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
    npm \
    supervisor

# Create www-data user and group if they do not exist
RUN addgroup -S www-data || true && adduser -S -G www-data www-data || true

# Copy configuration files
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/php-fpm.conf /etc/php83/php-fpm.d/www.conf
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Copy application files
COPY . /var/www/html

# Change working directory to /var/www/html
WORKDIR /var/www/html

# Ensure .env file exists, if not, copy .env.example
RUN [ ! -f .env ] && cp .env.example .env || true

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm install --production

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose ports
EXPOSE 80

# Start services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
