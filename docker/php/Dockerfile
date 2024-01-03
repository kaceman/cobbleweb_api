# Use an official PHP runtime as a parent image
FROM php:7.4-apache

# Set the working directory to /var/www/html
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the current directory contents into the container at /var/www/html
COPY . /var/www/html

# Copy the custom Apache configuration
COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Enable the custom Apache configuration
RUN a2ensite 000-default

# Install Symfony dependencies
RUN composer install

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for the web server
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
