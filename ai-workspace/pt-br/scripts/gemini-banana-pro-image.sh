#!/bin/bash

# Script para gerar imagens usando o Google Gemini "Banana Pro" (Gemini 3 Pro Image)
# Usage: ./gemini-banana-pro-image.sh "Prompt" "OutputFilename" [AspectRatio]
# Note: Output filename extension will be ignored and replaced with .webp

# Configurações
DEBUG=false
TEMP_JSON="gemini_response_$$.json"

# 1. Verificar se magick está instalado
if ! command -v magick &> /dev/null; then
    echo "❌ Erro: Ferramenta 'magick' (ImageMagick) não encontrada."
    echo "Por favor, instale o ImageMagick para prosseguir com a conversão para WebP."
    exit 1
fi

# Ler a chave da API do environment.json
if [ -f "dev-environment/data/environment.json" ]; then
    API_KEY=$(jq -r '.gemini.apiKey' dev-environment/data/environment.json)
else
    echo "❌ Erro: Arquivo environment.json não encontrado."
    exit 1
fi

if [ -z "$API_KEY" ] || [ "$API_KEY" == "null" ]; then
    echo "❌ Erro: API Key não encontrada no environment.json."
    exit 1
fi

PROMPT="$1"
INPUT_FILENAME="$2"
ASPECT_RATIO="${3:-1:1}" # Default 1:1

if [ -z "$PROMPT" ]; then
    echo "❌ Erro: Prompt não fornecido."
    echo "Uso: $0 \"Prompt\" \"OutputFilename\" [AspectRatio]"
    exit 1
fi

if [ -z "$INPUT_FILENAME" ]; then
    INPUT_FILENAME="generated_image"
fi

# Definir nomes de arquivos
BASE_NAME="${INPUT_FILENAME%.*}"
TEMP_PNG="${BASE_NAME}.png"
FINAL_WEBP="${BASE_NAME}.webp"

MODEL="gemini-3-pro-image-preview"

# Construir JSON Payload
PAYLOAD=$(jq -n \
    --arg prompt "$PROMPT" \
    --arg ar "$ASPECT_RATIO" \
    '{
        contents: [{ parts: [{ text: $prompt }] }],
        generationConfig: {
            responseModalities: ["IMAGE"],
            imageConfig: {
                aspectRatio: $ar,
                imageSize: "2K"
            }
        }
    }')

if [ "$DEBUG" = true ]; then
    echo "Payload: $PAYLOAD"
fi

echo "🎨 Gerando imagem com Gemini Banana Pro ($MODEL)..."
echo "Prompt: $PROMPT"

# Salvar resposta em arquivo temporário para evitar imprimir base64 no terminal
curl -s -X POST "https://generativelanguage.googleapis.com/v1beta/models/$MODEL:generateContent?key=$API_KEY" \
    -H "Content-Type: application/json" \
    -d "$PAYLOAD" \
    -o "$TEMP_JSON"

# Verificar erro na resposta
ERROR=$(jq -r '.error.message // empty' "$TEMP_JSON")
if [ ! -z "$ERROR" ]; then
    echo "❌ Erro na API: $ERROR"
    cat "$TEMP_JSON"
    rm "$TEMP_JSON"
    exit 1
fi

# Extrair imagem Base64 (suporta inline_data e inlineData)
# Não armazenamos em variável para evitar problemas de output no terminal
jq -r '.candidates[0].content.parts[] | select(.inline_data != null or .inlineData != null) | (.inline_data.data // .inlineData.data)' "$TEMP_JSON" | head -n 1 | base64 -d > "$TEMP_PNG"

# Verificar se a imagem foi salva
if [ ! -s "$TEMP_PNG" ]; then
    echo "❌ Erro: Nenhuma imagem retornada ou falha na decodificação."
    echo "Resposta completa salva em debug_response_error.json"
    mv "$TEMP_JSON" "debug_response_error.json"
    exit 1
fi

# Limpar JSON temporário
rm "$TEMP_JSON"

echo "🔄 Convertendo para WebP..."
magick "$TEMP_PNG" "$FINAL_WEBP"

if [ $? -eq 0 ]; then
    rm "$TEMP_PNG"
    echo "✅ Imagem salva com sucesso (convertida para WebP) em: $FINAL_WEBP"
else
    echo "❌ Erro ao converter imagem para WebP."
    echo "⚠️ Imagem original PNG mantida em: $TEMP_PNG"
    exit 1
fi
