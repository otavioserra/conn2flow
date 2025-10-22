#!/usr/bin/env bash
set -euo pipefail


# =====================================================================================
# Build Local do Plugin (dinâmico via environment.json)
#
# Este script lê o arquivo fixo dev-environment/data/environment.json,
# extrai o caminho do config de plugins baseado no tipo (devPluginEnvironmentConfig.public.path ou devPluginEnvironmentConfig.private.path),
# lê esse config e usa os caminhos dinâmicos para buildar o plugin.
#
# Flags:
#   --plugin-root=/caminho  Define raiz do plugin (sobrescreve qualquer outro)
#   --keep-resources        Não remove diretórios resources antes do zip
#   --out-dir=/caminho      Diretório destino dos artefatos (default: conforme config)
#   --name=arquivo.zip      Nome base do zip (default: gestor-plugin.zip)
#   --no-hash               Não gera arquivo .sha256
#   --type=public|private   Tipo do plugin (public ou private, default: public)
# =====================================================================================


SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
# Caminho fixo do environment.json principal
ENV_MAIN_JSON="$SCRIPT_DIR/../../../dev-environment/data/environment.json"

if [[ ! -f "$ENV_MAIN_JSON" ]]; then
  echo "[build-plugin] ERRO: environment.json principal não encontrado: $ENV_MAIN_JSON" >&2
  exit 1
fi

# Define tipo padrão do plugin
PLUGIN_TYPE="public"

# Flags podem sobrescrever variáveis (processa primeiro para definir PLUGIN_TYPE)
for arg in "$@"; do
  case "$arg" in
    --type=*) PLUGIN_TYPE="${arg#*=}" ;;
    --help|-h)
      cat <<EOF
Uso: $(basename "$0") [opções]
  --plugin-root=/path       Define raiz do plugin
  --keep-resources          Mantém diretórios resources no pacote
  --out-dir=/path           Altera diretório de saída
  --name=arquivo.zip        Nome do zip (default gestor-plugin.zip)
  --no-hash                 Não gerar hash SHA256
  --type=public|private     Tipo do plugin (default: public)
EOF
      exit 0;;
  esac
done

# Valida o tipo do plugin
if [[ "$PLUGIN_TYPE" != "public" && "$PLUGIN_TYPE" != "private" ]]; then
  echo "[build-plugin] ERRO: Tipo de plugin inválido: $PLUGIN_TYPE. Use 'public' ou 'private'." >&2
  exit 1
fi

echo "[build-plugin] Tipo de plugin: $PLUGIN_TYPE" >&2

# Extrai caminho do config de plugin baseado no tipo
if command -v jq >/dev/null 2>&1; then
  PLUGIN_ENV_PATH=$(jq -r ".devPluginEnvironmentConfig.${PLUGIN_TYPE}.path" "$ENV_MAIN_JSON")
else
  PLUGIN_ENV_PATH=$(grep "\"devPluginEnvironmentConfig\"" -A 10 "$ENV_MAIN_JSON" | grep "\"${PLUGIN_TYPE}\"" -A 3 | grep '"path"' | sed -E 's/.*"path" *: *"([^"]*)".*/\1/' | head -1)
fi

if [[ -z "$PLUGIN_ENV_PATH" || "$PLUGIN_ENV_PATH" == "null" ]]; then
  echo "[build-plugin] ERRO: devPluginEnvironmentConfig.${PLUGIN_TYPE}.path não definido em $ENV_MAIN_JSON" >&2
  exit 1
fi
if [[ ! -f "$PLUGIN_ENV_PATH" ]]; then
  echo "[build-plugin] ERRO: Arquivo de config de plugin não encontrado: $PLUGIN_ENV_PATH" >&2
  exit 1
fi



# Extrai caminhos do config de plugin e monta o caminho do plugin ativo
if command -v jq >/dev/null 2>&1; then
  PLUGIN_ROOT_BASE=$(jq -r '.devEnvironment.source' "$PLUGIN_ENV_PATH")
  OUT_DIR_BASE=$(jq -r '.devEnvironment.deploys' "$PLUGIN_ENV_PATH")
  ACTIVE_PLUGIN_ID=$(jq -r '.activePlugin.id' "$PLUGIN_ENV_PATH")
  ACTIVE_PLUGIN_PATH=$(jq -r --arg id "$ACTIVE_PLUGIN_ID" '.plugins[] | select(.id==$id) | .path' "$PLUGIN_ENV_PATH")
else
  PLUGIN_ROOT_BASE=$(grep '"source"' "$PLUGIN_ENV_PATH" | sed -E 's/.*"source" *: *"([^"]*)".*/\1/')
  OUT_DIR_BASE=$(grep '"deploys"' "$PLUGIN_ENV_PATH" | sed -E 's/.*"deploys" *: *"([^"]*)".*/\1/')
  ACTIVE_PLUGIN_ID=$(grep '"activePlugin"' -A 2 "$PLUGIN_ENV_PATH" | grep '"id"' | sed -E 's/.*"id" *: *"([^"]*)".*/\1/')
  # Busca o path do plugin ativo na lista plugins
  ACTIVE_PLUGIN_PATH=$(awk -v id="$ACTIVE_PLUGIN_ID" 'BEGIN{p=0} /"plugins" *:/ {p=1} p && /"id"/ {if ($0 ~ id) f==1} f && /"path"/ {match($0, /"path" *: *"([^"]*)"/, a); print a[1]; exit}' "$PLUGIN_ENV_PATH")
fi

# Monta o caminho do plugin ativo
PLUGIN_ROOT="$PLUGIN_ROOT_BASE$ACTIVE_PLUGIN_PATH"

