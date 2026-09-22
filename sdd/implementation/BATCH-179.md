# BATCH-179: Sincronização Automática da Tabela `hooks` no Pipeline de Deploy de Projeto (`project:update-all`)

Execução da [req-174](../human-requests/req-174.md).

---

## Atividades e Checklist

### 1. [x] Controlador de Banco: Integração com `atualizacoes_hooks_sincronizar()`
- [x] Em `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`:
  - Carregar `atualizacoes-hooks.php` e `hooks.php`.
  - Invocar `atualizacoes_hooks_sincronizar()` após a sincronização de dados e migrações.
  - Assegurar execução tanto no fluxo padrão quanto no modo `--project`.
  - Exibir métricas de hooks no resumo final (`relatorioFinal`).

### 2. [x] CLI do Framework: Comando `project:sync-hooks`
- [x] Criar comando `ProjectSyncHooksCommand` ou integrar atalho `c2f project:sync-hooks <projeto-id>`.
- [x] Implementar suporte aos 3 modos de execução (`ssh`, `host`, `docker`) com elevação segura (`sudo -u <tenant> sh -c`).

### 3. [x] Validação e Testes
- [x] Criar testes unitários no PHPUnit cobrindo:
  - Invocação e idempotência da sincronização de hooks durante a rotina de banco com `--project`.
  - Comando CLI `project:sync-hooks`.
- [x] Executar suítes locais:
  - `composer test` (PHPUnit completo).
  - `npx vitest run` (Vitest completo).
  - `git diff --check`.
- [x] Homologar no Lab HestiaCP (`conn2flow.local` / `conn2flow-site-local`):
  - Executar `project:update-all` e verificar que a tabela `hooks` é atualizada sem intervenção manual.

---

## Critérios de Aceite e Validação

1. **Sincronização Automática**: Hooks de módulos de projetos são gravados na tabela `hooks` automaticamente em qualquer deploy de projeto (`project:update-all` ou `project:sync-db`).
2. **Relatório Claro**: Resumo final da etapa de banco reporta a quantidade de hooks sincronizados.
3. **CLI Funcional**: Comando `project:sync-hooks` opera com sucesso em ambientes locais e remotos SSH.
4. **Zero Regressões**: Suíte de testes 100% verde.

---

## Evidências de Execução

- `ProjectHooksSyncReq174Test`: **3 testes / 27 asserções**, exit 0. Cobertura comportamental da idempotência, expurgo ao remover a chave `hooks`, integração no fluxo `--project`, relatório e registro do comando dedicado.
- `composer test` com `OPENSSL_CONF` explícito: **1.205 testes / 7.908 asserções**, 4 skips de ambiente, exit 0.
- `npx vitest run`: **30 arquivos / 426 testes**, exit 0.
- Sintaxe: `php -l` em todos os PHP alterados, `bash -n` no transporte e parse dos dois JSONs de idioma, todos com exit 0.
- `php cli/c2f.php help`: `project:sync-hooks (sync:project-hooks)` registrado e visível.
- `git diff --check`: exit 0.
- Homologação Lab: Deploy executado em `conn2flow-site-local` com sucesso (`HTTP Code: 200`, `Hooks => total=80 (módulos=12, plugins=0, projeto=68)`, `Σ TOTAL => +0 ~0 =0`). Confirmação em runtime de sincronização automática e métricas no relatório final sem intervenção manual.
- Review findings-first: nenhuma falha funcional, regressão ou drift bloqueante.

## Estado

complete
