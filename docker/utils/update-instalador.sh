#!/bin/bash

# Script para atualizar automaticamente o instalador do conn2flow
# Busca a versão mais recente no GitHub e substitui o arquivo local
# INCLUI RESET COMPLETO DO AMBIENTE (pastas + logs)
# 
# Uso: ./update-instalador.sh [pasta_instalacao]
# Exemplo: ./update-instalador.sh instalador
# Exemplo: ./update-instalador.sh subdominio/install
# Padrão: instalador

# Verifica se foi passado parâmetro para a pasta de instalação
INSTALL_FOLDER=${1:-"instalador"}

echo "� INICIANDO RESET COMPLETO DO AMBIENTE CONN2FLOW"
echo "=================================================="
echo "📁 Pasta de destino: public_html/$INSTALL_FOLDER"
echo ""

# PASSO 1: LIMPEZA COMPLETA DAS PASTAS
echo "🧹 PASSO 1: Limpando pastas home/ e public_html/..."
# Diretório onde estão os dados do Docker
DADOS_DIR="$(dirname "$0")/../dados"
cd "$DADOS_DIR"
echo "📂 Diretório de trabalho: $(pwd)"

# Remove todo conteúdo das pastas
rm -rf home/* public_html/*
echo "   ✅ Pastas home/ e public_html/ completamente limpas"

# PASSO 2: LIMPEZA DOS LOGS DO DOCKER
echo ""
echo "🗑️ PASSO 2: Limpando logs do ambiente Docker..."

# Limpa logs do PHP
docker exec conn2flow-app bash -c "echo '�️ Logs limpados automaticamente em: \$(date)' > /var/log/php_errors.log" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ Log PHP limpo"
else
    echo "   ⚠️ Container conn2flow-app não encontrado ou não está rodando"
fi

# Limpa logs do Apache
docker exec conn2flow-app bash -c "echo '🗑️ Logs limpados automaticamente em: \$(date)' > /var/log/apache2/access.log && echo '🗑️ Logs limpados automaticamente em: \$(date)' > /var/log/apache2/error.log" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ Logs Apache limpos"
else
    echo "   ⚠️ Falha ao limpar logs do Apache"
fi

# PASSO 3: DOWNLOAD DA NOVA VERSÃO
echo ""
echo "📥 PASSO 3: Baixando nova versão do instalador..."

# URL da API do GitHub para releases
API_URL="https://api.github.com/repos/otavioserra/conn2flow/releases"

# Busca o release mais recente do instalador
echo "🌐 Consultando API do GitHub..."
LATEST_RELEASE=$(curl -s "$API_URL" | grep -E '"tag_name".*"instalador-v[0-9]+\.[0-9]+\.[0-9]+"' | head -1 | sed 's/.*"instalador-v\([^"]*\)".*/\1/')

if [ -z "$LATEST_RELEASE" ]; then
    echo "❌ Erro: Não foi possível encontrar releases do instalador"
    exit 1
fi

echo "✅ Versão mais recente encontrada: instalador-v$LATEST_RELEASE"

# URL de download do instalador
DOWNLOAD_URL="https://github.com/otavioserra/conn2flow/releases/download/instalador-v$LATEST_RELEASE/instalador.zip"
echo "📥 URL de download: $DOWNLOAD_URL"

# Download da nova versão
echo "⬇️ Baixando instalador-v$LATEST_RELEASE..."
if curl -L "$DOWNLOAD_URL" -o "instalador-novo.zip"; then
    echo "✅ Download concluído com sucesso!"
else
    echo "❌ Erro no download"
    exit 1
fi

# Renomeia para o padrão atual
mv "instalador-novo.zip" "gestor-instalador.tar.gz"

echo "🧹 Limpando pasta public_html/$INSTALL_FOLDER (se existir)..."
rm -rf "public_html/$INSTALL_FOLDER"

echo "📦 Descompactando na pasta public_html/$INSTALL_FOLDER..."
mkdir -p "public_html/$INSTALL_FOLDER"
cd "public_html/$INSTALL_FOLDER"

# Descompacta o arquivo
if unzip -q "../../gestor-instalador.tar.gz"; then
    echo "✅ Instalador descompactado com sucesso!"
else
    echo "❌ Erro ao descompactar. Tentando com tar..."
    cd ../..
    if tar -xzf "gestor-instalador.tar.gz" -C "public_html/$INSTALL_FOLDER"; then
        echo "✅ Instalador descompactado com tar!"
    else
        echo "❌ Erro ao descompactar arquivo"
        exit 1
    fi
fi

cd ../..

# PASSO 4: VERIFICAÇÃO FINAL E STATUS
echo ""
echo "🔍 PASSO 4: Verificação final do ambiente..."

# Verifica status dos containers Docker
echo "🐳 Status dos containers:"
CONTAINERS_STATUS=$(docker ps --format "table {{.Names}}\t{{.Status}}" | grep conn2flow)
if [ $? -eq 0 ]; then
    echo "$CONTAINERS_STATUS"
else
    echo "   ⚠️ Nenhum container conn2flow encontrado rodando"
fi

echo ""
echo "📁 Verificação das pastas:"
echo "   home/: $(ls -la home/ 2>/dev/null | wc -l) itens"
echo "   public_html/: $(ls -la public_html/ 2>/dev/null | wc -l) itens"

echo ""
echo "🎉 RESET E ATUALIZAÇÃO CONCLUÍDOS!"
echo "=================================================="
echo "📋 Resumo completo:"
echo "   • ✅ Pastas home/ e public_html/ completamente limpas"
echo "   • ✅ Logs Docker resetados (PHP + Apache)"
echo "   • ✅ Nova versão: instalador-v$LATEST_RELEASE"
echo "   • ✅ Arquivo: gestor-instalador.tar.gz (atualizado)"
echo "   • ✅ Descompactado em: public_html/$INSTALL_FOLDER/"
echo "   • 🌐 Acesso: http://localhost/$INSTALL_FOLDER/"
echo ""
echo "✨ AMBIENTE PRONTO PARA NOVA INSTALAÇÃO! ✨"
