FROM php:8.2-cli

# Install dependencies needed for PostgreSQL and Composer
RUN apt-get update && apt-get install -y \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy all application files
COPY . .

# (Optional) Install dependencies if any are added later
# RUN composer install --no-dev --optimize-autoloader

# Expose the correct port
ENV PORT=10000
EXPOSE $PORT

# Start the PHP built-in server
CMD php -S 0.0.0.0:$PORT
