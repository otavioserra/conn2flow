#!/bin/bash
# Script para executar migrações/atualizações do banco de dados no ambiente Docker
# Lê o caminho do Docker dinamicamente do environment.json
#
# Uso:
#   bash ./ai-workspace/pt-br/scripts/dev-environment/atualizacoes-gestor-banco-de-dados.sh
#
# O dockerPath é lido de dev-environment/data/environment.json:
#   - devEnvironment.dockerPath: caminho dentro do container Docker (ex: /var/www/sites/localhost/conn2flow-site/)
#
# Isso permite que cada desenvolvedor configure seus próprios caminhos sem editar o script.

# Carregar variáveis do environment.json
ENV_JSON="$(dirname "$0")/../../../../dev-environment/data/environment.json"
if [ ! -f "$ENV_JSON" ]; then
  echo "❌ Erro: environment.json não encontrado em $ENV_JSON"
  exit 1
fi

# Tentar usar jq, fallback para grep/sed se não disponível
if command -v jq >/dev/null 2>&1; then
  PATH_DOCKER=$(jq -r '.devEnvironment.dockerPath' "$ENV_JSON")
else
  PATH_DOCKER=$(grep '"dockerPath"' "$ENV_JSON" | sed -E 's/.*"dockerPath" *: *"([^"]*)".*/\1/')
fi

# Validar variável
if [ -z "$PATH_DOCKER" ] || [ "$PATH_DOCKER" = "null" ]; then
  echo "❌ Erro: 'dockerPath' não definido em environment.json (devEnvironment.dockerPath)"
  exit 1
fi

# Construir o caminho completo do comando PHP
PHP_SCRIPT="${PATH_DOCKER}controladores/atualizacoes/atualizacoes-banco-de-dados.php"

# Exibir informações
echo "🐳 Caminho Docker: $PATH_DOCKER"
echo "📄 Script PHP:     $PHP_SCRIPT"
echo "🔄 Executando atualizações do banco de dados..."

# Executar atualizações do banco dentro do Docker
docker exec conn2flow-app bash -c "php ${PHP_SCRIPT} --debug --log-diff"

# Mensagem final
if [ $? -eq 0 ]; then
  echo "✅ Atualizações do banco de dados concluídas com sucesso!"
else
  echo "❌ Ocorreu um erro durante as atualizações do banco de dados."
fi
