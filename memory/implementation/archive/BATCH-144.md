# BATCH-144 — Procedência do CSS derivado, CSS de conteúdo do Quill e auditoria/regeneração

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-141.md` + reanálise conduzida com o Engenheiro Chefe
- **Change request**: `sdd/change-requests/CR-002-procedencia-css-derivado.md`
- **Data de abertura**: 2026-08-28
- **Classificação**: arquitetura de CSS (autoria x derivado), runtime público, política de atualização

## Diagnóstico (medido, não inferido)

O runtime serve **tudo do banco** (`gestor.php:2782`; disco só em `DEVELOPMENT_ENV=true`). O
compilador offline varre **arquivos em `resources/`**. Como o editor online grava só no banco, o CSS
entregue é compilado de um HTML que não é o HTML entregue.

Medições no `transformamp` (`DEVELOPMENT_ENV=false`, portanto caminho de produção):

| Evidência | Valor |
| --- | --- |
| Classes usadas sem CSS na resposta HTTP | 80/248 na home; 85/182 em `/artigos/teste-de-pagina/` |
| Recursos Tailwind com ao menos uma classe sem CSS | **1.279 de 1.410 (91%)** |
| `paginas` de publicação | `css_precompiled=0` e `css_compiled=0` — nascem no banco e o CLI não as vê |
| `preserve_on_user_modified` | preserva `css_compiled` (um DERIVADO) e exclui `css_precompiled` |
| Recursos apoiados em `tailwind_sources` de PHP/JS | 40, **todos do `perfil-usuario`** |

O HTML da página de publicação **já contém** as classes do template (`border-r-2`, `ml-auto`,
`text-right`, `pr-4`): o template é expandido no salvamento. Isso derruba o Módulo 1 do intake —
não falta carregar o CSS do template, falta o CSS **da própria página**.

## Correções entregues

### 1. CSS de conteúdo do Quill como asset de sistema

A página pública **nunca** carregava CSS do Quill. Medido em `/artigos/teste-de-artigo/`:
`.ql-indent-1` chegava sem regra alguma. As classes que funcionavam vinham por acidente de um
`css_compiled` contaminado em 2026-06 — daí o "tem hora que alinha, tem hora que não": dependia de
qual botão de formatação o usuário tinha usado.

- `gestor/assets/interface/quill-content.css`: 134 regras extraídas do `quill.snow.css` v2.0.3
  oficial, só o que renderiza CONTEÚDO (sem toolbar, sem `!important`).
- `gestor_quill_conteudo_detectar()` + `gestor_quill_assets()` (puras) e `gestor_pagina_quill()`,
  no mesmo padrão condicional do PDF.js: entra só onde há conteúdo Quill.
- A folha do Quill no editor recebeu `data-c2f-css-role="quill"`, o que a tira do alcance da captura.

### 2. Assinatura de procedência

`gestor_css_procedencia_assinatura()` / `gestor_css_procedencia_valida()` (puras) e a coluna
`css_source_hash` nas quatro tabelas de recurso. Assinatura ausente conta como stale de propósito:
o acervo anterior realmente não tem procedência conhecida.

### 3. Atomicidade na atualização de sistema

`aplicarPoliticaProcedenciaCss()` invalida a assinatura quando o deploy toca autoria ou derivado
(inclusive pela coluna espelho `<campo>_updated`). **Não apaga CSS** — em produção não há como
recompilar na hora, e apagar deixaria a página sem estilo. Aplicada nos três caminhos de update.

### 4. Auditoria e regeneração

- `c2f css:audit` — mede procedência e cobertura por tabela, só para `framework_css=tailwindcss`
  (contar recursos Fomantic, que recebem a folha por CDN, inflava o número e apontava para o
  problema errado). Também lista HTML/classe embutidos em PHP/JS.
- `c2f css:rebuild` — compila o Tailwind contra o HTML **do banco**, honra `tailwind_sources` e,
  com `--url`, usa o **HTML renderizado** como fonte adicional: a resposta HTTP já traz toda classe
  aplicada, então essa fonte não depende de ninguém declarar nada.

## Decisões

- **`html`/`css` são autoria; `css_precompiled`/`css_compiled` são derivados** (correção do
  Engenheiro Chefe à proposta inicial). O derivado é recalculável e não deve ser preservado como se
  fosse trabalho do usuário.
- **Invalidar, nunca apagar**: stale mantém o CSS atual e ganha um sinal.
- **A métrica ignora `group`/`peer`**: são marcadores de variante sem regra própria, e contá-los
  acusava piora em 4 páginas onde nada piorou.
- **Fonte que não exige declaração**: o HTML renderizado torna `tailwind_sources` dispensável no
  caso geral. A declaração continua honrada, mas deixa de ser a única saída.

## Resultados medidos

| Alvo | Antes | Depois |
| --- | --- | --- |
| `/artigos/teste-de-pagina/` (HTTP real) | 85 classes sem CSS | **13** |
| Lote de 80 páginas | 1.538 classes sem CSS | **291 (-81%)** |
| `layouts` stale | 14 | **0** |
| Classes `ql-*` sem CSS | `.ql-indent-1` sem regra | **todas cobertas** |

As 13 restantes da página são `prose-*`: o projeto usa essas classes mas **não tem o plugin
`@tailwindcss/typography` instalado** — dependência ausente, não falha do mecanismo.

## Dívida identificada (para lote próprio)

`gestor/modulos/perfil-usuario/perfil-usuario.js` monta **26 classes Tailwind em runtime**
(`classList.add('w-0','bg-slate-300')`), violando a norma de que PHP/JS não carregam HTML nem
classe. É a razão de existirem as 40 declarações `tailwind_sources` do módulo — e o motivo de ele
quebrar a cada atualização. O `perfil-usuario.php` **já está limpo** (zero classes): a declaração
apontando para ele é obsoleta. Outros 6 arquivos têm classes semânticas próprias, menos críticas.

## Fase 4 — conteúdo novo já nasce correto

Todo módulo que grava recurso passou a carimbar a procedência do CSS no salvamento, nos **10
pontos** de gravação (adicionar/editar/clonar):

| Módulo | pontos carimbados |
| --- | --- |
| `admin-paginas` | 3 |
| `admin-layouts` | 2 |
| `admin-componentes` | 2 |
| `admin-templates` | 3 |
| `publisher-pages` | 3 (adicionar, editar, clonar) |

- `gestor_css_procedencia_para_recurso()` resolve o baseline pelo layout e devolve a assinatura
  pronta; usa `gestor_schema_campo_existe()` (com cache e try/catch), para que um erro de schema
  jamais derrube o salvamento por causa do carimbo.
- No `editar`, o carimbo cai em `banco_select_campos_antes()` quando o campo não veio no POST —
  assinar contra vazio marcaria como stale um recurso íntegro.
- `publisher_pages_css_derivado()` faz a publicação **herdar o `css_precompiled` do template** que a
  gerou. Publicação nasce no banco, nunca teve arquivo em `resources/`, e por isso o compilador
  offline nunca a alcançava: era a razão de `css_precompiled=0` e `css_compiled=0` em toda
  publicação. O HTML da publicação é o template com valores substituídos, e substituir valor não
  cria classe — o CSS do template cobre a página.
- Um teste ESTRUTURAL trava a regressão provável, que é omissão: qualquer novo fluxo de gravação
  sem carimbo quebra a suíte.

## Fase 5 — a regeneração vira etapa do pipeline

Rodar `css:rebuild` como passo separado era "alguém precisa lembrar" — a mesma classe de falha que
este lote combate. A etapa entrou nos dois pipelines:

```
c2f manager:update-all       -> 1/4 Resources  2/4 Files  3/4 Database  4/4 CSS rebuild
c2f project:update-all <id>  -> 1/6 Core  2/6 DB  3/6 Resources  4/6 Files  5/6 DB  6/6 CSS rebuild
```

- **Não-fatal**: se a regeneração falhar (sem Tailwind CLI, sem coluna de procedência), avisa e o
  pipeline segue — as etapas essenciais já foram aplicadas, e abortar faria um pipeline bem-sucedido
  parecer quebrado.
- Sem `--project`, o alvo passa a ser o ambiente de TESTE do sistema (`conn2flow-gestor`); o
  `gestor/` do repositório é código-fonte, não tem `.env` nem banco.

## Trava de alvo por `local` (environment.json)

`devProjects.<id>.local` é a autoridade sobre gravar sem autorização. `css:rebuild` imprime
`projeto | local | url` antes de escrever e recusa `local: false` sem `--confirmar-remoto`. Motivo:
os pares `<projeto>` / `<projeto>-local` compartilham o mesmo `path_tests`, então comandos de leitura
parecem idênticos enquanto o deploy envia para `PROJECT_URL/_api/project/update`.

## Bug corrigido: `$fontesExtras` sem parâmetro na assinatura

`regenerarCompilar()` perdeu o parâmetro numa edição intermediária. O `foreach` iterava sobre
variável inexistente, o PHP emitia apenas warning — engolido pelo log em background — e as
`tailwind_sources` **nunca eram aplicadas**. Achado ao rodar em foreground depois que o operador
apontou que execuções concorrentes estavam travando o processo. Blindado por teste que exige o
parâmetro na assinatura.

## Validação

`sdd/validation/VALIDATION-CHECKLIST.md#batch-144`. PHPUnit **865/865**, Vitest **366/366**.

## Pendente

- Regenerar o acervo inteiro (rodado em 105 recursos; o restante é tempo de CLI).
- **Homologar o carimbo pela TELA**: a fase 4 está coberta por teste unitário e estrutural, mas não
  foi verificada salvando pela interface — o POST reconstruído por script não representa o
  formulário fielmente.
- Baseline do editor incluir as dependências, como o docblock de
  `html_editor_css_precompiled_baseline()` já promete (hoje concatena só layout + recurso).
- Eliminar as classes montadas em runtime no `perfil-usuario.js` (26) e remover a declaração
  obsoleta que aponta para o `perfil-usuario.php`, que já está limpo.
