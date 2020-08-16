FROM php:7.4.4-fpm

## Install recommended extensions for Symfony
RUN apt-get update --fix-missing \
    && apt-get install -y --no-install-recommends \
#       for composer
        git \
#       for zip php extension
        libzip-dev \
#       for php intl extension
        libicu-dev \
#       for php pdo_pgsql extension
        libpq-dev \
#       for php http extension
        libcurl4-openssl-dev \
#       for php http extension
        libssl-dev \
        unzip \
#       for php gd extension
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        htop \
#       for CI
        ssh \
        rsync \
        zip \
        unzip \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        opcache \
        intl \
        zip \
        sockets \
        pcntl

ENV ACCEPT_EULA=Y

# Install php xdebug extension
ADD https://pecl.php.net/get/xdebug-2.9.4 /tmp/xdebug.tar.gz
RUN mkdir -p /usr/src/php/ext/xdebug\
    && tar xf /tmp/xdebug.tar.gz -C /usr/src/php/ext/xdebug --strip-components=1\
    && docker-php-ext-configure xdebug\
    && docker-php-ext-install xdebug\
    && rm /tmp/xdebug.tar.gz

# Install php raphf extension (need for php http extension)
ADD https://pecl.php.net/get/raphf/2.0.0 /tmp/raphf.tar.gz
RUN mkdir -p /usr/src/php/ext/raphf\
    && tar xf /tmp/raphf.tar.gz -C /usr/src/php/ext/raphf --strip-components=1\
    && docker-php-ext-configure raphf\
    && docker-php-ext-install raphf\
    && rm /tmp/raphf.tar.gz

# Install php propro extension (need for php http extension)
ADD https://pecl.php.net/get/propro/2.1.0 /tmp/propro.tar.gz
RUN mkdir -p /usr/src/php/ext/propro\
    && tar xf /tmp/propro.tar.gz -C /usr/src/php/ext/propro --strip-components=1\
    && docker-php-ext-configure propro\
    && docker-php-ext-install propro\
    && rm /tmp/propro.tar.gz

# Install php http extension
ADD https://pecl.php.net/get/pecl_http/3.2.0 /tmp/http.tar.gz
RUN mkdir -p /usr/src/php/ext/http\
    && tar xf /tmp/http.tar.gz -C /usr/src/php/ext/http --strip-components=1\
    && docker-php-ext-configure http\
    && docker-php-ext-install http\
    && rm /tmp/http.tar.gz

# Install php gd extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

ENV COMPOSER_ALLOW_SUPERUSER 1
# Install composer
RUN php -r "readfile('https://getcomposer.org/installer');" | php\
    && chmod +x composer.phar\
    && mv composer.phar /usr/local/bin/composer

RUN apt-get clean

# Permission fix
RUN usermod -u 1000 www-data