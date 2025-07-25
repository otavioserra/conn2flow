# Dockerfile para conn2flow - Ambiente de Produção
FROM php:8.3-apache

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configurar extensões GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Instalar extensões PHP necessárias
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    xml

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configurar Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos da aplicação
COPY . .

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Criar diretórios necessários
RUN mkdir -p /var/www/html/gestor/logs \
    && mkdir -p /var/www/html/gestor/temp \
    && chown -R www-data:www-data /var/www/html/gestor/logs \
    && chown -R www-data:www-data /var/www/html/gestor/temp

# Copiar e configurar script de inicialização
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expor porta 80
EXPOSE 80

# Comando de inicialização com nosso script personalizado
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
