#!/bin/bash

echo "=== CONN2FLOW DOCKER ENTRYPOINT ==="
echo "Configurando permissões e ambiente..."

# Aguarda um pouco para garantir que os volumes estão montados
sleep 2

# Cria os diretórios base se não existirem
mkdir -p /home/conn2flow
mkdir -p /var/www/html

# Define proprietário correto para o diretório home
chown -R www-data:www-data /home/conn2flow
chmod -R 755 /home/conn2flow

# Garante que o Apache pode escrever nos logs
chown -R www-data:www-data /var/log
chmod -R 755 /var/log

# Garante permissões corretas no diretório web
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

echo "Permissões configuradas:"
echo "- /home/conn2flow: $(ls -ld /home/conn2flow | awk '{print $1, $3, $4}')"
echo "- /var/www/html: $(ls -ld /var/www/html | awk '{print $1, $3, $4}')"

echo "Iniciando Apache..."

# Inicia o Apache em foreground
exec apache2-foreground
