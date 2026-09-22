# BATCH-179: Sincronização Automática da Tabela `hooks` no Pipeline de Deploy de Projeto (`project:update-all`)

Execução da [req-174](../human-requests/req-174.md).

---

## Atividades e Checklist

### 1. [ ] Controlador de Banco: Integração com `atualizacoes_hooks_sincronizar()`
- [ ] Em `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`:
  - Carregar `atualizacoes-hooks.php` e `hooks.php`.
  - Invocar `atualizacoes_hooks_sincronizar()` após a sincronização de dados e migrações.
  - Assegurar execução tanto no fluxo padrão quanto no modo `--project`.
  - Exibir métricas de hooks no resumo final (`relatorioFinal`).

### 2. [ ] CLI do Framework: Comando `project:sync-hooks`
- [ ] Criar comando `ProjectSyncHooksCommand` ou integrar atalho `c2f project:sync-hooks <projeto-id>`.
- [ ] Implementar suporte aos 3 modos de execução (`ssh`, `host`, `docker`) com elevação segura (`sudo -u <tenant> sh -c`).

### 3. [ ] Validação e Testes
- [ ] Criar testes unitários no PHPUnit cobrindo:
  - Invocação e idempotência da sincronização de hooks durante a rotina de banco com `--project`.
  - Comando CLI `project:sync-hooks`.
- [ ] Executar suítes locais:
  - `composer test` (PHPUnit completo).
  - `npx vitest run` (Vitest completo).
  - `git diff --check`.
- [ ] Homologar no Lab HestiaCP (`conn2flow.local` / `snapphoton-local`):
  - Executar `project:update-all` e verificar que a tabela `hooks` é atualizada sem intervenção manual.

---

## Critérios de Aceite e Validação

1. **Sincronização Automática**: Hooks de módulos de projetos são gravados na tabela `hooks` automaticamente em qualquer deploy de projeto (`project:update-all` ou `project:sync-db`).
2. **Relatório Claro**: Resumo final da etapa de banco reporta a quantidade de hooks sincronizados.
3. **CLI Funcional**: Comando `project:sync-hooks` opera com sucesso em ambientes locais e remotos SSH.
4. **Zero Regressões**: Suíte de testes 100% verde.

---

## Evidências de Execução

(A preencher pelo executor ao concluir)

## Estado

`ready-for-intake`
