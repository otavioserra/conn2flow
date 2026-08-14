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

# req-109: diretórios de log dos sites hospedados.
# O mesmo diretório é escrito pelo Apache (www-data) e pelo CLI do container (deploy, cron, testes);
# quem cria primeiro define o dono, e sem 777 o outro processo só consegue "Permission denied".
if [ -d /var/www/sites ]; then
  chown -R www-data:www-data /var/www/sites
  find /var/www/sites -type d -name logs -print0 2>/dev/null | while IFS= read -r -d '' dir_log; do
    chmod -R 777 "$dir_log"
  done

  # Cria o diretório de logs de cada site que ainda não o tenha (gestor/logs).
  find /var/www/sites -maxdepth 3 -type d -name gestor -print0 2>/dev/null | while IFS= read -r -d '' dir_gestor; do
    mkdir -p "$dir_gestor/logs"
    chown -R www-data:www-data "$dir_gestor/logs"
    chmod -R 777 "$dir_gestor/logs"
  done
fi

echo "Permissões configuradas:"
echo "- /home/conn2flow: $(ls -ld /home/conn2flow | awk '{print $1, $3, $4}')"
echo "- /var/www/html: $(ls -ld /var/www/html | awk '{print $1, $3, $4}')"
if [ -d /var/www/sites ]; then
  echo "- /var/www/sites: $(ls -ld /var/www/sites | awk '{print $1, $3, $4}')"
fi

echo "Iniciando Apache..."

# Inicia o Apache em foreground
exec apache2-foreground
