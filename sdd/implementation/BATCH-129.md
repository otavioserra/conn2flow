# BATCH-129: Extrator Semântico de Tokens do Tailwind para o Assistente de IA no Editor HTML

Intake: [req-127.md](../human-requests/req-127.md)
Validação: [VALIDATION-CHECKLIST.md#batch-129](../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation`

O Assistente de IA recebia `{{html}}`, `{{css}}` e `{{framework_css}}` — e `{{framework_css}}` é a
string `tailwindcss`, que não diz nada sobre a marca do projeto. O modelo respondia com `bg-red-600`
onde o projeto tem `bg-mp-red`, e o operador reescrevia a mão a cada interação. Mandar o
`browser-contract.css` bruto resolveria o problema e custaria 78 KB (~20 mil tokens) por interação.

Este lote entrega o meio-termo: um extrator que devolve ~1,5 KB com as declarações do `@theme` que
viram utility e os nomes das classes de `@layer components`.

---

## Atividades Técnicas

1. **[x] Extrator semântico (`gestor/bibliotecas/html-editor.php`):**
   - `html_editor_ia_extrair_tokens_tema(?string $caminhoContrato = null, ?int $limiteBytes = null)`
     resolve o contrato pelo mesmo caminho do runtime do Tailwind Browser
     (`html_editor_tailwind_browser_contract()`, req-114/req-117): `contents/` do projeto na frente
     de `assets/` do core.
   - `html_editor_ia_tokens_tema_compilar()` é a parte PURA (string entra, string sai) — a mesma
     separação do par `html_editor_css_precompiled_concatenar()` / `..._baseline()` do req-117.
   - Auxiliares: `..._limite()` (orçamento), `..._namespaces()` (allowlist),
     `..._valor_util()` (corte por forma do valor), `..._bloco()` (recorte balanceado do `@theme`),
     `..._componentes()` (classes de `@layer components`).

2. **[x] Injeção no payload (`html_editor_ajax_ia_requests`):**
   - `html_editor_ia_modo_theme_tokens_aplicar()` troca `{{theme_tokens}}` e retira os marcadores; o
     bloco INTEIRO sai quando não há contrato.
   - `{{css_compiled}}` opt-in, resumido por `html_editor_ia_css_classes_resumir()`.

3. **[x] Modos de IA (fonte em `resources/`, não no JSON compilado):**
   - Diretriz adicionada nos 6 `.md`: `paginas`, `paginas-editbar` (admin-paginas) e `componentes`
     (admin-componentes), em `pt-br` e `en`.
   - `version` de `1.0` para `1.1` nos `ai_modes` dos dois módulos; `ModosIaData.json` recompilado
     por `c2f resources:sync`.

4. **[x] Testes:**
   - `tests/Unit/PHP/HtmlEditorIaThemeTokensTest.php` (39 casos) — extrator e bloco condicional.
   - `tests/Integration/HtmlEditorIaRequestsThemeTokensTest.php` (11 casos) — payload real do
     endpoint nos três escopos de edição, com dublê de `ia_enviar_prompt()`.

---

## Decisões tomadas na implementação

- **A ordem de corte é round-robin entre namespaces, não sequencial.** Medido no `transformamp`: 63
  cores contra 3 `--shadow-*`. Na varredura sequencial as cores consomem o orçamento inteiro e o
  outro namespace do contrato some por ser pequeno. Uma rodada por namespace resolve, e a saída
  volta à ordem natural — o round-robin decide QUEM entra, nunca em que ordem sai.

- **O corte de valor é por FORMA, não por nome.** Qualquer valor com `data:`, `url()` longo ou mais
  de 120 bytes cai fora. Contrato futuro que embuta asset cai na mesma regra sem allowlist nova. É
  o que tira as `--art-*-mask` do `transformamp` (uma só passa de 700 bytes e não vira utility).

- **A allowlist de namespaces vai além dos três do intake.** O intake cita `--color-*`, `--font-*` e
  `--spacing-*`; entraram também `--text-`, `--radius-`, `--shadow-`, `--breakpoint-`, `--container-`,
  `--leading-` e `--tracking-`, porque todos viram utility com nome próprio no v4 e sem eles a IA
  escreve o valor arbitrário `rounded-[12px]` em vez do token da marca. Medido: nos quatro contratos
  do ambiente local esses namespaces somados não passam de 10 declarações.

- **A diretriz é bloco condicional, com o par de marcadores da convenção do core.** Projeto sem
  contrato, ou fora do Tailwind, tem a seção inteira removida por `modelo_tag_del()`. Mandar o
  modelo "usar prioritariamente" uma lista que chega em branco é pior do que não mandar nada — ele
  preenche a lacuna inventando.

- **A frase-guia fica no `.md`, não no PHP.** O bloco injetado é CSS puro; a instrução que ensina a
  derivar a utility do token (`--color-mp-red` → `bg-mp-red`) é texto de produto e vive no artefato
  multi-idioma. Sem ela o modelo escreve `style="color: var(--color-mp-red)"`, que funciona e não é
  o que o projeto usa.

- **`{{css_compiled}}` é opt-in e resumido.** O valor cru é o output inteiro do Tailwind e devolveria
  o payload à casa dos 20 mil tokens. Nenhum modo do core declara a tag; ela existe para o operador
  que quiser o contexto num modo próprio. As utilities do v4 escapam o seletor (`.lg\:flex`), e o
  resumo desescapa — é o nome desescapado que vai no atributo `class`.

---

## Findings do review, corrigidos nesta rodada

Dois casos do próprio lote, ambos no caminho "projeto sem contrato de tema". O modo de IA é
**editável no painel** e vive no banco, então nem os marcadores nem a tag podem ser tratados como
garantidos:

1. **Tag sem marcadores vazava literal.** Modo em que o operador apagou o par mas manteve
   `{{theme_tokens}}`: `modelo_tag_del()` não encontrava o que cortar e a tag saía escrita por
   extenso dentro do bloco ```` ```css ```` do payload. A troca por vazio passou a acontecer sempre,
   depois do corte.
2. **Marcador invertido cortava do lugar errado.** `modelo_tag_del()` opera por posição: com
   `> -->` antes de `< -->`, o corte levaria o resto do prompt junto. Passou a haver guard de ordem
   (`$pos_in < $pos_out`) antes de chamar a função.

Cobertos por `testTagSemMarcadoresNaoVazaLiteralQuandoNaoHaTokens`,
`testMarcadorInvertidoNaoLevaORestoDoPromptJunto` e `testTagSemMarcadoresAindaRecebeOsTokens`.


## Fora do escopo (registrado, não corrigido)

- **`versao` de `ai_modes` e `ai_prompts` nunca incrementa.** Em
  `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`, `carregarDadosExistentes()`
  indexa os existentes sob as chaves `modos_ia` / `prompts_ia`, mas `versaoChecksumPrompt()` é
  chamada com `'ai_modes'` / `'ai_prompts'` — o `isset($existentes[$tipo][$key])` é sempre falso e a
  versão fica em `1` para sempre. **Não bloqueia este lote**: o sync do banco decide o que atualizar
  pelo md5 do arquivo `ModosIaData.json` e faz UPSERT campo a campo, e `modos_ia` tem
  `preserve_on_user_modified: []`, então o prompt novo chega ao banco de qualquer forma. Corrigir
  incrementaria a versão dos 24 modos e 32 prompts de uma vez, o que é ruído fora deste lote.

- **`{{html_extra_head}}` nunca é substituída no payload.** O modo `componentes` (pt-br e en) declara
  a tag num bloco ```` ```html-extra-head ````, mas `html_editor_ajax_ia_requests()` só troca
  `{{html}}`, `{{css}}` e `{{framework_css}}` — a tag chega ao modelo escrita por extenso. É defeito
  pré-existente e **não é one-liner**: `iaRequestsData()` em `html-editor-interface.js` não envia o
  campo, então corrigir de verdade significa mexer também no front. Fora do escopo do req-127, que
  trata de contexto de tema; registrado aqui para o próximo lote que tocar o Assistente.
