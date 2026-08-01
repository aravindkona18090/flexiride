FROM dunglas/frankenphp:1-php8.2

# Install required PHP extensions
RUN install-php-extensions mysqli pdo_mysql

WORKDIR /app

# Copy project files
COPY . .

# Copy Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHPMailer dependencies (if needed)
RUN if [ -f PHPMailer/composer.json ]; then \
    cd PHPMailer && composer install --no-dev --optimize-autoloader; \
    fi

EXPOSE 8080

CMD ["frankenphp", "php-server", "--listen", ":8080", "--root", "/app"]