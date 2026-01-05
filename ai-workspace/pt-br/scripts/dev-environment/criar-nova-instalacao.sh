#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../../" && pwd)"

echo "[nova-instalacao] Iniciando criação de nova instalação..."

# 1. Executar o build do instalador
echo "[nova-instalacao] Gerando instalador..."
"$SCRIPT_DIR/../updates/build-local-gestor-instalador.sh"

# 2. Ler configuração do environment.json
ENV_FILE="$REPO_ROOT/dev-environment/data/environment.json"
if [ ! -f "$ENV_FILE" ]; then
    echo "[nova-instalacao] ERRO: Arquivo environment.json não encontrado: $ENV_FILE" >&2
    exit 1
fi

# Extrair o target do devInstallerEnvironment usando sed
INSTALLER_TARGET=$(sed -n '/"devInstallerEnvironment"/,/}/p' "$ENV_FILE" | grep '"target"' | sed 's/.*"target"[[:space:]]*:[[:space:]]*"//' | sed 's/".*//')

if [ -z "$INSTALLER_TARGET" ]; then
    echo "[nova-instalacao] ERRO: Não foi possível extrair devInstallerEnvironment.target do environment.json" >&2
    exit 1
fi

echo "[nova-instalacao] Pasta de destino: $INSTALLER_TARGET"

# 3. Deletar todos os arquivos da pasta target
if [ -d "$INSTALLER_TARGET" ]; then
    echo "[nova-instalacao] Removendo arquivos existentes..."
    # Remove arquivos visíveis e ocultos, mas preserva o diretório
    find "$INSTALLER_TARGET" -mindepth 1 -delete
else
    echo "[nova-instalacao] Criando diretório de destino..."
    mkdir -p "$INSTALLER_TARGET"
fi

# 4. Copiar o .zip para a pasta target
ZIP_SOURCE="$REPO_ROOT/dev-environment/data/sites/localhost/conn2flow-github/instalador.zip"
if [ ! -f "$ZIP_SOURCE" ]; then
    echo "[nova-instalacao] ERRO: Arquivo instalador.zip não encontrado: $ZIP_SOURCE" >&2
    exit 1
fi

echo "[nova-instalacao] Copiando instalador.zip..."
cp "$ZIP_SOURCE" "$INSTALLER_TARGET/"

# 5. Descompactar o arquivo
echo "[nova-instalacao] Descompactando instalador..."
cd "$INSTALLER_TARGET"
unzip -q instalador.zip

# 6. Remover o .zip
echo "[nova-instalacao] Removendo arquivo zip..."
rm instalador.zip

# 7. Ajustar permissões no Docker (se disponível)
if command -v docker >/dev/null 2>&1; then
    echo "[nova-instalacao] Ajustando permissões no Docker..."
    docker exec conn2flow-app bash -c "chown -R www-data:www-data /var/www/sites/localhost/public_html/instalador/" 2>/dev/null || true
fi

echo "[nova-instalacao] ✅ Nova instalação criada com sucesso!"
echo "[nova-instalacao] 📁 Local: $INSTALLER_TARGET"
echo "[nova-instalacao] 🌐 URL: http://localhost/instalador/"
