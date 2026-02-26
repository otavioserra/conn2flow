#!/bin/bash

##### Examples of usage:

# Listar modelos padrão (pro e flash)
# bash ai-workspace/pt-br/scripts/tests/gemini-model-list.sh

# Salvar JSON completo
# bash ai-workspace/pt-br/scripts/tests/gemini-model-list.sh --output modelos.json

# Salvar em local padrão com timestamp
# bash ai-workspace/pt-br/scripts/tests/gemini-model-list.sh --output-default

# Filtrar por gemini-2.0 e flash
# bash ai-workspace/pt-br/scripts/tests/gemini-model-list.sh --filter "gemini-2.0,flash"

# Combinar filtro e output padrão
# bash ai-workspace/pt-br/scripts/tests/gemini-model-list.sh --filter "gemini-pro" --output-default

#########################################################

# Script para listar modelos disponíveis do Google Gemini

echo "=== LISTANDO MODELOS DO GOOGLE GEMINI ==="

# Ler a chave da API do environment.json
apiKey=$(jq -r '.gemini.apiKey' dev-environment/data/environment.json)

if [ -z "$apiKey" ]; then
    echo "❌ Erro: Não foi possível ler a apiKey do environment.json"
    exit 1
fi

# Configurações padrão
output_file=""
output_default=false
filter_words=("gemini-pro" "gemini-flash")

# Processar argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        --output)
            output_file="$2"
            shift 2
            ;;
        --output-default)
            output_default=true
            shift
            ;;
        --filter)
            # Substituir filtros padrão pelos customizados
            filter_words=()
            IFS=',' read -ra CUSTOM_FILTERS <<< "$2"
            for filter in "${CUSTOM_FILTERS[@]}"; do
                filter_words+=("$filter")
            done
            shift 2
            ;;
        *)
            echo "❌ Uso: $0 [--output arquivo] [--output-default] [--filter palavra1,palavra2]"
            echo "Exemplos:"
            echo "  $0"
            echo "  $0 --output modelos.json"
            echo "  $0 --output-default"
            echo "  $0 --filter gemini-2.0,flash"
            exit 1
            ;;
    esac
done

# Se --output-default foi usado, gerar arquivo com timestamp
if [ "$output_default" = true ]; then
    timestamp=$(date +%Y%m%d_%H%M%S)
    output_file="dev-environment/data/ai/models/model-${timestamp}.json"
fi

echo "Fazendo requisição para listar modelos..."

# Fazer a requisição para listar modelos
response=$(curl -s "https://generativelanguage.googleapis.com/v1beta/models?key=$apiKey")

# Verificar se houve erro na requisição
if [ $? -ne 0 ]; then
    echo "❌ Erro na requisição HTTP"
    exit 1
fi

# Verificar se a resposta contém erro
if echo "$response" | jq -e '.error' >/dev/null 2>&1; then
    echo "❌ Erro na API: $(echo "$response" | jq -r '.error.message')"
    exit 1
fi

# Construir filtro jq
filter_condition=""
for word in "${filter_words[@]}"; do
    if [ -n "$filter_condition" ]; then
        filter_condition="$filter_condition or "
    fi
    filter_condition="$filter_condition(.name | contains(\"$word\"))"
done

# Salvar JSON completo ou filtrado se especificado
if [ -n "$output_file" ]; then
    if [ "${#filter_words[@]}" -eq 2 ] && [ "${filter_words[0]}" = "gemini-pro" ] && [ "${filter_words[1]}" = "gemini-flash" ]; then
        # Filtros padrão - salvar JSON completo
        echo "$response" > "$output_file"
        echo "✅ JSON completo salvo em: $output_file"
    else
        # Filtros customizados - salvar apenas modelos filtrados
        echo "$response" | jq ".models[] | select($filter_condition)" | jq -s "{models: .}" > "$output_file"
        echo "✅ JSON filtrado salvo em: $output_file"
    fi
fi

# Aplicar filtro e formatar saída
echo "$response" | jq -r ".models[] | select($filter_condition) | \"• \(.name) - \(.description | if . == null then \"Sem descrição\" else . end)\""
