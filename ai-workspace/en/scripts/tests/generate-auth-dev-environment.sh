#!/bin/bash

# Script to generate authentication in the development environment

echo "=== GENERATING AUTHENTICATION IN DEVELOPMENT ENVIRONMENT ==="

# Check if --force was passed
force=false
if [ "$1" = "--force" ]; then
    force=true
fi

# Read dockerPath from environment.json
dockerPath=$(jq -r '.devEnvironment.dockerPath' dev-environment/data/environment.json)

if [ -z "$dockerPath" ]; then
    echo "❌ Error: Could not read dockerPath from environment.json"
    exit 1
fi

echo "Docker Path: $dockerPath"

# Check if token already exists in container
if docker exec conn2flow-app test -f "$dockerPath/.envAITestsToken" && [ "$force" = false ]; then
    echo "✅ Token already exists in $dockerPath"
    exit 0
fi

if [ "$force" = true ]; then
    echo "Forcing removal of existing token..."
    docker exec conn2flow-app bash -c "rm -f $dockerPath/.envAITestsToken"
fi

echo "Copying generation script to container..."
docker cp ai-workspace/en/scripts/tests/generate-auth.php conn2flow-app:"$dockerPath/generate-auth.php"

echo "Executing generation script in container..."
docker exec conn2flow-app bash -c "cd $dockerPath && php generate-auth.php"

echo "Removing temporary script from container..."
docker exec conn2flow-app bash -c "cd $dockerPath && rm generate-auth.php"

echo "✅ Token successfully generated in $dockerPath"
