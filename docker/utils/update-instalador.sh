#!/bin/bash

# Script para atualizar automaticamente o instalador do conn2flow
# Busca a versão mais recente no GitHub e substitui o arquivo local
# 
# Uso: ./update-instalador.sh [pasta_instalacao]
# Exemplo: ./update-instalador.sh instalador
# Exemplo: ./update-instalador.sh subdominio/install
# Padrão: instalador

# Verifica se foi passado parâmetro para a pasta de instalação
INSTALL_FOLDER=${1:-"instalador"}

echo "🔍 Buscando versão mais recente do instalador no GitHub..."
echo "📁 Pasta de destino: public_html/$INSTALL_FOLDER"

# URL da API do GitHub para releases
API_URL="https://api.github.com/repos/otavioserra/conn2flow/releases"

# Diretório onde estão os dados do Docker
DADOS_DIR="$(dirname "$0")/../dados"
cd "$DADOS_DIR"

echo "📂 Diretório de trabalho: $(pwd)"

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

echo ""
echo "🎉 ATUALIZAÇÃO CONCLUÍDA!"
echo "📋 Resumo:"
echo "   • Versão: instalador-v$LATEST_RELEASE"
echo "   • Arquivo: gestor-instalador.tar.gz (atualizado)"
echo "   • Descompactado em: public_html/$INSTALL_FOLDER/"
echo "   • Acesso: http://localhost/$INSTALL_FOLDER/"
echo ""
echo "✨ Pronto para nova instalação!"
