#!/bin/bash

##### Examples of usage:

# List default models (pro and flash)
# bash ai-workspace/scripts/tests/gemini-model-list.sh

# Save full JSON
# bash ai-workspace/scripts/tests/gemini-model-list.sh --output models.json

# Save to default location with timestamp
# bash ai-workspace/scripts/tests/gemini-model-list.sh --output-default

# Filter by gemini-2.0 and flash
# bash ai-workspace/scripts/tests/gemini-model-list.sh --filter "gemini-2.0,flash"

# Combine filter and default output
# bash ai-workspace/scripts/tests/gemini-model-list.sh --filter "gemini-pro" --output-default

#########################################################

# Script to list available Google Gemini models

echo "=== LISTING GOOGLE GEMINI MODELS ==="

# Read API key from environment.json
apiKey=$(jq -r '.gemini.apiKey' dev-environment/data/environment.json)

if [ -z "$apiKey" ]; then
    echo "❌ Error: Could not read apiKey from environment.json"
    exit 1
fi

# Default settings
output_file=""
output_default=false
filter_words=("gemini-pro" "gemini-flash")

# Process arguments
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
            # Replace default filters with custom ones
            filter_words=()
            IFS=',' read -ra CUSTOM_FILTERS <<< "$2"
            for filter in "${CUSTOM_FILTERS[@]}"; do
                filter_words+=("$filter")
            done
            shift 2
            ;;
        *)
            echo "❌ Usage: $0 [--output file] [--output-default] [--filter word1,word2]"
            echo "Examples:"
            echo "  $0"
            echo "  $0 --output models.json"
            echo "  $0 --output-default"
            echo "  $0 --filter gemini-2.0,flash"
            exit 1
            ;;
    esac
done

# If --output-default was used, generate file with timestamp
if [ "$output_default" = true ]; then
    timestamp=$(date +%Y%m%d_%H%M%S)
    output_file="dev-environment/data/ai/models/model-${timestamp}.json"
fi

echo "Making request to list models..."

# Make request to list models
response=$(curl -s "https://generativelanguage.googleapis.com/v1beta/models?key=$apiKey")

# Check for request error
if [ $? -ne 0 ]; then
    echo "❌ HTTP request error"
    exit 1
fi

# Check if response contains error
if echo "$response" | jq -e '.error' >/dev/null 2>&1; then
    echo "❌ API Error: $(echo "$response" | jq -r '.error.message')"
    exit 1
fi

# Build jq filter
filter_condition=""
for word in "${filter_words[@]}"; do
    if [ -n "$filter_condition" ]; then
        filter_condition="$filter_condition or "
    fi
    filter_condition="$filter_condition(.name | contains(\"$word\"))"
done

# Save full or filtered JSON if specified
if [ -n "$output_file" ]; then
    if [ "${#filter_words[@]}" -eq 2 ] && [ "${filter_words[0]}" = "gemini-pro" ] && [ "${filter_words[1]}" = "gemini-flash" ]; then
        # Default filters - save full JSON
        echo "$response" > "$output_file"
        echo "✅ Full JSON saved to: $output_file"
    else
        # Custom filters - save only filtered models
        echo "$response" | jq ".models[] | select($filter_condition)" | jq -s "{models: .}" > "$output_file"
        echo "✅ Filtered JSON saved to: $output_file"
    fi
fi

# Apply filter and format output
echo "$response" | jq -r ".models[] | select($filter_condition) | \"• \(.name) - \(.description | if . == null then \"No description\" else . end)\""
