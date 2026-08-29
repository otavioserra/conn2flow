param()

$rawInput = [Console]::In.ReadToEnd()
if ([string]::IsNullOrWhiteSpace($rawInput)) {
    exit 0
}

try {
    $payload = $rawInput | ConvertFrom-Json
} catch {
    exit 0
}

$command = $payload.tool_input.command
if (-not $command) {
    exit 0
}

# 1. Bloqueio de cópia manual para ambientes de teste/espelho
$copyPattern = '\b(cp|copy|Copy-Item|xcopy)\b.*(dev-environment[\\/]data[\\/]sites|\/sites\/|\\sites\\)'
if ($command -match $copyPattern) {
    $msg = "Bloqueado por Governança: É proibido sincronizar por cópia manual. Utilize 'c2f manager:update-all' (sistema) ou 'c2f project:update-all <id>' (projeto)."
    [Console]::Error.WriteLine($msg)
    Write-Output "{`"permissionDecision`":`"deny`",`"reason`":`"$msg`"}"
    exit 1
}

# 2. Bloqueio de git add indiscriminado (-A, ., -u, --all)
$gitAddPattern = '\bgit\s+add(\s+-[a-zA-Z0-9_-]+)*\s+(\.|\-A|\-u|\-\-all)(\s|$|[;&|])'
if ($command -match $gitAddPattern) {
    $msg = "Bloqueado por Governança: É proibido 'git add -A' ou 'git add .'. Utilize 'git add <caminhos-especificos>'."
    [Console]::Error.WriteLine($msg)
    Write-Output "{`"permissionDecision`":`"deny`",`"reason`":`"$msg`"}"
    exit 1
}

exit 0
