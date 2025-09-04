#!/bin/bash
# Script de sincronização segura do gestor-instalador para o ambiente Docker
# Copia apenas arquivos novos ou modificados, nunca apaga nada da origem
# Uso: bash ./docker/utils/sincroniza-gestor-instalador.sh checksum
#
# Origem:   gestor-instalador/
# Destino:  docker/dados/sites/localhost/public_html/instalador/


# Caminhos absolutos para garantir execução de qualquer lugar
ORIGEM="/c/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor-instalador/"
DESTINO="/c/Users/otavi/OneDrive/Documentos/GIT/conn2flow/docker/dados/sites/localhost/public_html/instalador/"
PATH_DOCKER="/var/www/sites/localhost/public_html/instalador/"

# Modo de sincronização: padrao (default), checksum, forcar
MODO=${1:-padrao}

case "$MODO" in
  padrao|"" )
    echo "🔄 Modo: padrão (data/hora, não sobrescreve arquivos mais novos no destino)"
    CMD=(rsync -avu "$ORIGEM" "$DESTINO")
    ;;
  checksum )
    echo "🔄 Modo: checksum (compara conteúdo dos arquivos)"
    CMD=(rsync -av --checksum "$ORIGEM" "$DESTINO")
    ;;
  forcar )
    echo "🔄 Modo: forçar sobrescrita de todos os arquivos (ignora data/hora)"
    CMD=(rsync -av --ignore-times "$ORIGEM" "$DESTINO")
    ;;
  * )
    echo "❌ Modo inválido. Use: padrao | checksum | forcar"
    exit 1
    ;;
esac

# Executa o comando escolhido
"${CMD[@]}"

# Atualiza permissões de pasta
docker exec conn2flow-app bash -c "chown -R www-data:www-data $PATH_DOCKER"

# Remover o .htaccess
docker exec conn2flow-app bash -c "rm -f $PATH_DOCKER/.htaccess"

# Mensagem final
if [ $? -eq 0 ]; then
  echo "✅ Sincronização concluída com sucesso!"
else
  echo "❌ Ocorreu um erro na sincronização."
fi
