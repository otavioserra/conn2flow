#!/bin/bash

# Script to test Google Gemini API

# Settings
DEBUG_JSON=false  # Set to true to print the full JSON response

echo "=== TESTING GOOGLE GEMINI API ==="

# Read API key from environment.json
apiKey=$(jq -r '.gemini.apiKey' dev-environment/data/environment.json)

if [ -z "$apiKey" ]; then
    echo "❌ Error: Could not read apiKey from environment.json"
    exit 1
fi

# Check if a question was passed or read from file
if [ -z "$1" ]; then
    promptFile="dev-environment/data/ai/prompts/generateContent.txt"
    if [ ! -f "$promptFile" ]; then
        echo "❌ Error: Prompt file not found: $promptFile"
        exit 1
    fi
    
    question=$(cat "$promptFile" | tr -d '\n\r')
    if [ -z "$question" ]; then
        echo "❌ Error: Prompt file is empty: $promptFile"
        exit 1
    fi
    
    echo "Using prompt from file: $promptFile"
else
    question="$1"
fi

# Escape input for safe JSON
question_escaped=$(echo "$question" | jq -Rs .)

echo "Question: $question"
echo "Making request to Gemini..."

# Build safe JSON payload
payload=$(jq -n --arg text "$question" '{contents: [{parts: [{text: $text}]}]}')

# Make request to Gemini API
response=$(curl -s -X POST "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=$apiKey" \
    -H "Content-Type: application/json" \
    -d "$payload")

# Check for request error
if [ $? -ne 0 ]; then
    echo "❌ HTTP request error"
    exit 1
fi

# Print full JSON if DEBUG_JSON is enabled
if [ "$DEBUG_JSON" = true ]; then
    echo "=== FULL RESPONSE JSON ==="
    echo "$response"
    echo "=== END OF JSON ==="
fi

# Extract answer from JSON
answer=$(echo "$response" | jq -r '.candidates[0].content.parts[0].text' 2>/dev/null)

if [ -z "$answer" ] || [ "$answer" = "null" ]; then
    echo "❌ Error processing API response"
    echo "Raw response: $response"
    exit 1
fi

echo "Gemini Response:"
echo "$answer"
