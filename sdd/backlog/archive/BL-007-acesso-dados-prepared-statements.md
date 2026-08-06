# BL-007 — Acesso a dados por concatenação de SQL e fallback de escape frágil

- **Tipo**: Architecture / Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA (defesa em profundidade) — ALTA se surgir um único ponto sem escape
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `bibliotecas/banco.php` e todos os consumidores (`banco_select_name`, `banco_delete`, etc.)

## Contexto observado

O caminho principal de acesso a dados monta SQL por **concatenação de string**: `banco_select_name($campos, $tabela, $extra)` recebe o `WHERE` como texto já montado ([banco.php:524](../../gestor/bibliotecas/banco.php)). Em todo o core os `WHERE` são interpolados manualmente com `banco_escape_field(...)`. Isso funciona **enquanto cada valor for escapado corretamente** — mas depende de disciplina humana em centenas de call-sites; um único esquecimento vira SQL injection.

Dois agravantes:

1. **Fallback de escape inseguro**: quando não há conexão mysqli, `banco_escape_field()` cai em `addslashes()` ([banco.php:44](../../gestor/bibliotecas/banco.php)). `addslashes` não é equivalente a `mysqli_real_escape_string` (não trata charset/multibyte) — escape incorreto em contextos sem conexão ativa (testes, execução distribuída).
2. **Prepared statements já existem, mas só na periferia**: `banco-v2.php` e os controladores de migração/plugins usam `prepare()`; o acesso a dados de runtime dos módulos, não.

## Proposta de melhoria (a validar)

1. Padronizar o acesso a dados de runtime em **prepared statements com bind** (consolidar em torno de `banco-v2.php`) e migrar os módulos gradualmente.
2. Enquanto durar a migração, oferecer uma API de `where` parametrizado (array de condições) em vez de string crua, reduzindo interpolação manual.
3. Remover o fallback `addslashes`: se não há conexão, o escape deve falhar de forma explícita em vez de silenciosamente inseguro.
4. Adicionar testes com payloads de injeção (aspas, multibyte GBK) nos helpers.

## Critérios de aceite (rascunho)

- Novos acessos a dados usam bind de parâmetros; caminho legado documentado e em migração.
- Sem fallback de escape silencioso e inseguro.
- Suite de teste cobrindo tentativas de injeção.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
