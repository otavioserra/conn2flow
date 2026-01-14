#!/bin/bash

# Script to generate images using Google Gemini "Banana Pro" (Gemini 3 Pro Image)
# Usage: ./gemini-banana-pro-image.sh "Prompt" "OutputFilename" [AspectRatio]
# Note: Output filename extension will be ignored and replaced with .webp

# Settings
DEBUG=false
TEMP_JSON="gemini_response_$$.json"

# 1. Check if magick is installed
if ! command -v magick &> /dev/null; then
    echo "❌ Error: 'magick' (ImageMagick) tool not found."
    echo "Please install ImageMagick to proceed with WebP conversion."
    exit 1
fi

# Read API Key from environment.json
if [ -f "dev-environment/data/environment.json" ]; then
    API_KEY=$(jq -r '.gemini.apiKey' dev-environment/data/environment.json)
else
    echo "❌ Error: environment.json file not found."
    exit 1
fi

if [ -z "$API_KEY" ] || [ "$API_KEY" == "null" ]; then
    echo "❌ Error: API Key not found in environment.json."
    exit 1
fi

PROMPT="$1"
INPUT_FILENAME="$2"
ASPECT_RATIO="${3:-1:1}" # Default 1:1

if [ -z "$PROMPT" ]; then
    echo "❌ Error: Prompt not provided."
    echo "Usage: $0 \"Prompt\" \"OutputFilename\" [AspectRatio]"
    exit 1
fi

if [ -z "$INPUT_FILENAME" ]; then
    INPUT_FILENAME="generated_image"
fi

# Define filenames
BASE_NAME="${INPUT_FILENAME%.*}"
TEMP_PNG="${BASE_NAME}.png"
FINAL_WEBP="${BASE_NAME}.webp"

MODEL="gemini-3-pro-image-preview"

# Build JSON Payload
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

echo "🎨 Generating image with Gemini Banana Pro ($MODEL)..."
echo "Prompt: $PROMPT"

# Save raw response to temp file to avoid printing base64 to terminal
curl -s -X POST "https://generativelanguage.googleapis.com/v1beta/models/$MODEL:generateContent?key=$API_KEY" \
    -H "Content-Type: application/json" \
    -d "$PAYLOAD" \
    -o "$TEMP_JSON"

# Check for error in response
ERROR=$(jq -r '.error.message // empty' "$TEMP_JSON")
if [ ! -z "$ERROR" ]; then
    echo "❌ API Error: $ERROR"
    cat "$TEMP_JSON"
    rm "$TEMP_JSON"
    exit 1
fi

# Extract Base64 Image (supports inline_data and inlineData)
# We don't store in a variable to avoid terminal output issues
jq -r '.candidates[0].content.parts[] | select(.inline_data != null or .inlineData != null) | (.inline_data.data // .inlineData.data)' "$TEMP_JSON" | head -n 1 | base64 -d > "$TEMP_PNG"

# Verify if image was saved
if [ ! -s "$TEMP_PNG" ]; then
    echo "❌ Error: No image returned or decoding failed."
    echo "Full response saved to debug_response_error.json"
    mv "$TEMP_JSON" "debug_response_error.json"
    exit 1
fi

# Clean up temp JSON
rm "$TEMP_JSON"

echo "🔄 Converting to WebP..."
magick "$TEMP_PNG" "$FINAL_WEBP"

if [ $? -eq 0 ]; then
    rm "$TEMP_PNG"
    echo "✅ Image saved successfully (converted to WebP) in: $FINAL_WEBP"
else
    echo "❌ Error converting image to WebP."
    echo "⚠️ Original PNG image kept in: $TEMP_PNG"
    exit 1
fi
