#!/usr/bin/env bash
set -euo pipefail

# =====================================================================================
# Build Local do Plugin (skeleton)
# Espelha estratégia do build do gestor: gera Data.json (múltiplos), copia para TMP,
# remove resources e empacota.
#
# Flags:
#   --test-plugin           Usa pasta de testes (tests/build/plugin)
#   --plugin-root=/caminho  Define raiz do plugin (sobrescreve qualquer outro)
#   --keep-resources        Não remove diretórios resources antes do zip
#   --out-dir=/caminho      Diretório destino dos artefatos (default: ai-workspace/scripts/build)
#   --name=arquivo.zip      Nome base do zip (default: gestor-plugin.zip)
#   --no-hash               Não gera arquivo .sha256
# =====================================================================================

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SKELETON_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"   # plugin-skeleton
REPO_ROOT="$(cd "$SKELETON_ROOT/.." && pwd)"          # raiz do monorepo
DEFAULT_PLUGIN_ROOT="$SKELETON_ROOT/plugin"
TEST_PLUGIN_ROOT="$SKELETON_ROOT/tests/build/plugin"
DATA_SCRIPT="$SKELETON_ROOT/utils/controllers/agents/update-data-resources-plugin.php"
OUT_DIR_DEFAULT="$SKELETON_ROOT/ai-workspace/scripts/build"
ZIP_NAME="gestor-plugin.zip"
KEEP_RESOURCES=false
GEN_HASH=true
PLUGIN_ROOT="$DEFAULT_PLUGIN_ROOT"
OUT_DIR="$OUT_DIR_DEFAULT"

for arg in "$@"; do
  case "$arg" in
    --test-plugin) PLUGIN_ROOT="$TEST_PLUGIN_ROOT" ;;
    --plugin-root=*) PLUGIN_ROOT="${arg#*=}" ;;
    --keep-resources) KEEP_RESOURCES=true ;;
    --out-dir=*) OUT_DIR="${arg#*=}" ;;
    --name=*) ZIP_NAME="${arg#*=}" ;;
    --no-hash) GEN_HASH=false ;;
    --help|-h)
      cat <<EOF
Uso: $(basename "$0") [opções]
  --test-plugin             Usa gestor/tests/build/plugin
  --plugin-root=/path       Define raiz do plugin
  --keep-resources          Mantém diretórios resources no pacote
  --out-dir=/path           Altera diretório de saída
  --name=arquivo.zip        Nome do zip (default gestor-plugin.zip)
  --no-hash                 Não gerar hash SHA256
EOF
      exit 0;;
  esac
done

if [[ ! -d "$PLUGIN_ROOT" ]]; then
  echo "[build-plugin] ERRO: Plugin root inexistente: $PLUGIN_ROOT" >&2
  exit 1
fi

mkdir -p "$OUT_DIR"

echo "[build-plugin] Plugin root: $PLUGIN_ROOT" >&2
echo "[build-plugin] Out dir: $OUT_DIR" >&2

# 1. Gerar múltiplos Data.json (usa script skeleton com override explicito de plugin-root)
if [[ -f "$DATA_SCRIPT" ]]; then
  echo "[build-plugin] Gerando Data JSON (Layouts/Paginas/Componentes/Variaveis)" >&2
  php "$DATA_SCRIPT" --plugin-root="$PLUGIN_ROOT" || { echo '[build-plugin] WARN: geração falhou'; }
else
  echo "[build-plugin] AVISO: Script de geração não encontrado: $DATA_SCRIPT" >&2
fi

# 2. Copiar para diretório temporário (para remover resources sem afetar workspace)
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR" || true' EXIT
cp -a "$PLUGIN_ROOT" "$TMP_DIR/plugin"

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