# Define o diretório de saída final (subpasta com ID do plugin)
OUT_DIR="${OUT_DIR_BASE%/}/$ACTIVE_PLUGIN_ID"

# Define o caminho da pasta de deploys (onde os arquivos processados são gerados)
DEPLOY_PLUGIN_ROOT="$OUT_DIR/temp"

# Descobre a raiz do environment.json do plugin
PLUGIN_ENV_ROOT=$(dirname "$PLUGIN_ENV_PATH")
DATA_SCRIPT="$PLUGIN_ENV_ROOT/scripts/resources/update-data-resources-plugin.php"
ZIP_NAME="gestor-plugin.zip"
KEEP_RESOURCES=false
GEN_HASH=true

# Flags podem sobrescrever outras variáveis
for arg in "$@"; do
  case "$arg" in
    --plugin-root=*) PLUGIN_ROOT="${arg#*=}" ;;
    --keep-resources) KEEP_RESOURCES=true ;;
    --out-dir=*) OUT_DIR="${arg#*=}" ;;
    --name=*) ZIP_NAME="${arg#*=}" ;;
    --no-hash) GEN_HASH=false ;;
    --type=*) ;; # Já processado acima
  esac
done

# Define a pasta fonte inicial (sempre começa com plugin original)
SOURCE_ROOT="$PLUGIN_ROOT"
echo "[build-plugin] Pasta fonte inicial: $SOURCE_ROOT" >&2

if [[ ! -d "$SOURCE_ROOT" ]]; then
  echo "[build-plugin] ERRO: Pasta fonte inexistente: $SOURCE_ROOT" >&2
  exit 1
fi

mkdir -p "$OUT_DIR"

echo "[build-plugin] Plugin root: $PLUGIN_ROOT" >&2
echo "[build-plugin] Deploy root: $DEPLOY_PLUGIN_ROOT" >&2
echo "[build-plugin] Out dir: $OUT_DIR" >&2

# 1. Gerar múltiplos Data.json (sempre usa plugin original como fonte, gera na pasta deploys)
if [[ -d "$DEPLOY_PLUGIN_ROOT" ]]; then
  echo "[build-plugin] Limpando pasta deploys anterior: $DEPLOY_PLUGIN_ROOT" >&2
  rm -rf "$DEPLOY_PLUGIN_ROOT"/* 2>/dev/null || true
fi

if [[ -f "$DATA_SCRIPT" ]]; then
  echo "[build-plugin] Gerando Data JSON (Layouts/Paginas/Componentes/Variaveis)" >&2
  echo "[build-plugin] Fonte: $PLUGIN_ROOT" >&2
  echo "[build-plugin] Destino: $DEPLOY_PLUGIN_ROOT" >&2
  php "$DATA_SCRIPT" --plugin-root="$PLUGIN_ROOT" --deploy-plugin-root="$DEPLOY_PLUGIN_ROOT" || { echo '[build-plugin] WARN: geração falhou'; }
else
  echo "[build-plugin] AVISO: Script de geração não encontrado: $DATA_SCRIPT" >&2
fi

# 2. Copiar para diretório temporário (usa SOURCE_ROOT que pode ser deploys ou plugin original)
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR" || true' EXIT
cp -a "$SOURCE_ROOT" "$TMP_DIR/plugin"

cd "$TMP_DIR/plugin"

# 3. Limpezas opcionais
if [[ "$KEEP_RESOURCES" = false ]]; then
  echo "[build-plugin] Removendo resources/ (globais + módulos)" >&2
  rm -rf resources/ 2>/dev/null || true
  find modules -type d -name resources -exec rm -rf {} + 2>/dev/null || true
else
  echo "[build-plugin] Mantendo resources conforme flag --keep-resources" >&2
fi

# Logs, caches comuns
find . -maxdepth 4 -type f -name "*.log" -delete 2>/dev/null || true
rm -rf .git* node_modules vendor/composer/tmp-* 2>/dev/null || true

# 4. Empacotar
ARCHIVE_PATH="$OUT_DIR/$ZIP_NAME"
HASH_PATH="$ARCHIVE_PATH.sha256"
rm -f "$ARCHIVE_PATH" "$HASH_PATH" 2>/dev/null || true

ARCHIVER="zip"
if command -v 7z >/dev/null 2>&1; then ARCHIVER="7z"; fi

echo "[build-plugin] Arquivador: $ARCHIVER" >&2
if [[ "$ARCHIVER" = 7z ]]; then
  7z a -tzip "$ARCHIVE_PATH" \
    -xr!'.git*' -xr!'node_modules' -xr!'*.DS_Store*' -xr!'vendor/composer/tmp-*' -xr!'tests'
else
  zip -r "$ARCHIVE_PATH" . \
    -x "*.git*" \
    -x "node_modules/*" \
    -x "*.DS_Store*" \
    -x "vendor/composer/tmp-*" \
    -x "tests/*"
fi


# 5. Hash
if [[ "$GEN_HASH" = true ]]; then
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$ARCHIVE_PATH" | awk '{print $1}' > "$HASH_PATH"
  elif command -v certutil >/dev/null 2>&1; then
    certutil -hashfile "$ARCHIVE_PATH" SHA256 | sed -n '2p' | tr -d '\r\n' > "$HASH_PATH"
  else
    echo "[build-plugin] AVISO: sha256sum/certutil indisponíveis - pulando hash" >&2
  fi
fi

echo "[build-plugin] Artefatos:" >&2
ls -lh "$ARCHIVE_PATH"* 2>/dev/null || true
echo "[build-plugin] OK" >&2
