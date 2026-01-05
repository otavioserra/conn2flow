#!/bin/bash

# Script para testar a API do Google Gemini

# Configurações
DEBUG_JSON=false  # Defina como true para imprimir o JSON completo da resposta

echo "=== TESTANDO API DO GOOGLE GEMINI ==="

# Ler a chave da API do environment.json
apiKey=$(jq -r '.gemini.apiKey' dev-environment/data/environment.json)

if [ -z "$apiKey" ]; then
    echo "❌ Erro: Não foi possível ler a apiKey do environment.json"
    exit 1
fi

# Verificar se foi passada uma pergunta ou ler do arquivo
if [ -z "$1" ]; then
    promptFile="dev-environment/data/ai/prompts/generateContent.txt"
    if [ ! -f "$promptFile" ]; then
        echo "❌ Erro: Arquivo de prompt não encontrado: $promptFile"
        exit 1
    fi
    
    question=$(cat "$promptFile" | tr -d '\n\r')
    if [ -z "$question" ]; then
        echo "❌ Erro: Arquivo de prompt está vazio: $promptFile"
        exit 1
    fi
    
    echo "Usando prompt do arquivo: $promptFile"
else
    question="$1"
fi

# Escapar o input para JSON seguro
question_escaped=$(echo "$question" | jq -Rs .)

echo "Pergunta: $question"
echo "Fazendo requisição para Gemini..."

# Construir payload JSON seguro
payload=$(jq -n --arg text "$question" '{contents: [{parts: [{text: $text}]}]}')

# Fazer a requisição para a API do Gemini
response=$(curl -s -X POST "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=$apiKey" \
    -H "Content-Type: application/json" \
    -d "$payload")

# Verificar se houve erro na requisição
if [ $? -ne 0 ]; then
    echo "❌ Erro na requisição HTTP"
    exit 1
fi

# Imprimir JSON completo se DEBUG_JSON estiver ativado
if [ "$DEBUG_JSON" = true ]; then
    echo "=== JSON COMPLETO DA RESPOSTA ==="
    echo "$response"
    echo "=== FIM DO JSON ==="
fi

# Extrair a resposta do JSON
answer=$(echo "$response" | jq -r '.candidates[0].content.parts[0].text' 2>/dev/null)

if [ -z "$answer" ] || [ "$answer" = "null" ]; then
    echo "❌ Erro ao processar resposta da API"
    echo "Resposta bruta: $response"
    exit 1
fi

echo "Resposta do Gemini:"
echo "$answer"
