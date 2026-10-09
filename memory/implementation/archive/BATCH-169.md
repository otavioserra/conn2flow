# BATCH-169 — Blindagem de rsync contra MSYS Path Conversion (req-164)

- **Status**: complete
- **Intake**: [req-164.md](../../human-requests/archive/req-164.md)
- **Data**: 2026-09-17
- **Classificação**: implementação incremental de transporte SSH no Windows
- **Modo de autonomia**: supervisionado (sem commit, push ou deploy)

## Objetivo

Impedir que o Git Bash/MSYS2 converta origens POSIX (`/c/Users/...`) em caminhos com letra de
unidade (`C:/Users/...`) antes de executar `rsync`. O caractere `:` fazia o rsync interpretar a
origem local como remota e, diante do destino SSH, abortar com
`The source and destination cannot both be remote`.

## Implementação

1. `project-transport.sh` ganhou `project_transport_run_rsync()`, que executa o comando recebido
   com `MSYS_NO_PATHCONV=1` sem remontar ou interpolar seus argumentos.
2. As três invocações de rsync em `sync-core-to-project.sh` (core, contrato runtime e CLI) passaram
   pelo helper compartilhado.
3. `run_project_rsync()` em `synchronize-project.sh` passou pelo mesmo helper, cobrindo o projeto e
   o overlay distribuído.
4. `ProjectSshDeployReq034Test` ganhou guarda para o corpo do helper e para os quatro pontos de uso.
5. A armadilha e a solução obrigatória foram documentadas em `c2f-shell-and-windows-traps` nos
   cinco espelhos (`.codex`, `.claude`, `.cursor`, `.gemini`, `.github`). Os cinco arquivos ficaram
   com MD5 idêntico; `.claude/skills/*` permanece ignorado pela política existente do repositório.

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `bash -n` na biblioteca e nos dois scripts de projeto | **3/3 sem erro** |
| `ProjectSshDeployReq034Test` | **23/23**, 85 asserções, exit 0 |
| PHPUnit completo (`--order-by=default`) | **1.175/1.175**, 7.798 asserções, 4 skipped, exit 0 |
| Vitest completo | **29/29 arquivos, 423/423 testes**, exit 0 |
| `git diff --check` | OK (somente avisos LF→CRLF da configuração do worktree) |
| Paridade dos cinco espelhos da skill | MD5 idêntico |

### Ressalva do ambiente PHP

O PHP 8.5.8 do host carrega `pdo_sqlite` desabilitado e aponta `OPENSSL_CONF` para um arquivo
inexistente do XAMPP. A primeira rodada reproduziu os 16 erros ambientais já registrados no
BATCH-168. A rodada válida usou `PHP_INI_SCAN_DIR` temporário com `pdo_sqlite`/`sqlite3` e o
`openssl.cnf` da própria instalação WinGet; nenhum `php.ini` do operador foi alterado.

## Pendência de homologação

- Executar `c2f project:sync-core conn2flow-site-local` no transporte SSH do Lab e confirmar que a
  origem `/c/Users/...` permanece local para o rsync e que o erro de dois destinos remotos não volta.
- A homologação remota não foi executada neste lote porque o modo supervisionado proíbe deploy sem
  validação prévia do operador.

## Manutenção SDD

O arquivador oficial `ai:archive-sdd --keep=10 --repair-links` arquivou 2 intakes e 4 batches
antigos e reescreveu 10 links, restaurando a janela de 10 artefatos ativos. O comando retornou exit
1 após concluir os movimentos porque encontrou seis links órfãos preexistentes, sem alvo disponível
para reancoragem automática (dois itens de backlog, BATCH-116/131/135 e `batch-047`).
