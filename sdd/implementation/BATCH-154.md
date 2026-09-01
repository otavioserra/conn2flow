# BATCH-154 — Paridade bare-metal no `project:update-all` (req-152)

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-152.md`
- **Data**: 2026-09-01
- **Classificação**: pipeline de projeto, Docker/bare-metal, bootstrap CLI

---

## 1. Diagnóstico causal

`ai-workspace/en/scripts/dev-environment/updates-manager-database.sh` tratava `dockerPath` como obrigatório e encerrava sempre com `docker exec conn2flow-app`. Isso contradizia a configuração já aceita pelo resolvedor de projetos: no HestiaCP, `snapphoton-lab.target` aponta diretamente para o Gestor do tenant e não existe container por projeto.

A homologação incremental tornou visíveis dois contratos adicionais:

1. o `config.php` e suas bibliotecas precisam do diretório de trabalho na raiz do Gestor durante a execução CLI host;
2. `gestor/phinx.php` é requerido dentro de `migracoes()`, portanto precisa vincular `$_GESTOR` ao escopo global antes de carregar `config.php`.

## 2. Implementação

- `dockerPath` explícito: modo Docker;
- `target/path_tests` sob `dev-environment/data/sites/`: `dockerPath` derivado, modo Docker;
- outro `target/path_tests` contendo o atualizador PHP: modo host;
- qualquer outro cenário: erro explícito, sem fallback por adivinhação.

No modo host, o comando roda em subshell com `cd "$PATH_HOST"`; no modo Docker, a chamada continua em `conn2flow-app`, agora sem `bash -c`. Os argumentos são um array Bash e o identificador do projeto é validado antes de entrar nas expressões jq.

`gestor/phinx.php` declara `global $_GESTOR` antes do `require_once $configPath`. A correção é restrita ao contexto de produção do Phinx e preserva o caminho especial do instalador.

## 3. Evidências

### Automatizadas locais

- `bash -n updates-manager-database.sh`: aprovado.
- Testes focados: **10/10**, 30 asserções.
- PHPUnit completa: **1008/1008**, 4.347 asserções, 4 skips esperados, 0 falhas.
- `php -l gestor/phinx.php`: aprovado.
- Caminho Docker direto: `docker exec conn2flow-app php ... --help` aprovado, sem mutação de banco.

### Runtime Lab HestiaCP

Alvo: `snapphoton-lab` em `192.168.1.108`.

- Tentativa 1: confirmou a seleção host, mas revelou o diretório de trabalho incorreto.
- Tentativa 2: confirmou o diretório do Gestor, mas revelou o escopo parcial de `$_GESTOR` no bootstrap Phinx.
- Tentativa 3: pipeline **5/5 aprovado**, com migrações, recursos, arquivos e `TRANSACAO_COMMIT`; banco `+19 ~0 =0`.
- Repetição idempotente: pipeline **5/5 aprovado**, banco `+0 ~0 =19`.

Backups reversíveis mantidos exclusivamente no Lab:

- `/root/updates-manager-database.before-req152.20260901T1900.sh`
- `/root/phinx.before-req152.20260901T1900.php`

Nenhuma credencial foi registrada no repositório ou nas evidências.
