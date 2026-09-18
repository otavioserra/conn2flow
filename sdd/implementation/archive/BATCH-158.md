# BATCH-158 — Paridade visual estrita entre página pública, pré-visualizador e editor visual

- **Status**: complete
- **Intake**: `sdd/human-requests/req-158.md`
- **Nota**: cadastrado originalmente como `req-156`; um agente concorrente reutilizou o
  número para outro escopo (BATCH-159) e o intake foi recuperado do git como `req-158`.
- **Data de abertura**: 2026-09-02
- **Modo**: supervisionado

## Escopo aprovado

1. Isolar o iframe do Editor HTML Visual (`editorHtmlVisual`) da UI administrativa, alinhando-o ao
   pré-visualizador.
2. Verificar a cascata da rota pública (`gestor.php`) contra os outros dois ambientes.
3. Garantir que a alternância entre as abas do CRUD preserve o mesmo estado de CSS.
4. Criar guardas automatizadas (Vitest e PHPUnit).
5. **Acrescentado pelo operador durante a execução**: eliminar as dependências de CDN em runtime nos
   arquivos tocados, conforme DEC-122/DEC-123/DEC-127 (BATCH-146/148/149).

## Diagnóstico medido

Medição A/B/C em Chromium 1440x900, com os artefatos reais (HTML e CSS do banco, layout
pré-compilado do projeto `conn2flow-site-local`) e as funções extraídas do arquivo-fonte.

### Causa 1 — o editor visual carregava a folha do Fomantic sem camada

O BATCH-156 removeu `semantic.min.css` do PREVIEW (`htmlEditorPreviewFrameworkIncludes()`) e não do
EDITOR VISUAL, onde a tag seguia escrita à mão nos dois ramos de `editorHtmlVisualConteudo()`
(páginas/componentes e layouts). A assimetria produziu exatamente o "ambiente intermediário"
descrito no intake.

São DUAS contaminações distintas, e cada uma exige um mecanismo próprio:

| Contaminação | Efeito medido (editor x preview) | Mecanismo |
| --- | --- | --- |
| Conflito de cascata: folha sem camada vence utilities em `@layer` | título 72 -> **24px**, peso 900 -> **700**, texto do CTA branco -> **rgb(65,131,196)** | `@import ... layer(c2f-editor-chrome)` |
| `html{font-size:14px}` redefine a unidade `rem` | encolhimento uniforme de **14/16 = 0,875**: 128 -> 112px, 48 -> 42px, 16 -> 14px | restaurar `html{font-size:16px}` fora de camada |

A segunda não é corrigível por camada: o Tailwind v4 dimensiona espaçamento, tipografia e raio em
`rem`, e não existe regra do Tailwind concorrendo por `html { font-size }` — a do Fomantic vence por
ausência de disputa. Aplicando só a camada, a paridade parou em 63px (72 x 0,875).

A folha NÃO podia simplesmente sair, como no preview: `#html-editor-modal` é um modal Fomantic e
`html-editor.js` chama `.modal()` sobre ele. Removê-la deixaria o modal de edição de texto, imagem e
código sem estilo.

### Causa 2 — rota pública: a divergência NÃO se reproduziu no ambiente real

O espelho do banco no container Docker (`conn2flow_site`) tem, para `teste-de-pagina`, um
`css_compiled` do **Tailwind v3** (`--tw-border-spacing-x`, `--tw-ordinal`, zero `@layer`) sobre um
layout pré-compilado em **v4.3.0** (`@layer properties/theme/base/components/utilities`) — 11 das 19
páginas Tailwind daquele espelho estão assim, e a medição sobre ele reproduzia `line-height` 90 ->
72px e gradiente interpolado em sRGB em vez de oklab.

**A rota pública real não tem esse defeito.** `https://conn2flow.local/teste-de-pagina/` é servida
pela VM (192.168.1.108, `deploy_mode: ssh`) e entrega Tailwind v4 com `@layer` e **zero CDNs**. O
banco do container é um espelho desatualizado. Medida contra a rota real, a paridade fecha em 15/15.

A guarda de procedência implementada (abaixo) é, portanto, **preventiva**, não correção de um defeito
ativo em produção local. Ela fecha uma assimetria real: `tailwind_recursos_fingerprint()` sempre
carimbou a versão do Tailwind no build OFFLINE, mas `gestor_css_procedencia_assinatura()` não a
considerava no derivado gravado ONLINE — então um `css_compiled` de outra major passaria por íntegro
enquanto HTML, CSS e baseline não mudassem, e o `css:rebuild` (que só opera sobre stale) nunca o
tocaria.

### Causa 3 — CDN em runtime nos pontos que o BATCH-146 não alcançou

O BATCH-146 varreu as tags montadas no PHP. As que o **cliente** monta escaparam: iframes por
`srcdoc`, Editbar e previews de widget traziam host e versão escritos à mão, paralelos ao registro.

| Arquivo | Tags removidas |
| --- | --- |
| `html-editor-interface.js` | Fomantic CSS/JS, jQuery, **14 do CodeMirror**, Quill, Tailwind Browser |
| `dashboard.toolbar.js` (Editbar) | jQuery (`cdnjs`), CodeMirror (`cdnjs`), Tailwind Browser (`unpkg`) |
| `publisher-index`, `pages-index`, `publisher-highlights`, `menus`, `galleries` | Tailwind Browser (`unpkg`), 1 cada |

Dois achados: `addon/edit/closetag.js` e `addon/edit/closebrackets.js` eram usados APENAS pelo iframe
do editor e não estavam no registro — migrar sem incluí-los os deixaria caindo no CDN em silêncio,
que é como `assets_externos_url()` degrada. E `@tailwindcss/browser` nunca esteve no registro: era o
último ponto do gestor preso a `unpkg.com`, com a versão repetida em sete lugares.

