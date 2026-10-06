# BATCH-238: Biblioteca de Estado e Acesso Controlado a Variáveis Globais (req-229)

Implementação do meio de campo controlado para desacoplar a manipulação indiscriminada de `$_GESTOR` sem quebrar o código procedural existente, servindo como fundação para a futura refatoração OOP da Linha 3.1.

- **Status**: `in-review`
- **Requisição associada**: [req-229.md](../../human-requests/archive/req-229.md)
- **Branch**: `feat/req-229`
- **Data**: 2026-10-04

## Escopo e Componentes

1. **Camada de Estado Controlado (`GestorState`)**:
   - Classe singleton/estática encapsulando `$_GESTOR` com acesso direto e atômico.
   - Suporte a notação pontuada (dot notation) em leitura (`get`), escrita (`set`) e verificação (`has`).
   - Suporte a fallback em leitura.
   - Allowlist de chaves somente-leitura após boot: `raiz-absoluta`, `url-raiz`, `linguagem-codigo`, `versao-num`.
   - Interceptação e log/auditoria ao tentar sobrescrever chaves protegidas com bloqueio (`false`).
   - Extração de fatias delimitadas de contexto via `contexto()` (`modulo`, `usuario`, `sistema`, etc.).
   - API de auditoria e controle dinâmico (`proteger()`, `desproteger()`, `isProtegida()`, `getAuditoria()`, `reset()`).

2. **Fachada Procedural Pública**:
   - `gestor_get(string $chave, mixed $padrao = null): mixed`
   - `gestor_set(string $chave, mixed $valor): bool`
   - `gestor_has(string $chave): bool`
   - `gestor_contexto(string $escopo): array`

3. **Retrocompatibilidade Integral 100%**:
   - O array superglobal `$_GESTOR` permanece como fonte subjacente; códigos legados continuam operando normalmente sem necessidade de migração forçada imediata.
   - Sincronização bidirecional em tempo real garantida.

4. **Suíte de Testes Automatizados no PHPUnit**:
   - `tests/Unit/PHP/GestorStateTest.php` cobrindo leitura, escrita, notação pontuada, fallback, integridade, proteção de chaves e contexto (28 testes, 93 asserções).

5. **Documentação e Governança**:
   - Referência de bibliotecas em `ai-workspace/pt-br/docs/reference/libraries/gestor.md` e `ai-workspace/en/docs/reference/libraries/gestor.md`.
   - Conceito arquitetural em `ai-workspace/pt-br/docs/concepts/global-variables.md` e `ai-workspace/en/docs/concepts/global-variables.md`.
   - Atualização da skill `c2f-global-variables` (com espelhamento em `.gemini`, `.codex`, `.cursor`, `.github`).

## Commits (core)

| Commit | Conteúdo |
|---|---|
| `1f844cac` | `feat(core): GestorState e funcoes controladas gestor_get/set/has/contexto (req-229 / BATCH-238)` |

## Evidências de Validação

- [x] **PHPUnit:** 28 testes e 93 asserções em `tests/Unit/PHP/GestorStateTest.php`, 100% aprovados sem falhas ou warnings.
- [x] **Retrocompatibilidade:** Modificações diretas em `$_GESTOR` lidas por `gestor_get()` e mutações via `gestor_set()` visíveis em `$_GESTOR`.
- [x] **Proteção de Chaves:** Tentativas de sobrescrever `raiz-absoluta`, `url-raiz`, `linguagem-codigo` e `versao-num` barradas e auditadas.
- [x] **Zero Regressão:** Testes existentes de bibliotecas e controladores validados sem quebras.
- [x] **Lint de Sintaxe:** `php -l gestor/bibliotecas/gestor.php` limpo e `git diff --check` sem erros.
