FROM php:8.2-apache

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpq-dev \
    default-mysql-client

# Включаем Apache mod_rewrite
RUN a2enmod rewrite

# Устанавливаем PHP расширения
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Настраиваем Apache для работы с Laravel
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Открываем порт 80
EXPOSE 80

# Запускаем Apache
CMD ["apache2-foreground"]