#!/bin/bash

# Script para gerar autenticação no ambiente de desenvolvimento

echo "=== GERANDO AUTENTICAÇÃO NO AMBIENTE DE DESENVOLVIMENTO ==="

# Verificar se --force foi passado
force=false
if [ "$1" = "--force" ]; then
    force=true
fi

# Ler o caminho dockerPath do environment.json
dockerPath=$(jq -r '.devEnvironment.dockerPath' dev-environment/data/environment.json)

if [ -z "$dockerPath" ]; then
    echo "❌ Erro: Não foi possível ler o dockerPath do environment.json"
    exit 1
fi

echo "Caminho do Docker: $dockerPath"

# Verificar se o token já existe no container
if docker exec conn2flow-app test -f "$dockerPath/.envAITestsToken" && [ "$force" = false ]; then
    echo "✅ Token já existe em $dockerPath"
    exit 0
fi

if [ "$force" = true ]; then
    echo "Forçando remoção do token existente..."
    docker exec conn2flow-app bash -c "rm -f $dockerPath/.envAITestsToken"
fi

echo "Copiando script de geração para o container..."
docker cp ai-workspace/scripts/tests/gerar-auth.php conn2flow-app:"$dockerPath/gerar-auth.php"

echo "Executando script de geração no container..."
docker exec conn2flow-app bash -c "cd $dockerPath && php gerar-auth.php"

echo "Removendo script temporário do container..."
docker exec conn2flow-app bash -c "cd $dockerPath && rm gerar-auth.php"

echo "✅ Token gerado com sucesso em $dockerPath"