## Live Todo List

- [x] Classificar o intake e abrir o BATCH-158.
- [x] Medir os três ambientes em Chromium com os artefatos reais.
- [x] Isolar o chrome do editor por cascade layer e restaurar a unidade `rem`.
- [x] Declarar a ordem das camadas no editor e no preview.
- [x] Registrar `tailwindcss-browser` e os dois addons faltantes do CodeMirror.
- [x] Publicar o mapa de URLs do registro para o JavaScript e eliminar os CDNs em runtime.
- [x] Levar a versão do compilador para a assinatura de procedência do CSS derivado.
- [x] Guardas automatizadas (Vitest e PHPUnit).
- [x] Minificar derivados, sincronizar recursos e revalidar a paridade.
- [x] Homologar visualmente com o operador no CRUD autenticado.

## Implementação

- `htmlEditorVisualFrameworkIncludes()` (nova): mantém o Fomantic no iframe — o chrome do editor
  precisa dele — dentro de `@layer c2f-editor-chrome`, e restaura `html{font-size:16px}` fora de
  camada, para vencer o Fomantic e ainda perder para o CSS autoral, que é injetado depois.
- `htmlEditorLayerOrderDeclaration()` (nova): emite `@layer c2f-editor-chrome, properties, theme,
  base, components, utilities;` no topo do documento. Uma camada é ordenada pela primeira menção do
  nome; declarar a ordem faz a cascata deixar de depender da posição das folhas — e o Tailwind
  Browser registra camadas de forma assíncrona, quando compila. Aplicada aos DOIS ambientes.
- `assets-externos.php`: entrada `tailwindcss-browser` (versão única do compilador, consumida pelo
  editor, pela Editbar e pela procedência), `closetag.js`/`closebrackets.js` no CodeMirror, e as
  funções `assets_externos_urls_map()` / `assets_externos_urls_js()`.
- `gestor.php` publica `assetsUrls` no objeto `gestor` de toda página; `global.js` expõe
  `window.gestorAssets.url()`, com fallback pela janela pai (iframes `srcdoc` herdam a origem, não as
  variáveis) e retorno vazio quando o registro não conhece o arquivo — tag vazia falha de modo
  visível, CDN embutido recriaria a dependência em silêncio.
- `html_editor_tailwind_browser_version()` passou a ler o registro em vez de devolver um literal.
- `gestor_css_procedencia_assinatura()` acrescentou `compilador` e passou a `v2:`. Assinaturas `v1:`
  deixam de casar de propósito: o acervo carimbado sob a major anterior entra na fila do
  `css:rebuild`, sem migração de dados nem edição manual de banco. `gestor_css_compilador_versao()`
  resolve a versão pelo registro; `css-regenerar.php`, `css-auditoria.php` e `publisher-pages.php`
  informam o compilador ao recalcular — sem isso o gravador e o leitor divergiriam e TODO recurso
  ficaria permanentemente stale.

## Evidências

- **Paridade nos três ambientes, contra a rota pública REAL** (`https://conn2flow.local/teste-de-pagina/`),
  15 propriedades de `getComputedStyle` sobre os elementos do intake:
  - Editor Visual x Pré-visualizador: **15/15 idênticas**
  - Página Pública x Pré-visualizador: **15/15 idênticas**
- Antes da correção, o editor divergia em 15/15 (título 24px, CTA azul, `py-32` 112px). Com apenas a
  cascade layer, ainda divergia em 8/15 pelo fator `rem` de 0,875.
- **Zero CDNs** em `html-editor-interface.js`, `dashboard.toolbar.js`, `global.js` e nos 5 módulos de
  preview — verificado também nos derivados `.min.js`.
- `c2f assets:vendor`: 3 baixados, 58 já existiam, **0 falhas**.
- `c2f assets:minify`: 8 derivados regenerados; `--verificar` reporta **0 desatualizados**.
- `c2f resources:sync`: 2.844 recursos, 237 Tailwind em cache, **0 problemas**.
- Vitest completo: 28/28 arquivos, **393/393** testes (382 antes; +11).
- PHPUnit completo: **1.092/1.092** testes, 7.523 asserções, 4 skips esperados (1.073 antes; +19).

## Gates residuais

- **Homologação visual autenticada permanece humana**: o CRUD exige sessão administrativa. A medição
  cobriu a rota pública real e as composições montadas pelas funções reais do editor, mas não a tela
  `admin-paginas/editar/` operada por um usuário.
- `assets:publish` avisou que `PUBLIC_PATH` não está configurado — mesmo gate residual do BATCH-156,
  não regressão. As URLs seguem resolvendo pelo controlador `arquivo-estatico`.
- O espelho do banco no container Docker mantém 11 páginas com `css_compiled` do Tailwind v3. Não foi
  alterado: é espelho, não o ambiente servido. Com a assinatura `v2`, um `c2f css:rebuild` nesse
  ambiente as trata como stale e as regenera.
- `paginas.css_precompiled` da página alvo está vazio e o baseline do editor vem só do layout; o
  conteúdo da página é compilado em runtime pelo Tailwind Browser. Não houve divergência medida por
  causa disso, mas é uma assimetria conhecida entre build offline e runtime.

## Restrições

- Nenhum commit, push, release ou deploy sem autorização do Humano-no-Loop.
- Nenhuma cópia manual para mirrors; sincronização somente pelo pipeline oficial.
- Alterações preexistentes da árvore de trabalho foram preservadas.
