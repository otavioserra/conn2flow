$payload = @{
    systemMessage = 'Repositorio SDD: leia sdd/README.md, processo, batch atual, validation checklist e decision log antes de mudar codigo ou sdd; trate mudanca de requisito via change-request antes de implementar.'
} | ConvertTo-Json -Compress

Write-Output $payload
