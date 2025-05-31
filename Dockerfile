FROM php:8.3-apache

# Set the working directory inside the container
WORKDIR /var/www/html

# Install Apache mods, SSH, and PHP extensions
RUN apt-get update && apt-get install -y \
    openssh-server \
    libzip-dev zip unzip \
    iputils-ping && \
    docker-php-ext-install pdo pdo_mysql zip && \
    a2enmod rewrite && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* # Clean up APT when done

# Create required SSH folder
RUN mkdir /var/run/sshd

# Set root password for SSH
RUN echo 'root:rootpassword' | chpasswd

# Allow SSH password login
RUN sed -i 's/#PasswordAuthentication yes/PasswordAuthentication yes/' /etc/ssh/sshd_config

# Allow root login via SSH
RUN sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm composer-setup.php

# Copy your custom php.ini file into the PHP configuration directory
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy the Laravel application files into the container
COPY . /var/www/html

# NEW: COPY your custom apache.conf into the container's sites-available directory
# We name it laravel.conf inside the container so a2ensite can find it.
COPY apache.conf /etc/apache2/sites-available/laravel.conf

# Disable the default Apache site and enable your new Laravel site
# This ensures Apache uses your custom configuration
RUN a2dissite 000-default.conf && a2ensite laravel.conf

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set appropriate permissions for Laravel storage and bootstrap/cache directories
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Expose both web (80) and SSH (22) ports
EXPOSE 80 22

# Start SSH and Apache on container startup
CMD service ssh start && apache2-foreground