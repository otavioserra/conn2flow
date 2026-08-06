# BL-006 — Instalador: lock de execução, autenticação e resíduos pós-instalação

- **Tipo**: Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA/ALTA
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `gestor-instalador/index.php`, `gestor-instalador/src/Installer.php`

## Contexto observado

1. **Sem autenticação e sem lock**: o `index.php` processa qualquer POST e executa a etapa pedida (`$installer->runStep($action)`) sem token/segredo ([index.php:100-123](../../gestor-instalador/index.php)). Enquanto os arquivos do instalador existirem no servidor, qualquer pessoa pode acioná-lo (reconfigurar `.env`, apontar para outro banco, sobrescrever arquivos).
2. **Limpeza só no fim do caminho feliz**: a remoção dos arquivos do instalador acontece no passo final e **mantém `installer.log`** ([Installer.php:775](../../gestor-instalador/src/Installer.php)). Se a instalação falhar no meio, os arquivos permanecem acionáveis; e o `installer.log` residual (host de banco, caminhos, etapas) fica acessível via web.
3. **`configureEnvFile()` usa `preg_replace` com a senha como replacement** ([Installer.php:499](../../gestor-instalador/src/Installer.php)): senhas contendo `$1`, `\` etc. podem ser corrompidas na gravação do `.env` (correção/robustez).

## Proposta de melhoria (a validar)

1. Exigir um segredo de instalação (arquivo `install.lock`/token gerado) antes de qualquer ação de escrita; recusar se a instalação já foi concluída.
2. Bloquear re-execução com um marcador persistente e instruir/forçar remoção completa dos arquivos do instalador ao concluir, incluindo o `installer.log` (ou movê-lo para fora da raiz web).
3. Trocar `preg_replace` por escrita literal do `.env` (ex.: `preg_replace_callback` ou reconstrução linha a linha) para não interpretar metacaracteres na senha.

## Critérios de aceite (rascunho)

- Instalador não executa ações sem o segredo/lock e recusa rodar após concluído.
- Nenhum arquivo do instalador (inclusive log) permanece acessível via web após sucesso.
- Senhas com metacaracteres gravadas corretamente no `.env`.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
