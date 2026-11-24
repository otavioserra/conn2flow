---
id: "BL-052"
title: "BL-052 — Sincronização de Banco (`atualizacoes-banco-de-dados.php`): preservação de `data_criacao` e `data_modificacao` em deploys de recursos e publicações"
status: "IN-DISCUSSION"
date: "2026-11-19"
author: "architect"
target_repo: "conn2flow"
summary_short: "Evitar que deploys que atualizam recursos/publicações sobrescrevam `data_modificacao` via `ON UPDATE CURRENT_TIMESTAMP` do MySQL e embaralhem a ordenação por data no `publisher-index` e `publisher-highlights`."
summary_medium: "Quando publicações vêm via `pages.json` / `sync_resources` sem `data_criacao` e `data_modificacao` explícitas, o deploy grava a data do momento do deploy e qualquer UPDATE posterior (inclusive apenas de `css_precompiled` ou `versao`) dispara `ON UPDATE CURRENT_TIMESTAMP` no MySQL, embaralhando a ordenação por data nos widgets do publicador. O Core já suporta `data_criacao`/`data_modificacao` no JSON (`req-086`), mas precisa proteger a data existente quando o JSON omite o campo e documentar/padronizar o uso explícito de datas fixas nos recursos."
---

# BL-052 — Sincronização de Banco: preservação de `data_criacao` e `data_modificacao` em deploys de recursos e publicações

- **Tipo:** Bug / Architecture / Core (`atualizacoes-banco-de-dados.php` + `atualizacao-dados-recursos.php`)
- **Status:** `IN-DISCUSSION`
- **Criado em:** 2026-11-19
- **Origem:** Engenheiro Chefe Humano (observado durante o `BATCH-003` do projeto `website-conn2flow` / `ia-parceria`, ao migrar episódios e materiais do publicador para `pages.json` + `sync_resources`).

---

## Sintoma

Ao versionar publicações (`paginas` com `publisher_id` + `publisher_pages`) em projetos do Conn2Flow v3.0:
1. Registros inseridos via `pages.json` / `sync_resources` sem `data_criacao` e `data_modificacao` recebem o timestamp do momento do deploy.
2. Em deploys subsequentes, qualquer `UPDATE` na linha — mesmo alterando apenas campos técnicos/derivados como `css_precompiled`, `versao`, `file_version` ou `checksum` — dispara o trigger `ON UPDATE CURRENT_TIMESTAMP` do MySQL/MariaDB em `data_modificacao`.
3. Como consequência, widgets baseados em ordenação cronológica (`publisher-highlights` com `rule: latest` e `publisher-index` com `order_by: date_desc` / `date_asc`) têm a ordem dos posts embaralhada a cada deploy, forçando workarounds de curadoria manual.

---

## Diagnóstico Técnico (Análise do Core)

1. **Suporte já existente para datas explícitas (`req-086`)**:
   - Em `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` (linhas 612–961, incluindo `pages.json` nas linhas 691–692), o compilador **já copia** `data_criacao` e `data_modificacao` do JSON para os `*Data.json` quando presentes.
   - Em `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php` (linhas 809–816, 957–963 e 1062–1068), o motor de sync **já força** a inclusão de `$row['data_modificacao']` no `UPDATE` quando o campo vem declarado no JSON, exatamente para neutralizar o `ON UPDATE CURRENT_TIMESTAMP` do MySQL.
   - **Implicação prática imediata**: Declarar explicitamente `data_criacao` e `data_modificacao` fixas nos registros de `pages.json` (e nos JSONs de `sync_resources`) **já funciona hoje** para estabilizar a data entre deploys sem sobrescrita.
2. **Lacuna restante no motor de sincronização**:
   - Quando `$row['data_modificacao']` **não** é informado no JSON (caso padrão da maioria dos recursos e publicações), o bloco `req-086` não atua (`isset($row['data_modificacao'])` é falso).
   - Assim, um `UPDATE` de deploy que toque apenas `css_precompiled`, `checksum` ou `versao` acaba sobrescrevendo `data_modificacao` no banco via `ON UPDATE CURRENT_TIMESTAMP`, mesmo sem nenhuma mudança editorial ou de data no JSON.
   - Adicionalmente, vale validar se o coletor de tabelas dinâmicas (`sync_resources` em `atualizacao-dados-recursos.php`) repassa `data_criacao` e `data_modificacao` consistentemente para tabelas como `publisher_pages`, `menus`, `publisher`, `publisher_highlights` e `publisher_index`.

---

## Solução Proposta (Diretrizes para Especificação)

1. **Preservação automática de `data_modificacao` existente no banco quando omitida no JSON**:
   - Em `atualizacoes-banco-de-dados.php`, nos três pontos de `UPDATE` (`req-086`), quando `$row['data_modificacao']` não estiver definido no JSON mas `$exist['data_modificacao']` existir no banco (especialmente quando o diff contiver apenas campos técnicos/derivados como `css_precompiled`, `checksum`, `versao`, `file_version`, `project`, ou sob política configurável em `schema-metadata.json`), incluir `$diff['data_modificacao'] = $exist['data_modificacao']` no `UPDATE` para impedir o `ON UPDATE CURRENT_TIMESTAMP` de alterar a data editorial.
2. **Paridade de `data_criacao` e `data_modificacao` em `sync_resources`**:
   - Confirmar e testar (PHPUnit) que `data_criacao` e `data_modificacao` declarados em `pages.json` e em tabelas dinâmicas (`sync_resources`, incluindo `publisher_pages`) são preservados integralmente na inserção (`INSERT`) e em re-deploys (`UPDATE`).
3. **Documentação nas Skills Canônicas (`c2f-resources-system` / `c2f-database-operations`)**:
   - Documentar explicitamente que projetos com publicações ordenadas por data devem declarar `data_criacao` e `data_modificacao` nos JSONs de recursos (ex.: `pages.json` e `publisher-pages.json`) para garantir ordenação determinística em qualquer ambiente limpo.

---

## Critério de Aceite Proposto

- Teste automatizado (PHPUnit) cobrindo:
  1. Registro com `data_criacao` e `data_modificacao` fixas no JSON mantém exatamente as mesmas datas após `INSERT` e após múltiplos `UPDATE`s subsequentes.
  2. Registro sem `data_modificacao` no JSON que sofre `UPDATE` apenas de `css_precompiled`/`checksum`/`versao` mantém o `data_modificacao` original gravado no banco (não dispara `ON UPDATE CURRENT_TIMESTAMP`).
  3. Ordenação `date_desc` / `date_asc` em `publisher-index` e `publisher-highlights` permanece determinística após múltiplos ciclos de `./c2f project:deploy`.
