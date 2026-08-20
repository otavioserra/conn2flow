<#
.SYNOPSIS
    Conn2Flow Core CLI Wrapper (PowerShell)
.DESCRIPTION
    Runs the modern OOP Conn2Flow CLI subsystem.
.EXAMPLE
    .\c2f.ps1 resources:sync
    .\c2f.ps1 ai:sync
    .\c2f.ps1 module:create meu-modulo
#>
[CmdletBinding()]
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$CliArgs
)

$scriptPath = Join-Path $PSScriptRoot 'cli\c2f.php'

if (-not (Test-Path $scriptPath)) {
    Write-Error "c2f bootstrap not found at $scriptPath"
    exit 1
}

php $scriptPath @CliArgs
exit $LASTEXITCODE
