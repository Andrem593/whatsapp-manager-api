# Usa una imagen base de Ubuntu
FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -y && apt-get install -y software-properties-common

# Añadir el repo de Ondřej para PHP 8.2
RUN add-apt-repository ppa:ondrej/php -y

RUN DEBIAN_FRONTEND=noninteractive apt-get install -y \
    sudo \
    nginx \
    php8.2-fpm \
    postgresql-client \
    curl \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    wget \
    gnupg

RUN DEBIAN_FRONTEND=noninteractive apt-get install -y  \
    php8.2-cli  \
    php8.2-common  \
    php8.2-zip  \
    php8.2-gd  \
    php8.2-mbstring  \
    php8.2-curl  \
    php8.2-xml  \
    php8.2-bcmath \
    php8.2-mysql  


    
# Copia la configuración de Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# RUN mkdir -p /etc/nginx/ssl
# RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/nginx/ssl/nginx.key -out /etc/nginx/ssl/nginx.crt -subj "/C=US/ST=Denial/L=Springfield/O=Dis/CN=www.example.com"
# Copia tu aplicación Laravel al directorio de trabajo
COPY . /var/www/html/

# Establece los permisos adecuados para Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Instala las dependencias de Composer
WORKDIR /var/www/html

# Exponer el puerto 80 para Nginx
EXPOSE 80
EXPOSE 9000
# Instalar SSH
RUN apt-get install -y openssh-server
RUN apt-get install -y nano
# Comando para iniciar los servicios de Nginx y PHP-FPM en primer plano
CMD service php8.2-fpm start && nginx -g 'daemon off;'