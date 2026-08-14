#!/bin/bash
# Script para gerenciar sites no ambiente Docker multi-domínio
# Uso: bash ./gerenciar-sites.sh [acao] [site]
# Ações: criar, listar, limpar, copiar-instalador

SITES_DIR="./sites"
ACAO=${1:-listar}
SITE_NAME=$2

case "$ACAO" in
  criar)
    if [ -z "$SITE_NAME" ]; then
      echo "❌ Nome do site é obrigatório para criar"
      echo "Uso: bash ./gerenciar-sites.sh criar nome-do-site.local"
      exit 1
    fi
    
    echo "🔧 Criando estrutura para: $SITE_NAME"
    mkdir -p "$SITES_DIR/$SITE_NAME/home"
    mkdir -p "$SITES_DIR/$SITE_NAME/public_html"

    # req-109: diretório de logs criado junto com o site, escrevível por Apache e CLI.
    mkdir -p "$SITES_DIR/$SITE_NAME/public_html/gestor/logs"
    chmod -R 777 "$SITES_DIR/$SITE_NAME/public_html/gestor/logs" 2>/dev/null || true

    if docker ps | grep conn2flow-app > /dev/null 2>&1; then
      docker exec conn2flow-app bash -c "
        if [ -d '/var/www/sites/$SITE_NAME/public_html/gestor/logs' ]; then
          chown -R www-data:www-data '/var/www/sites/$SITE_NAME/public_html/gestor/logs'
          chmod -R 777 '/var/www/sites/$SITE_NAME/public_html/gestor/logs'
        fi
      " 2>/dev/null || true
    fi


    # Página de boas-vindas
    echo "<h1>$SITE_NAME - Conn2Flow</h1><p>Site criado em $(date)</p>" > "$SITES_DIR/$SITE_NAME/public_html/index.html"
    
    # Criar usuário FTP automaticamente
    FTP_USER="ftp_${SITE_NAME//./_}"
    FTP_PASS="ftp${SITE_NAME}123"
    
    echo "👤 Criando usuário FTP: $FTP_USER"
    if docker ps | grep conn2flow-ftp > /dev/null; then
      docker exec conn2flow-ftp bash -c "
        if ! id '$FTP_USER' &>/dev/null; then
          useradd -m -d '/home/ftp-data/$SITE_NAME' -s /bin/bash '$FTP_USER'
          echo '$FTP_USER:$FTP_PASS' | chpasswd
          chown -R '$FTP_USER:$FTP_USER' '/home/ftp-data/$SITE_NAME'
          chmod 755 '/home/ftp-data/$SITE_NAME'
          echo '✅ Usuário FTP $FTP_USER criado!'
        fi
      " 2>/dev/null || echo "⚠️ Servidor FTP não está rodando. Execute: docker-compose up -d"
    fi
    
    echo "✅ Site $SITE_NAME criado com sucesso!"
    echo "📁 Pastas: $SITES_DIR/$SITE_NAME/home/ e $SITES_DIR/$SITE_NAME/public_html/"
    echo "🌐 Acesso Web: http://$SITE_NAME/ (configure seu hosts se necessário)"
    echo "📡 Acesso FTP: $FTP_USER:$FTP_PASS (porta 21)"
    ;;
    
  listar)
    echo "📋 Sites configurados:"
    if [ -d "$SITES_DIR" ]; then
      for site in "$SITES_DIR"/*; do
        if [ -d "$site" ]; then
          site_name=$(basename "$site")
          ftp_user="ftp_${site_name//./_}"
          ftp_pass="ftp${site_name}123"
          echo "  • $site_name"
          echo "    - Home: $site/home/"
          echo "    - Public: $site/public_html/"
          echo "    - FTP: $ftp_user:$ftp_pass"
        fi
      done
      echo ""
      echo "🌐 Acesso FTP: localhost:21"
      echo "📊 Portas PASV: 21100-21110"
    else
      echo "❌ Diretório sites/ não encontrado"
    fi
    ;;
    
  limpar)
    if [ -z "$SITE_NAME" ]; then
      echo "❌ Nome do site é obrigatório para limpar"
      echo "Uso: bash ./gerenciar-sites.sh limpar nome-do-site.local"
      exit 1
    fi
    
    echo "🧹 Limpando conteúdo de: $SITE_NAME"
    rm -rf "$SITES_DIR/$SITE_NAME/home"/*
    rm -rf "$SITES_DIR/$SITE_NAME/public_html"/*
    
    # Recriar página de boas-vindas
    echo "<h1>$SITE_NAME - Conn2Flow</h1><p>Site limpo em $(date)</p>" > "$SITES_DIR/$SITE_NAME/public_html/index.html"
    
    echo "✅ Site $SITE_NAME limpo com sucesso!"
    ;;
    
  copiar-instalador)
    if [ -z "$SITE_NAME" ]; then
      echo "❌ Nome do site é obrigatório para copiar instalador"
      echo "Uso: bash ./gerenciar-sites.sh copiar-instalador nome-do-site.local"
      exit 1
    fi
    
    if [ ! -f "gestor-instalador.tar.gz" ]; then
      echo "❌ Arquivo gestor-instalador.tar.gz não encontrado"
      exit 1
    fi
    
    echo "📦 Copiando instalador para: $SITE_NAME"
    rm -rf "$SITES_DIR/$SITE_NAME/public_html"/*
    cd "$SITES_DIR/$SITE_NAME/public_html" && tar -xzf "../../../gestor-instalador.tar.gz"
    
    echo "✅ Instalador copiado para $SITE_NAME com sucesso!"
    echo "🌐 Acesso: http://$SITE_NAME/"
    ;;
    
  *)
    echo "❌ Ação inválida: $ACAO"
    echo ""
    echo "Uso: bash ./gerenciar-sites.sh [acao] [site]"
    echo ""
    echo "Ações disponíveis:"
    echo "  listar                    - Lista todos os sites"
    echo "  criar [nome-site]        - Cria novo site"
    echo "  limpar [nome-site]       - Limpa conteúdo do site"
    echo "  copiar-instalador [site] - Copia instalador para o site"
    echo ""
    echo "Exemplos:"
    echo "  bash ./gerenciar-sites.sh listar"
    echo "  bash ./gerenciar-sites.sh criar teste.local"
    echo "  bash ./gerenciar-sites.sh copiar-instalador teste.local"
    ;;
esac
