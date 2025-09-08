# Dockerfile SIMPLIFICADO para conn2flow - Apenas infraestrutura básica
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
    default-mysql-client \
    less \
    procps \
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
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Configurar Apache - Multi-domínio
COPY sites.conf /etc/apache2/sites-available/000-default.conf

# Configurar diretório de trabalho e criar estrutura multi-domínio
WORKDIR /var/www

# Criar estrutura de pastas para múltiplos sites
RUN mkdir -p sites/localhost/home sites/localhost/public_html \
    && mkdir -p sites/site1.local/home sites/site1.local/public_html \
    && mkdir -p sites/site2.local/home sites/site2.local/public_html

# Criar páginas de boas-vindas para os novos sites
RUN echo '<h1>Site 1 - Conn2Flow</h1><p>Ambiente de teste configurado com sucesso!</p>' > /var/www/sites/site1.local/public_html/index.html \
    && echo '<h1>Site 2 - Conn2Flow</h1><p>Ambiente de teste configurado com sucesso!</p>' > /var/www/sites/site2.local/public_html/index.html

# Configurar permissões como no cPanel - para todos os sites
RUN chown -R www-data:www-data /var/www/sites \
    && chmod -R 755 /var/www/sites

# Copiar e configurar script de inicialização
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expor porta 80
EXPOSE 80

# Comando de inicialização
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
