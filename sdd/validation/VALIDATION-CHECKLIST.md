# Validation Checklist

Use este checklist para validar batches no `conn2flow` sem perder de vista o baseline operacional do repositório.

## Onboarding SDD repo-wide

- [x] `CLAUDE.md` instalado na raiz do repositório
- [x] `.claude/` instalado com agents, rules, skills e settings do Claude Code
- [x] `.github/copilot-instructions.md` instalado
- [x] `.github/instructions/`, `.github/prompts/`, `.github/skills/` e `.github/agents/` com artefatos SDD do Copilot
- [x] `sdd/scripts/hooks/` criado com hooks de sessão SDD
- [x] `sdd/human-requests/` ativo
- [x] `sdd/README.md`, `process/`, `implementation/`, `validation/` e `decisions/` criados
- [x] `sdd/00-baseline-architecture.md` criado com preservação do legado

## Checklist mínimo por batch

- [ ] O batch está registrado em `sdd/implementation/BATCH-INDEX.md`
- [ ] O impacto foi comparado contra `sdd/00-baseline-architecture.md`
- [ ] A menor validação executável do slice foi definida antes de editar mais do que o necessário
- [ ] Scripts, tasks ou paths alterados continuam coerentes com `dev-environment/data/environment.json`
- [ ] Não houve reescrita ampla do legado sem mudança normativa aprovada
- [ ] O review findings-first foi feito quando a mudança ficou pronta para avaliação

## Quando o batch tocar operação local

- [ ] Validar a task do VS Code mais próxima ou o script subjacente equivalente
- [ ] Se tocar Docker, checar status, logs ou execução correspondente
- [ ] Se tocar sincronização de projeto, validar source/target/path no `environment.json`
- [ ] Se tocar plugins, validar o fluxo na árvore `dev-plugins/`

## Evidência mínima esperada

- comando executado ou checagem objetiva usada
- resultado observado
- pendências ou riscos restantes

## Regra final

Se não houver validação executável no slice atual, o batch deve registrar explicitamente por que a validação ficou documental ou manual.

## Validações de Batches Arquivados

Para manter o checklist de validações leve e eficiente, as validações e evidências dos lotes `BATCH-001` a `BATCH-017` foram movidas para o arquivo histórico **[validation-001-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-001-017.md)**.

---
## BATCH-018 - Tipo Publicador, Correções no Menus e Módulo de Galerias (req-018)

### Parte 1 — Tipo Publicador e correções no Menus (DEC-025)

- [x] Interface de formulário de Menus (adicionar/editar/clonar × pt-br/en):
  - [x] Opção "Publicador" / "Publisher" adicionada ao dropdown `#item_type`.
  - [x] Exibição condicional de inputs: dropdown de publicadores (`#item_publisher_id`), limite (`#item_publisher_count`), e ordenação (`#item_publisher_order_by`) sob `#field-publisher-wrapper`.
- [x] Árvore visual e persistência de Menus (menus.js):
  - [x] Salvar `publisher_id`, `count` e `order_by` (+`publisher_name`) no schema do nó `publicador` (flatten/buildTree).
  - [x] Renderizar nó visual `Publicador: <nome> (limite: N)` na árvore.
  - [x] Impedir aninhamento manual de sub-itens sob o item publicador (clamp de `maxD` no DnD; inserção como irmão).
  - [x] Edição inline de Rótulo/Publicador/Limite/Ordenação/Classes CSS no painel do nó.
- [x] Backend CRUD de Menus (menus.php):
  - [x] `menus_publisher_options()` busca publicadores ativos e substitui `#publisher_id_options#`; chamada em adicionar/editar/clonar.
- [x] Renderização dinâmica de Menus (menus.widget.php):
  - [x] `menus_widget_buscar_publicacoes_publicador()` busca publicações por `paginas.publisher_id` com `count`/`order_by`.
  - [x] `menus_widget_expandir_publicadores()` injeta as publicações como filhos `pagina` antes da renderização recursiva (normalização preserva os campos do publicador).
- [x] Simulação do HTML Editor de Menus:
  - [x] `html-editor-interface.js`: `menusExpandirPublicadores()` gera `count` sub-itens `pagina` mock sob cada nó `publicador`.
  - [x] Componente `html-editor-menus-simulation` (pt-br/en) e fallback `MENUS_SIM_FALLBACK` com exemplo de nó `publicador`.
- [x] Correções adicionais no Menus (req-018):
  - [x] Alteração de `template_id` atualiza o CodeMirror mesmo focado/oculto (refresh agendado em `html_editor_set_html`/`set_css`).
  - [x] Alternador de `item_type` exibe/oculta os campos corretos (toggle estende `#field-publisher-wrapper`).
  - [x] Variáveis `[[item#slug]]` (como `data-slug`) e `[[item#css_classes]]` (anexada às classes) incluídas nos 12 templates.

### Parte 2 — Módulo de Galerias de Imagens (DEC-026)
- [x] Criação do Módulo de Galerias (req-018):
  - [x] Estrutura clonada de `publisher-highlights`/`menus` desacoplada de publisher (tabela `galleries` **sem** `publisher_id`).
  - [x] Migração Phinx `20260701120000_create_galleries_table.php` (tabela `galleries` com colunas pedidas + campos do sistema).
  - [x] Módulo `galleries` registrado em `ModulosData.json` (pt-br/en, grupo `administracao-gestor`, ícone `images`) e em `UsuariosPerfisModulosData.json`.
  - [x] Botão "Selecionar Imagens do Servidor" (`#btn-select-images`) abre o modal `iframePagina` apontando para `admin-arquivos/?paginaIframe=sim` (setup via `galleries_imagepick_setup`).
  - [x] Listener de `postMessage` em `galleries.js` valida `tipo` de imagem e adiciona à lista **sem fechar** o modal (seleção em lote).
  - [x] Cada imagem na curadoria exibe thumbnail, nome, input de legenda e botão remover.
  - [x] Reordenação drag-and-drop com `Sortable.js` (CDN), relendo a ordem física do DOM no `onEnd`.
  - [x] Serialização de `fields_schema.selected_items` com `id`, `caminho`, `imgSrc`, `nome` e `legenda`.
  - [x] CRUD backend (`galleries.php`: adicionar/editar/clonar) integrado ao ImagePick e ao html-editor (alvo `galleries`).
  - [x] Renderizador (`galleries.widget.php`) decodifica o JSON e renderiza item/no-item com `[[item#img-src]]`/`[[item#caminho]]`/`[[item#nome]]`/`[[item#legenda]]`.
  - [x] 4 templates padrões (`galleries-grid`, `galleries-carousel`, `galleries-masonry`, `galleries-slider`) em pt-br/en.
  - [x] Aba de simulação/variáveis para `galleries` no HTML Editor (`case 'galleries'` + `alvoUsaItemVars`) com componente mockado Picsum e fallback `GALLERIES_SIM_FALLBACK`.
- [ ] Ações pós-implementação (com o operador):
  - [ ] Executar `atualizacao-dados-recursos.php` / `🗃️ Projects - Update => Core` para registrar módulo/páginas/templates/componente, calcular checksums e aplicar a migração `galleries` no runtime.

### Evidência registrada em 2026-06-05 (BATCH-018)

- Validação executável (estática + testes de unidade, sem ambiente Docker nesta rodada):
  - `php -l` OK em `menus.php`, `menus.widget.php`, `galleries.php`, `galleries.widget.php`, `html-editor.php` e na migração `20260701120000_create_galleries_table.php`
  - `node --check` OK em `menus.js`, `galleries.js`, `html-editor-interface.js`
  - `JSON.parse` OK em `menus.json`, `galleries.json`, `ModulosData.json`, `UsuariosPerfisModulosData.json` e nos componentes de simulação (menus c/ nó publicador; galleries c/ 6 imagens)
  - Teste do widget `menus` (publicador) com stubs de banco — 9/9 asserts OK: expansão do publicador, limite `count`, ordenação, injeção como `item-parent`, sem `[[item#X]]`/arrobas residuais
  - Teste da simulação JS de `menus` (réplica real do arquivo) — 7/7 asserts: 4 sub-itens mock sob o publicador, submenu, sem variáveis literais
  - Teste do widget `galleries` com stubs — 11/11 asserts: img-src absoluta preservada, relativa prefixada com url-raiz, legendas/nome, 2 blocos item, CSS injetado, no-item exibido só quando vazio
  - Teste da simulação JS de `galleries` (réplica real) — 6/6 asserts: 6 imagens Picsum, sem variáveis literais
- Decisões registradas: [DEC-025](../decisions/DECISION-LOG.md) (tipo publicador + correções no menus) e [DEC-026](../decisions/DECISION-LOG.md) (módulo galleries)
- Bug corrigido durante a implementação: `menus_widget_normalizar_itens` descartava `publisher_id`/`count`/`order_by`, impedindo a expansão do publicador (corrigido).
- Harmonização com edição do Engenheiro Chefe: o `#item_type` do menus voltou a `<select>` nativo (correção do alternador, req-018 §1.2) — `currentItemType()` e o construtor do publicador passaram a ler valores via `.val()`/`option:selected`.
- Restrição respeitada: nenhum `git commit`/`git push` executado.
- Pendência (com o operador): rodar `🗃️ Projects - Update => Core` (registra o módulo `galleries`, novos componentes `html-editor-galleries-simulation`/atualização do `html-editor-menus-simulation`, recalcula checksums e aplica a migração). Depois, validar manualmente:
  - **Menus / publicador**: adicionar item "Publicador", escolher publicador/limite/ordenação; a árvore mostra `Publicador: <nome> (limite: N)`; não permite aninhar sob ele; preview/site geram os N sub-itens com as publicações reais; aba "Simular" mostra sub-itens mock.
  - **Menus / correções**: trocar `template_id` com a aba "Editor HTML" aberta atualiza o CodeMirror; alternar tipo mostra os campos certos; `[[item#slug]]`/`[[item#css_classes]]` saem no HTML final.
  - **Galleries**: menu "Galerias de Imagens" aparece; "Selecionar Imagens do Servidor" abre o gerenciador e permite escolher várias seguidas sem fechar; legenda editável; arrastar reordena; salvar/reabrir preserva a ordem; aba "Pré-Visualização" e o widget `widgets#galleries->render(...)` renderizam as imagens; aba "Simular" usa imagens Picsum.

## BATCH-019 - Correções no Menus e Lógica do Módulo de Galerias (req-019)

- [x] Módulo de Menus (req-019):
  - [x] Margem superior de 1rem inserida no contêiner `#btn-add-item-wrapper` (nos 3 HTMLs pt-br/en).
  - [x] Campo de target `#custom-target` inserido nos formulários e controlado condicionalmente no tipo `link-custom`.
  - [x] Edição inline de target funcional no painel da árvore visual e persistência no schema JSON.
  - [x] Campo de rótulo disponível no tipo `separador` na interface e na edição inline.
  - [x] Suporte ao bloco `item-separator` no backend (`menus.widget.php`) e simulação em JS (`html-editor-interface.js`).
  - [x] Atributo `target="[[item#target]]"` e divisores visuais `item-separator` incluídos em todos os 12 templates.
  - [x] Spacing horizontal aumentado de `gap-6` para `gap-8` no template `menus-horizontal-navbar`.
  - [x] Hamburguer mobile alterado para clique via botão no HTML e manipulado por `menus.widget.js`.
  - [x] Links pais clicáveis e tags `<a>` presentes no template `menus-footer-colunas`.
  - [x] Hover do dropdown em múltiplos subníveis funcionando via fallback em JS no `menus.widget.js`.
- [x] Módulo de Galerias (req-019):
  - [x] Campos de controles (`show_arrows`, `show_dots`, `autoplay`, `autoplay_speed`, `loop`) inseridos nas 3 páginas HTML pt-br/en.
  - [x] Hidratação, persistência e serialização dos controles configurada em `galleries.js`.
  - [x] Resolução de imagem no widget público (`galleries.widget.php`) prioriza `caminho` original em vez de `imgSrc` miniatura.
  - [x] Atributos de dados `data-*` correspondentes às configurações de controle gerados no DOM do widget.
  - [x] Renderizador trata blocos condicionais de controles (`controls-arrows`, `controls-dots`, `dot-item` interno) no backend e na simulação.
  - [x] Marcação de setas, dots e dot-items incluída nos templates `galleries-carousel.html` e `galleries-slider.html`.
  - [x] JavaScript do widget (`galleries.widget.js`) gerencia a rolagem horizontal suave, navegação por setas, dot pagination e temporizador de autoplay.
- [x] Registro e Prompts de IA (req-019):
  - [x] Bloco `ai_prompts_targets` e `ai_modes` configurados e presentes em `galleries.json` (pt-br/en).
  - [x] Arquivo `galleries.md` de prompt de IA criado e validado em `pt-br/ai_modes/galleries/` e `en/ai_modes/galleries/`.
  - [x] Arquivo `menus.md` de prompt de IA atualizado em `pt-br/ai_modes/menus/` e `en/ai_modes/menus/` com as novas variáveis (`target`, `css_classes`, `children`) e divisor de separador.
  - [x] Variáveis globais (`show_arrows`, etc.) expostas em `galleries_variaveis_template()` com `'global' => true`.
  - [x] `html-editor.php` gerando placeholders `[[show_arrows]]` (sem `item#`) na interface do editor e populo correto do placeholder `{{variables}}` em `html_editor_ajax_ia_requests()`.
  - [x] `galleries.widget.php` resolvendo globalmente as variáveis no HTML final (ex: `[[autoplay]]` -> `true`/`false`).
- [ ] Ações pós-implementação:
  - [ ] Executar `atualizacao-dados-recursos.php` para sincronizar e registrar os novos templates, componentes de simulação, alvos/modos de IA e variáveis no banco.

### Evidência registrada em 2026-06-05 (BATCH-019)

- Validação executável (estática + teste de unidade dos renderers, sem ambiente Docker nesta rodada):
  - `php -l` OK em `menus.php`, `menus.widget.php`, `galleries.php`, `galleries.widget.php` e `html-editor.php`.
  - `node --check` OK em `menus.js`, `menus.widget.js` (novo), `galleries.js`, `galleries.widget.js` (novo) e `html-editor-interface.js`.
  - `JSON.parse` OK em `menus.json`, `galleries.json` e nas árvores mockadas dos componentes `html-editor-menus-simulation` (pt-br/en, com link `_blank` e separador rotulado).
  - Teste de unidade dos renderers (`menus.widget.php` + `galleries.widget.php` com stubs de banco) — **27/27 asserts OK**: target `_self`/`_blank` (link-custom), bloco `item-separator` com/sem rótulo, fallback do separador sem o bloco, submenu recursivo, resolução de páginas; galerias com `img-src` priorizando `caminho` (relativo prefixado / absoluto preservado), 3 slides, `controls-arrows`/`controls-dots` condicionais, 3 dots com índice e classe ativa só no primeiro, variáveis globais `data-autoplay`/`data-speed`/`data-loop` resolvidas, galeria vazia (no-item + controles removidos).
- Bug corrigido durante a implementação: `menus_widget_normalizar_itens` descartava o campo `target`, fazendo o link-custom cair sempre em `_self` (corrigido — preserva `target`).
- Arquivos alterados (menus): `menus.php`, `menus.js`, `menus.widget.php`, `menus.json`, 6 páginas (`menus-{adicionar,editar,clonar}` pt-br/en), 12 templates, `menus.md` (pt-br/en), `html-editor-menus-simulation` (pt-br/en).
- Arquivos criados (menus): `menus.widget.js`.
- Arquivos alterados (galleries): `galleries.php`, `galleries.js`, `galleries.widget.php`, `galleries.json`, 6 páginas, `galleries-carousel`/`galleries-slider` (pt-br/en).
- Arquivos criados (galleries): `galleries.widget.js`, `ai_modes/galleries/galleries.md` (pt-br/en).
- Arquivos alterados (core compartilhado): `gestor/bibliotecas/html-editor.php` (flag `global` no `template_map` + casos `menus`/`galleries` no AJAX de IA) e `gestor/assets/interface/html-editor-interface.js` (simulação de target/separador no menus e de setas/dots/dot-item/globais nas galerias).
- Decisões registradas: [DEC-027](../decisions/DECISION-LOG.md) a [DEC-031](../decisions/DECISION-LOG.md).
- Versionamento: versões incrementadas nos recursos alterados (templates/páginas de menus e galerias, ai_mode de menus, `versao` dos módulos `menus` 1.0.1→1.0.2 e `galleries` 1.0.0→1.0.1); checksums mantidos intactos (recálculo automático pelo pipeline UPSERT).
- Restrição respeitada: nenhum `git commit`/`git push` executado.
- Pendência (com o operador): rodar `atualizacao-dados-recursos.php` / `🗃️ Projects - Update => Core` para registrar os novos templates, o componente atualizado, o alvo/modo de IA `galleries`, as variáveis e recalcular checksums; aplicar no ambiente de testes. Depois, validar manualmente:
  - **Menus / target**: criar um item `link-custom` com "Nova aba", salvar/reabrir e confirmar `target="_blank"` no preview e no site.
  - **Menus / separador**: adicionar separador com e sem rótulo; confirmar o bloco `item-separator` renderizando o divisor (com/sem texto) nos templates.
  - **Menus / widget JS**: no template mobile, o botão hambúrguer abre/fecha a lista; no dropdown, o submenu abre por hover mesmo se os named groups do Tailwind falharem.
  - **Galerias / controles**: marcar/desmarcar setas, pontinhos, autoplay, loop e ajustar o tempo; confirmar que o preview e o site refletem (carrossel/slider deslizam, dots sincronizam, autoplay respeita o tempo e pausa no hover).
  - **Galerias / imagem**: confirmar que o site usa o `caminho` original (não a miniatura) na tag `<img>`.
  - **IA**: na aba do Modo de IA do alvo `galleries`, confirmar que o prompt recebe as variáveis de item e as globais; no menus, as novas variáveis (`target`, `css_classes`, `children`).

## BATCH-020 - Integração do Tailwind CSS CLI no Core do Sistema e Pipeline de Release (req-020)

- [x] Estrutura de Estilo do Core:
  - [x] Arquivo `gestor/assets/tailwindcss/input.css` criado contendo `@import "tailwindcss";` e a diretiva `@config "../../../tailwind.config.js";`.
- [x] Configurações de Ambiente Local e Templates:
  - [x] Variável `"tailwindcss/cli"` adicionada no bloco `"devEnvironment"` em `environment.json`.
  - [x] Variável `"tailwindcss/cli"` adicionada no bloco `"devEnvironment"` do template `templates/environment/environment.json`.
  - [x] Auditoria comparativa do template com o arquivo ativo concluída e quaisquer variáveis estruturais ausentes normalizadas no template.
- [x] Sincronização e Compilação Local:
  - [x] `synchronize-manager.sh` lê a chave do environment (via `jq` e fallback regex), executa o build na pasta `gestor/` se configurado, e aborta a sincronização em caso de falha.
  - [x] `sync-core-to-project.sh` lê a chave do environment, executa o build do Tailwind na pasta `gestor/` se configurado, e aborta em caso de falha.
- [x] Pipeline de Release (GitHub Actions):
  - [x] `release-gestor.yml` inclui a etapa de configuração do Node.js v20.
  - [x] `release-gestor.yml` executa a compilação do Tailwind CSS CLI (`npx @tailwindcss/cli -i ./assets/tailwindcss/input.css -o ./assets/tailwindcss/output.css --minify`) antes de empacotar.
  - [x] `release-gestor.yml` inclui a linha `git add gestor/assets/tailwindcss/*.css` na etapa de commit das atualizações.

### Evidência registrada em 2026-06-08 (BATCH-020)

- Validação executável:
  - `bash -n` verificado em `synchronize-manager.sh` e `sync-core-to-project.sh` -> OK.
  - Leitura de JSON via `jq` da chave `tailwindcss/cli` em ambos JSONs (`environment.json` ativo e template) -> JSON válido.
  - Fallback regex verificado para extrair o comando correto de `devEnvironment`.
  - Integridade estrutural do `release-gestor.yml` validada por inspeção visual.
- Arquivos alterados/criados:
  - Criado estilo core `gestor/assets/tailwindcss/input.css`
  - Adicionado bloco `devEnvironment` com a nova chave em `dev-environment/data/environment.json` e no template `dev-environment/templates/environment/environment.json`.
  - Scripts de build e sync atualizados: `synchronize-manager.sh` e `sync-core-to-project.sh`.
  - Workflow `.github/workflows/release-gestor.yml` atualizado para configurar Node.js v20, compilar Tailwind CSS v4 CLI e comitar os arquivos gerados.
- Correção pós-execução do operador (2026-06-08): na primeira execução real de `sync-core-to-project.sh`, o passo de compilação Tailwind não disparou (só o `input.css` sincronizou, sem `output.css`). Causa raiz: no **jq 1.8.1**, o filtro `jq -r '.devEnvironment."tailwindcss/cli" // empty'` retorna vazio — a **barra `/` na chave em notação de ponto** interage incorretamente com o operador `//` (confirmado: `.devEnvironment.source // empty` funciona; `.devEnvironment."tailwindcss/cli" // empty` retorna vazio; `.devEnvironment["tailwindcss/cli"] // empty` funciona). Correção aplicada nos dois scripts: **notação de colchetes** `.devEnvironment["tailwindcss/cli"]`. Após o fix, a leitura via jq retorna o comando e o bloco de compilação dispara (o fallback regex de `synchronize-manager.sh` já estava correto). Requer Node.js disponível no host para o `npx`; em falha de compilação o sync aborta with `exit 1`.

## BATCH-021 - Lançamento v2.8.0, Correção HTML e Automação de Campos (req-021)

- [x] Módulo `publisher-pages`:
  - [x] Hidden input com `name="field_<id>"` para campos HTML inicializado em `publisher-pages.js` com o valor atual do editor Quill no carregamento da página.
- [x] Módulo `publisher`:
  - [x] Botão `#add-all-fields-btn` incluído nas 6 páginas HTML (adicionar/editar/clonar em pt-br/en).
  - [x] Visibilidade do botão `#add-all-fields-btn` controlada dinamicamente via `templateWrapper` em `publisher.js`.
  - [x] Clique do botão `#add-all-fields-btn` instanciando apenas os campos do modelo não vinculados.
  - [x] Rótulo (label) formatado via `generateLabelFromId`.
  - [x] Preenchimento inicial do input `.field-template` em `addFieldRow` se `template_field_id` for fornecido.
  - [x] Preenchimento de prompt em `updateFieldTemplateSearches` contendo fallback para campos adicionados dinamicamente em lote.
- [x] Lançamento v2.8.0:
  - [x] `CHANGELOG.md` e `CHANGELOG-PT-BR.md` atualizados com as notas da versão 2.8.0.
  - [x] `README.md` e `README-PT-BR.md` com a versão e destaques atualizados.
  - [x] Workflow `release-gestor.yml` atualizado na ação `Create Release` para referenciar as novidades da 2.8.0.

### Evidência registrada em 2026-06-08 (BATCH-021)

- Validação executável (estática, sem ambiente Docker nesta rodada):
  - `node --check gestor/modulos/publisher/publisher.js` → `publisher.js OK`
  - `node --check gestor/modulos/publisher-pages/publisher-pages.js` → `publisher-pages.js OK`
  - `release-gestor.yml`: integridade do block scalar `body: |` validada por inspeção (conteúdo a 10 espaços; `draft`/`prerelease`/`files` preservados a 8 espaços). PyYAML indisponível no host nesta rodada.
- Arquivos alterados:
  - `gestor/modulos/publisher-pages/publisher-pages.js` (inicializa o hidden input do campo HTML com o valor corrente do Quill dentro da iteração `.quill-editor`)
  - `gestor/modulos/publisher/publisher.js` (`templateWrapper` controla `#add-all-fields-btn`; helper `generateLabelFromId`; handler de clique em lote sobre `fieldSets.available`; pré-preenchimento do `.field-template` em `addFieldRow`; fallback de prompt `[[publisher#tipo#id]]` em `updateFieldTemplateSearches`)
  - `gestor/modulos/publisher/resources/{pt-br,en}/pages/publisher-{adicionar,editar,clonar}/*.html` (6 páginas com `#add-all-fields-btn` ao lado de `#add-field-btn`)
  - `CHANGELOG.md`, `CHANGELOG-PT-BR.md` (seção `[2.8.0] - 2026-06-08`)
  - `README.md`, `README-PT-BR.md` (versão e destaques v2.8.0)
  - `.github/workflows/release-gestor.yml` (corpo da ação `Create Release` reescrito para v2.8.0)
- Decisão registrada: [DEC-034](../decisions/DECISION-LOG.md#dec-034---2026-06-08---accepted)
- Pendência: rodar `🗃️ Projects - Update => Core` para recompilar as 6 páginas HTML alteradas e validar manualmente no Docker:
  - editar uma publicação com campo HTML sem tocar no editor e salvar → o conteúdo HTML é preservado (não esvazia)
  - selecionar um modelo no publisher exibe `#add-all-fields-btn`; clicar adiciona apenas os campos ainda não vinculados, com labels capitalizados (ex: `lista_signatarios` → `Lista Signatarios`)
  - cada campo em lote já vem com `.field-template` e prompt preenchidos como `[[publisher#tipo#id]]`
  - trocar para um modelo vazio/none oculta novamente o botão

## BATCH-022 - Pré-visualizador de HTML Externo Unificado (req-022)

- [x] Módulo Destaques (`publisher-highlights.js`):
  - [x] Função `scheduleWidgetPreview` chama `window.previewExternalHtmlConteudo` para gerar o HTML do iframe de pré-visualização.
  - [x] Passa `dados.html`, `css` e `gestor.html_editor.framework_css` de forma correta.
  - [x] Mantém fallback de segurança caso a função não exista no escopo.
- [x] Módulo Menus (`menus.js`):
  - [x] Função `scheduleWidgetPreview` chama `window.previewExternalHtmlConteudo` para gerar o HTML do iframe de pré-visualização.
  - [x] Passa `dados.html`, `css` e `gestor.html_editor.framework_css` de forma correta.
  - [x] Mantém fallback de segurança caso a função não exista no escopo.
- [x] Módulo Galerias (`galleries.js`):
  - [x] Função `scheduleWidgetPreview` chama `window.previewExternalHtmlConteudo` para gerar o HTML do iframe de pré-visualização.
  - [x] Passa `dados.html`, `css` e `gestor.html_editor.framework_css` de forma correta.
  - [x] Mantém fallback de segurança caso a função não exista no escopo.

### Evidência registrada em 2026-06-09 (BATCH-022)

- Validação executável (estática):
  - `node --check gestor/modulos/publisher-highlights/publisher-highlights.js` -> OK
  - `node --check gestor/modulos/menus/menus.js` -> OK
  - `node --check gestor/modulos/galleries/galleries.js` -> OK
- Arquivos alterados:
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (refatorado callback AJAX success do `widget-preview`)
  - `gestor/modulos/menus/menus.js` (refatorado callback AJAX success do `widget-preview`)
  - `gestor/modulos/galleries/galleries.js` (refatorado callback AJAX success do `widget-preview`)
- Decisão registrada: [DEC-035](../decisions/DECISION-LOG.md#dec-035---2026-06-09---accepted)
- Pendência:
  - Validar manualmente no ambiente local (Docker):
    - Pré-visualização do módulo Destaques carrega perfeitamente e aplica o CSS customizado e framework adequados.
    - Pré-visualização do módulo Menus carrega recursivamente e renderiza a árvore simulada.
    - Pré-visualização do módulo Galerias renderiza as imagens e controles.


## BATCH-023 - Otimização de CSS Automático com Filtragem de Redundâncias (req-023)

- [x] JavaScript do Editor HTML (`html-editor-interface.js`):
  - [x] Função `updateCSSCompiled` varre os stylesheets do iframe do previewer para extrair seletores de `system-output.css` e `output.css`.
  - [x] O conjunto `systemSelectors` (`Set`) é populado com as regras de classe simples (`selectorText`).
  - [x] A folha de estilos do Tailwind CDN (`styleSheet.cssRules`) é percorrida e as regras são filtradas para remover duplicidades.
  - [x] Regras `@media` têm suas sub-regras limpas e re-montadas de forma não redundante.
  - [x] A instância CodeMirror `CodeMirrorCssCompiled` recebe o valor do CSS limpo sem travar o editor.
  - [x] Compatibilidade de leitura estruturada via `sheet.cssRules` assegurada para Tailwind v3 (innerHTML) e v4 (insertRule).
- [x] Limpeza de Templates de Layout:
  - [x] CDNs redundantes de Tailwind CSS v3 e Fomantic UI removidos dos 9 templates de layout em `gestor/resources/pt-br/templates/`.
  - [x] Placeholders `<!-- pagina#titulo -->` e `<!-- pagina#js -->` padronizados nos layouts.

### Evidência registrada em 2026-06-09 (BATCH-023)

- Validação executável:
  - `node --check gestor/assets/interface/html-editor-interface.js` -> OK
  - `git diff gestor/resources/pt-br/templates` inspecionado → 9 arquivos de templates limpos e com placeholders normalizados.
- Arquivos alterados:
  - `gestor/assets/interface/html-editor-interface.js` (refatorada a rotina de extração e filtragem da função `updateCSSCompiled`)
  - 9 arquivos de layout HTML em `gestor/resources/pt-br/templates/` (limpeza de CDNs de frameworks e placeholders)

- Decisão registrada: [DEC-036](../decisions/DECISION-LOG.md#dec-036---2026-06-09---accepted)
- Pendência:
  - Testar manualmente no navegador (painel do Gestor):
    - Inserir tags HTML usando classes comuns (ex: `flex`, `hidden`). O painel CodeMirror "CSS Compilado" deve permanecer vazio.
    - Inserir uma classe exclusiva nova (ex: `bg-emerald-950`). O painel CodeMirror deve ser preenchido apenas com a regra específica para esta classe.
    - Confirmar que o salvamento no banco grava a string contendo apenas o CSS reduzido.


## BATCH-024 - Links Dinâmicos em Galerias, Controles de Exibição e Correções de Layout (req-024)

- [x] Galerias: Links Individuais por Imagem (Painel e Widget):
  - [x] Cada item na curadoria do painel tem o botão retrátil "Configurar Link" com ícone `linkify`.
  - [x] Exibe inputs dinâmicos conforme o tipo de link selecionado: Página (com dropdown carregado via global `galleries_pages`), Link Customizado (URL input + target), Link com Classe CSS (URL input + target + CSS class), Última Publicação (dropdown de publicadores via global `galleries_publishers` + dropdown ordenação).
  - [x] Serialização correta dos novos atributos no array `selected_items` de `fields_schema` (itens normalizados com `link_type`/`link_page_id`/`link_url`/`link_target`/`link_css_classes`/`link_publisher_id`/`link_order_by`).
  - [x] Widget renderiza os links nas imagens substituindo `[[item#link-url]]`, `[[item#link-target]]` e `[[item#link-css-classes]]` (páginas resolvidas em lote; publicador resolvido via publicação mais recente com cache).
  - [x] Caso `link_type` seja `'nenhum'`, retorna `'javascript:void(0);'` no link-url.
- [x] Galerias: Controles de Exibição Globais (Altura e Margem Lateral):
  - [x] Novos inputs de Altura (padrão 300) e Margem Lateral (padrão 0) na aba "Controles de Exibição" do painel de Galerias (6 páginas pt-br/en).
  - [x] Serialização correta e instantânea ao digitar, disparando o preview da aba ao vivo (listener `input change` em `#gallery-height`/`#gallery-margin-lateral`).
  - [x] Templates visuais atualizados: margem lateral (`[[margin_lateral]]`) na `<section>` raiz de **todos** (carousel, grid, slider, masonry); altura (`[[height]]`) em carousel, grid e slider. O masonry **não** recebe altura fixa para preservar o fluxo natural de colunas (alvenaria), conforme req §2.3.
- [x] Destaques do Publicador: Mapeamento de Variáveis (`linked-fields-list`):
  - [x] Tags renderizadas no container de vinculação de campos têm espaçamento horizontal (`margin: 2px 4px`).
- [x] Menus: Submenus no Menu Horizontal (`menus-horizontal-navbar`):
  - [x] Submenus de nível 2, 3 e subsequentes no menu horizontal têm o recuo/padding horizontal interno correto (`0.5rem 1rem`) e hover apropriado, sem que o texto dos links encoste nas bordas (bloco `<style>` na `<section>` raiz, pt-br/en).
- [x] Galerias: Legenda do Layout Masonry (`galleries-masonry`):
  - [x] Legendas das imagens no masonry não ficam coladas no canto esquerdo ou inferior (`mt-1 px-1` → `mt-2 px-3 pb-2`, pt-br/en).

### Evidência de Validação (BATCH-024)

- Validação estática executada em 2026-06-10:
  - `node --check gestor/modulos/galleries/galleries.js` → OK (sem erros de sintaxe).
  - `node --check gestor/modulos/publisher-highlights/publisher-highlights.js` → OK.
  - `php -l gestor/modulos/galleries/galleries.php` → `No syntax errors detected`.
  - `php -l gestor/modulos/galleries/galleries.widget.php` → `No syntax errors detected`.
- Arquivos alterados:
  - `gestor/modulos/galleries/galleries.js` (contrato de item com `link_*`, painel "Configurar Link" com visibilidade dinâmica, controles globais altura/margem).
  - `gestor/modulos/galleries/galleries.php` (`galleries_link_listas_setup()` injeta `galleries_pages`/`galleries_publishers`; `galleries_variaveis_template()` registra `link-url`/`link-target`/`link-css-classes` e globais `height`/`margin_lateral`).
  - `gestor/modulos/galleries/galleries.widget.php` (`galleries_widget_resolver_link()`, `galleries_widget_resolver_publicacao_recente()` com cache, `galleries_widget_carregar_paginas()` em lote; `height`/`margin_lateral` em `galleries_widget_resolver_globais()`).
  - `gestor/modulos/galleries/resources/{pt-br,en}/pages/galleries-{adicionar,editar,clonar}/*.html` (6 páginas: inputs Altura/Margem Lateral).
  - `gestor/modulos/galleries/resources/{pt-br,en}/templates/galleries-{carousel,slider,grid,masonry}/*.html` (8 templates: âncora de link, margem lateral, altura, legenda do masonry).
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (margem horizontal das tags em `renderLinkedVars`).
  - `gestor/modulos/menus/resources/{pt-br,en}/templates/menus-horizontal-navbar/*.html` (bloco `<style>` com padding nos submenus).
- Observações de contrato real (divergência do pseudocódigo do intake): publicadores vêm da tabela `publisher` (coluna `name`) e páginas da tabela `paginas` (slug em `id`, rótulo em `nome`, URL em `caminho`), espelhando `menus_publisher_options`/`menus_widget_carregar_paginas`. Os tipos de link seguem DEC-037: `nenhum`, `pagina`, `link-custom`, `link-css-classes`, `publicador`.
- Testes manuais/runtime pendentes com o operador (após `🗃️ Projects - Update => Core`):
  - Adição de link de Página, Customizado (`_blank`) e Última Publicação em imagens da galeria; conferir HTML renderizado no preview.
  - Ajuste de altura para 450px e margem lateral para 20px; conferir reflexo instantâneo no preview.
  - Verificação visual dos submenus do menu horizontal e das legendas no masonry.
- Decisão registrada: [DEC-037](../decisions/DECISION-LOG.md#dec-037---2026-06-10---accepted)


## BATCH-025 - Autocomplete de Páginas em Galerias, Ajuste do Menu Horizontal e Preparação Final de Release (req-025)

- [x] Galerias: Autocomplete AJAX de Páginas:
  - [x] Dropdown estático simples de páginas na curadoria removido e substituído pelo autocomplete AJAX do Menus (`buildPageAutocompleteField`).
  - [x] Filtro de tipo de página (Página, Sistema, Ambos) e input de busca implementados com identificadores e classes isolados por ID de item curado (`name="gallery_page_search_type_${it.id}"` nos rádios; `data-id="${it.id}"` + classes locais no input/sugestões/hidden) para evitar colisões entre linhas.
  - [x] AJAX de busca (`pages-search`) e de carregamento inicial (`pages-fetch`) roteados no `galleries.php` (`galleries_ajax_pages_search`/`galleries_ajax_pages_fetch`, clonados do `menus.php`) consultando a tabela `paginas`.
  - [x] Hidratação automática na edição varrendo o array global `galleries_pages` (`resolvePageNameLocal`); fallback via `pages-fetch` (`fetchPageName`) quando o nome não está em memória.
- [x] Galerias: Inativação de Links no Widget:
  - [x] Tags `<a>` sem link configurado (`link_type === 'nenhum'`) recebem a classe `pointer-events-none cursor-default` (anexada a `link-css-classes` em `galleries_widget_resolver_link`), desabilitando o clique e mantendo o cursor padrão.
- [x] Menus: Alinhamento Horizontal do Submenu no Navbar:
  - [x] Regra `[data-title="menu-horizontal-navbar"] ul.absolute a` alterada de `display: block !important` para `display: flex !important; align-items: center; justify-content: space-between; gap: 0.25rem !important` (pt-br + en), alinhando a setinha SVG na mesma linha do rótulo.
- [x] Galerias: Proporção das Miniaturas no Painel:
  - [x] Estilo `.gallery-item-thumb` alterado de `64px×48px` para `width: 200px; height: 140px;` em `injectGalleryStyles`.
- [x] Documentação e CI/CD:
  - [x] Data de lançamento da v2.8.0 atualizada para `2026-06-10` em `CHANGELOG.md`, `CHANGELOG-PT-BR.md`, `README.md` e `README-PT-BR.md`.
  - [x] Otimizações do BATCH-023 (CSS inline + previewer unificado) e correções do BATCH-024/025 (links em galerias, busca de páginas, miniaturas, imagens sem link, submenus) documentadas nos changelogs e nos READMEs (incl. aba "Código do Widget" e Tailwind CSS CLI v4).
  - [x] Workflow GitHub Actions (`release-gestor.yml`) com o `body` do release atualizado descrevendo novidades e correções.

### Evidência de Validação (BATCH-025)

- Validação estática executada em 2026-06-10:
  - `node --check gestor/modulos/galleries/galleries.js` → `JS_OK` (sem erros de sintaxe).
  - `php -l gestor/modulos/galleries/galleries.php` → `No syntax errors detected`.
  - `php -l gestor/modulos/galleries/galleries.widget.php` → `No syntax errors detected`.
  - YAML de `release-gestor.yml`: alteração restrita ao `body:` (block scalar literal), indentação de 10 espaços preservada; validação por parser não executada (js-yaml/PyYAML indisponíveis no ambiente).
- Arquivos alterados:
  - `gestor/modulos/galleries/galleries.js` (miniatura 200×140; autocomplete AJAX de páginas por imagem: `buildPageAutocompleteField`, `runGalleryPageSearch`, `renderGalleryPageSuggestions`, `resolvePageNameLocal`, `fetchPageName` + listeners isolados por `data-id`).
  - `gestor/modulos/galleries/galleries.php` (endpoints `pages-search`/`pages-fetch` no switch AJAX + funções `galleries_ajax_pages_search`/`galleries_ajax_pages_fetch` clonados do Menus).
- [x] **Mecanismo de Preservação de Template Modificado (PHP/JS)**:
  - [x] **Módulo Menus**:
    - [x] `menus.php`: `menus_template_options` recebe `$has_custom_code` e gera `<option value="[id]-modificado">` se verdadeiro.
    - [x] `menus.js`: Cache de `initialHtml`/`initialCss`. No load, se `-modificado` existir, seleciona ele e não dispara `loadTemplate`. No event change, se for `-modificado` restaura cache. No submit e no `currentSchemaOut()`, remove o sufixo `-modificado`.
  - [x] **Módulo Galerias**:
    - [x] `galleries.php`: `galleries_template_options` gera a opção `-modificado`.
    - [x] `galleries.js`: Lógica idêntica de cache, inicialização e remoção do sufixo no submit/serialização.
  - [x] **Módulo Destaques**:
    - [x] `publisher-highlights.php`: `publisher_highlights_template_options` gera a opção `-modificado`.
    - [x] `publisher-highlights.js`: Lógica idêntica de cache, inicialização. No load e change da opção `-modificado`, extrai localmente as variáveis `[[item#X]]` do `initialHtml` para manter a aba de mapeamento funcional.

### Evidência de Validação (BATCH-026)

- [x] Validação estática de sintaxe executada:
  - [x] `node --check gestor/modulos/menus/menus.js` → OK (sem erros de sintaxe)
  - [x] `node --check gestor/modulos/galleries/galleries.js` → OK (sem erros de sintaxe)
  - [x] `node --check gestor/modulos/publisher-highlights/publisher-highlights.js` → OK (sem erros de sintaxe)
  - [x] `php -l gestor/modulos/menus/menus.php` → `No syntax errors detected`
  - [x] `php -l gestor/modulos/galleries/galleries.php` → `No syntax errors detected`
  - [x] `php -l gestor/modulos/publisher-highlights/publisher-highlights.php` → `No syntax errors detected`
- [x] Testes manuais/runtime executados pelo operador:
  - [x] Editar um registro de cada módulo alterando o HTML/CSS e salvar. Reabrir e confirmar que a opção do select indica `- (Modificado)` e o editor não foi sobrescrito pelo original.
  - [x] Na tela de edição de cada módulo, trocar para o modelo original e confirmar que o AJAX `template-load` sobrescreve o editor. Trocar de volta para `- (Modificado)` e confirmar que o HTML/CSS modificado do registro é restaurado.
  - [x] Clonar um registro modificado de cada módulo e confirmar que o clone abre com a opção `- (Modificado)` e o editor mantém as alterações da origem.
  - [x] Salvar o registro editado/clone e verificar no banco de dados que a coluna `template_id` e o `fields_schema` foram persistidos de forma limpa (sem o sufixo `-modificado`).
  - [x] Rodar uma consulta de IA no destaques e verificar que ela gera o bloco `<!-- no-item < -->` e suporta variáveis extras.
  - [x] Rodar uma consulta de IA nas galerias e confirmar que a IA gera as tags `<a>` de âncora envolvendo as imagens com os placeholders de link (`[[item#link-url]]`, `[[item#link-target]]`, `[[item#link-css-classes]]`).
- [x] Decisão registrada: [DEC-039](../decisions/DECISION-LOG.md#dec-039---2026-06-10---accepted)


## BATCH-027 - Resolução de Framework CSS e Variáveis de Destaques de Modelo Modificado (req-027)

- [x] **Resolução de Framework CSS do Template (`framework_css`)**:
  - [x] Para evitar que o pré-visualizador (`live widget-preview`) falhe ao abrir um modelo `-modificado` devido à falta do framework CSS, o PHP deve selecionar o `framework_css` dos templates e disponibilizá-lo como um atributo de dados `data-framework` nos elementos `<option>` de templates.
  - [x] O JavaScript deve ler este atributo no page load (inicialização) e em eventos `change` para inicializar a variável `gestor.html_editor.framework_css` de forma síncrona nos módulos:
    - [x] `menus.js`
    - [x] `galleries.js`
    - [x] `publisher-highlights.js`
- [x] **Extração de Variáveis em Destaques (Highlights)**:
  - [x] No JavaScript de `publisher-highlights.js`, ao carregar/re-selecionar a variante `-modificado`, as variáveis `[[item#X]]` devem ser extraídas localmente do HTML do banco de dados (`initialHtml` ou cached HTML) usando expressão regular client-side para manter o painel de mapeamento de variáveis populado, chamando `renderItemVars()` e `syncEditorVariables()`.

### Evidência de Validação (BATCH-027)

- [x] Validação estática de sintaxe executada:
  - [x] `node --check gestor/modulos/menus/menus.js`
  - [x] `node --check gestor/modulos/galleries/galleries.js`
  - [x] `node --check gestor/modulos/publisher-highlights/publisher-highlights.js`
  - [x] `php -l gestor/modulos/menus/menus.php`
  - [x] `php -l gestor/modulos/galleries/galleries.php`
  - [x] `php -l gestor/modulos/publisher-highlights/publisher-highlights.php`
- [x] Testes manuais/runtime pendentes com o operador:
  - [x] Confirmar que o pré-visualizador (`live widget-preview`) funciona perfeitamente logo no carregamento inicial da edição de um registro com modelo `-modificado` nos três módulos.
  - [x] Mudar o modelo para o original e de volta para `-modificado`, conferindo que o previewer renderiza com o framework CSS correto (`gestor.html_editor.framework_css`).
  - [x] Abrir um registro de Destaques em `-modificado` e verificar que a aba de mapeamento de variáveis de item é populada instantaneamente com as variáveis extraídas localmente via regex.
- [x] Decisão registrada: [DEC-040](../decisions/DECISION-LOG.md#dec-040---2026-06-10---accepted)


## BATCH-028 - Persistência de Estilos de Widgets e Novo Módulo Publicador Índice (req-028)

- [x] **Persistência de Estilos (`css_compiled` e `html_extra_head`)**:
  - [x] Colunas adicionadas fisicamente nas tabelas do banco via migração (3 originais alteradas + nova `20260611110000` idempotente com guards `hasTable`/`hasColumn`).
  - [x] Leitura, gravação, backup e reidratação de placeholders implementados nos arquivos PHP dos 3 módulos (`menus.php`, `galleries.php`, `publisher-highlights.php`) — `adicionar`/`editar` (com backup)/`clonar`, incl. sanitização `[[VAR]]`→`@[[var]]@` e conversão inversa para o editor (`#pagina-css-compiled#`/`#pagina-html-extra-head#`).
  - [x] Envio e reidratação de valores originais na clonagem via placeholders `#css-compiled-original#`/`#html-extra-head-original#` (espelhando `#html-original#`/`#css-original#`); o trânsito efetivo no submit ocorre pelos textareas `name="css_compiled"`/`name="html_extra_head"` do html-editor.
- [x] **Injeção Centralizada e Desduplicação (`gestor.php`)**:
  - [x] Função helper `gestor_pagina_recursos_incluir` criada na biblioteca comum `gestor/bibliotecas/gestor.php` e funcional.
  - [x] Validação por hash MD5 (`$_GESTOR['recursos-incluidos-hashes']`) impede duplicidades de CSS/CSS compilado/HTML head repetidos na mesma página — validado por teste isolado (8/8).
  - [x] Função `gestor_componente()` refatorada (2 blocos de injeção: caminho `return_array` e caminho único) para usar a nova helper.
  - [x] Renderizadores (`menus.widget.php`, `galleries.widget.php`, `publisher-highlights.widget.php`) refatorados: `xxx_widget_montar_saida()` agora delega à helper e retorna HTML puro; `render`/`render_inline` selecionam e propagam `css_compiled`/`html_extra_head`.
- [x] **Módulo Publicador Índice (`publisher-index`)**:
  - [x] Tabela `publisher_index` criada via migração `20260611120000` (espelho de `publisher_highlights` + `css_compiled`/`html_extra_head`).
  - [x] Manifest `publisher-index.json` (templates `publisher-index-lista`/`publisher-index-grid`) e resources pt-br/en criados; módulo registrado em `ModulosData.json` e `UsuariosPerfisModulosData.json` (nome "Publicador Índice"/"Publisher Index", grupo `administracao-gestor`, ícone `list alternate outline`).
  - [x] CRUD (`publisher-index.php` + `publisher-index.js`) serializando os campos adicionais do `fields_schema` (`items_per_page`, `show_search_input`, `show_sorting_select`, `show_load_more_btn`) — inputs nas 6 páginas (pt-br/en).
  - [x] Widget renderer (`publisher-index.widget.php`): page load da 1ª página + injeção via helper + `publisher_index_render_ajax` (consulta paginada, busca `LIKE`, ordenação, `tem_mais`, retorna `''` para não disparar erro 500) — validado por teste (23/23).
  - [x] Script público (`publisher-index.widget.js`): busca com debounce 300ms, ordenação e "Carregar mais" (append via AJAX `ajax=sim`+`ajaxWidgets`).

### Evidência de Validação (BATCH-028)

- [x] Validação estática de sintaxe executada em 2026-06-11 (todos OK):
  - [x] `php -l` OK em `gestor/bibliotecas/gestor.php`, `menus.php`, `menus.widget.php`, `galleries.php`, `galleries.widget.php`, `publisher-highlights.php`, `publisher-highlights.widget.php`, `publisher-index.php`, `publisher-index.widget.php` e nas 2 migrações novas.
  - [x] `node --check` OK em `publisher-index.js` e `publisher-index.widget.js`.
  - [x] `json_decode` OK em `publisher-index.json`, `ModulosData.json`, `UsuariosPerfisModulosData.json`.
- [x] Testes de unidade executados (stubs de banco, sem Docker):
  - [x] Widget `publisher-index` — **23/23 asserts**: page load (10 de 25 itens, `tem_mais`, data-attributes resolvidos, no-item removido, recursos via helper, sem variáveis residuais), bloco condicional `search-input` removido quando `show_search=false`, AJAX páginas 2/3 (10 + 5 itens, `tem_mais` correto, só os itens sem contêiner), busca paginada (`LIKE`) e busca sem resultados retornando `no-item`.
  - [x] Helper `gestor_pagina_recursos_incluir` — **8/8 asserts**: injeção de css/css-compiled/html-extra-head, dedup por MD5 (mesmo conteúdo não duplica), conteúdo distinto adiciona, valores vazios ignorados.
- [ ] Testes manuais/runtime pendentes com o operador (após `🗃️ Projects - Update => Core` que registra o módulo `publisher-index`, páginas, templates, alvo/modo de IA, aplica as migrações e recalcula checksums):
  - [ ] Salvar CSS customizado, CSS compilado e HTML extra head nos 3 módulos e verificar que aparecem no `<head>` da página publicada sem duplicatas (ver código-fonte).
  - [ ] Clonar registros e verificar que o clone mantém todos os estilos e o extra head intactos.
  - [ ] Publicar uma página contendo o widget `publisher-index` e validar:
    - [ ] Listagem de itens inicial na tela.
    - [ ] Filtragem em tempo real digitando na busca (confirmação do debounce e injeção do AJAX).
    - [ ] Ordenação alfabética e por data (asc/desc).
    - [ ] Clique em "Carregar Mais" injetando novos itens abaixo e sumindo com o botão quando não há mais dados.
- [x] Decisão registrada: [DEC-041](../decisions/DECISION-LOG.md#dec-041---2026-06-11---accepted)


## BATCH-029 - Reestruturação e Otimização de Dados e Sincronização

- [x] **Mapeamento e Migração de Colunas** (DEC-042: escopo redirecionado para `variaveis`, única tabela pendente):
  - [x] Mapear migrações usando `linguagem_codigo` (`create_variaveis_table` + índice em `alter_recursos_add_plugin_id`; as 7 do req já em `language`).
  - [x] Migração de criação ajustada + índice composto `language` + nova migração corretiva idempotente (`20260705100000_rename...`).
  - [x] Atualizar referências no código (`configuracao.php`, `gestor.php`, `plugins-installer.php`, gerador, atualizador, plugin-banco) e regenerar `VariaveisData.json`.
- [x] **Bloco de Metadados e Tabelas Globais**:
  - [x] Adicionar bloco `"tabela"."config"` com regras de sincronização em 13 JSONs de módulo.
  - [x] Criar `gestor/resources/tables_config.json` (4 tabelas globais sem dono).
  - [x] Suportar a chave `"deletar"` nos blocos locais e no global.
- [x] **Refatoração do Gerador (`atualizacao-dados-recursos.php`)**:
  - [x] Motor genérico (Registry Pattern) consolidando `config` local + global.
  - [x] Agregar e consolidar regras `config` e deleção em `schema-metadata.json` (17 tabelas).
  - [x] Suportar ganchos `data-hooks.php` (globais e por módulo).
  - [x] Logs via `log_disco_local()`/`log_disco()` + `@` cego substituído por `ensureDir()`.
- [x] **Refatoração do Atualizador (`atualizacoes-banco-de-dados.php`)**:
  - [x] Leitura dinâmica de `schema-metadata.json` (`schemaMetadata()`), removendo `$preserveMap`/`$tabelasChaveNatural`/`$tabelasInsertOnly`.
  - [x] `max_allowed_packet` dinâmico + loteador threshold 70% (fallback 16MB) via `inserirEmLote`.
  - [x] Motor genérico de chave natural (`naturalKeyGenerica`) + WHERE genérico null-safe.
  - [x] Deleção imperativa (`executarDelecoes`) + transações PDO + logs unificados.
- [x] **Unificação e Visibilidade de Logs**:
  - [x] `log_unificado()` no script principal e no de plugins (incorporado de outro agente — DEC-042).
  - [x] Redirecionamento para `$GLOBALS['EXTERNAL_LOGGER']` quando definido.
  - [x] Impressão no stdout sob `PHP_SAPI === 'cli'`.
  - [x] Chave `db_logs` (+ `full_log`) na resposta de deploy da API (`api.php`).
  - [x] Captura e prefixo `[BANCO]` nos logs de `atualizacoes-sistema.php` (inline, sem `exec()`).

### Evidência de Validação (BATCH-029) — 2026-06-12

- [x] Validação estática executada:
  - [x] `php -l` OK em 11 arquivos (gerador, atualizador, plugin-banco, `api.php`, `atualizacoes-sistema.php`, `configuracao.php`, `gestor.php`, `plugins-installer.php`, 3 migrações).
  - [x] `json_decode` OK em 34 JSONs (13 módulos com `config` + `tables_config.json` + `schema-metadata.json` + `VariaveisData.json` + demais módulos).
  - [x] 0 referências a `linguagem_codigo` como coluna SQL fora da migração de rename.
- [x] Testes de unidade: `naturalKeyGenerica` 8/8 (paridade com o switch antigo); `inserirEmLote` em PDO SQLite (batch 50 c/ chunking, dedup fallback, simulate); `schemaMetadata` 17 tabelas; geração do contrato espelhando o hardcode.
- [x] **Teste end-to-end contra MySQL 8.0 real** (banco dedicado `conn2flow_test`, dropado ao fim; `conn2flow` real intacto): 6/6 OK — `modulos` INSERT em lote (3), UPDATE divergente (1) + NO-CHANGE (2), `variaveis` PRESERVE de `user_modified`, `usuarios` INSERT_ONLY (não atualiza), transação ROLLBACK desfaz insert, e deleção imperativa (`executarDelecoes`) removendo registro do bloco `deletar` do contrato.
- [ ] **Pendente com o operador**: `Update => Core` (regenerar contrato/checksums no pipeline) + deploy real (aplicar migrações incl. `rename` da `variaveis` no banco de dev/produção) e validar `db_logs` no endpoint de deploy via API (com/sem `full_log`) e o loteador em volume real.


## BATCH-030 - Autenticação Multi-Método, 2FA (App/E-mail), Social Login e Rotação JWT

- [ ] **Configurações Globais (admin-environment)**:
  - [ ] Toggles globais para métodos de login (Senha, Google, Meta) ativando/desativando formulários e botões na tela de login.
  - [ ] Toggle global de 2FA obrigatório (`AUTH_2FA_REQUIRED`) forçando a configuração inicial pós-login para quem não a tem.
  - [ ] Checkboxes de métodos permitidos de 2FA (App TOTP e Código por E-mail).
  - [ ] Gravação e leitura síncrona das credenciais OAuth e JWT no arquivo `.env`.
- [ ] **Autenticação de Dois Fatores (2FA) - Aplicativo & E-mail**:
  - [ ] Colunas `two_factor_secret`, `two_factor_enabled`, `two_factor_type`, `two_factor_email_code` e `two_factor_email_expire` adicionadas na tabela `usuarios`.
  - [ ] Classe de suporte a TOTP gerando e validando chaves com o Google Authenticator.
  - [ ] Fluxo de envio de código dinâmico por e-mail de 6 dígitos com expiração de 5 minutos.
  - [ ] Seção de Segurança em `perfil-usuario` (`?configurar-seguranca=sim`) habilitando/desabilitando 2FA e exibindo QR Code/validação de e-mail conforme as regras ativas.
  - [ ] Fluxo de login administrativo interceptando usuários e exigindo validação de 2FA (gerando código por e-mail se este for o tipo do usuário).
- [ ] **Login Social (OAuth 2.0 Google / Meta)**:
  - [ ] Tabela `usuarios_provedores` criada.
  - [ ] Biblioteca `oauth.php` implementada e redirecionando/autenticando perfis sociais Google e Meta.
  - [ ] Vínculo e desvinculo de contas funcionando na rota de Segurança do Perfil.
- [ ] **Sessões e Rotação de Chaves JWT**:
  - [ ] Biblioteca `jwt.php` gerenciando histórico de chaves em formato JSON na tabela `variaveis`.
  - [ ] Lógica de rotação de chaves funcionando (chave ativa vira expirada, gera-se nova ativa, chaves obsoletas expurgadas).
  - [ ] Decodificação de tokens com chaves em carência (grace period de 24h) aceita como válida e disparando renovação automática de token no cabeçalho de resposta.
- [ ] **Endurecimento de Endpoints**:
  - [ ] Tokens CSRF validados em posts de alteração de estado administrativos.
  - [ ] Detecção de sequestro de sessão (Session Hijacking) comparando User-Agent e bloco de IP (3 octetos), deslogando usuário em caso de discrepância.

### Evidência de Validação (BATCH-030)

#### Slices 1–2 (fundação: banco + bibliotecas puras) — 2026-06-13

- [x] **Slice 1 — Migrações Phinx** (`php -l` OK):
  - `20260706100000_add_two_factor_to_usuarios_table.php` (idempotente, guards `hasColumn`, up/down).
  - `20260706100010_create_usuarios_provedores_table.php` (guard `hasTable`, índice único `(provider_name, provider_uid)`, índice em `usuario_id`).
  - Timestamps `20260706*` escolhidos > maior existente (`20260705100000`) para garantir ordem de execução.
- [x] **Slice 2 — Bibliotecas puras** (`php -l` OK em `2fa.php`/`jwt.php`/`oauth.php`):
  - Testes PHPUnit permanentes criados: `tests/Unit/PHP/TwoFactorTest.php` e `tests/Unit/PHP/JwtTest.php` → **20/20 testes, 42 asserts OK** (`php vendor/bin/phpunit`).
  - Teste standalone com stubs de banco em memória → **28/28 OK**: HOTP (10 vetores RFC 4226 Apêndice D), TOTP (RFC 6238: T=59→287082, T=1111111109→081804), validação com drift ±1, geração de segredo Base32, URI otpauth; ciclo JWT completo (Active → rotação → Grace → fora-da-carência lança exceção → assinatura corrompida lança exceção).
- Observações de contrato (divergências justificadas do intake, padrão de batches anteriores):
  - `usuarios_provedores` sem FK física (convenção do legado: nenhuma migração usa `addForeignKey`; relacionamento por `usuario_id` integer + índice). ON DELETE CASCADE será tratado em código no fluxo de exclusão de usuário.
  - JWT: período de carência medido por `expired_at` (quando a chave foi rotacionada), com fallback `created_at`. O req §3.2 sugeria `created_at`, o que invalidaria o grace em produção (chave ativa vive `AUTH_JWT_ROTATION_DAYS` antes de expirar).
  - Função de e-mail real é `comunicacao_email()` (o req cita `gestor_email_enviar()` como placeholder).
  - `banco.php` implementa apenas `mysqli` (não PDO/SQLite); por isso o ciclo JWT end-to-end com banco fica para integração com MySQL real (operador) — a lógica já está coberta pelo standalone com stubs.

#### Slices 3–6 (integração: admin-environment, perfil-usuario, login, endurecimento) — 2026-06-13

- [x] **Slice 3 — admin-environment**: toggles de login (Senha/Google/Meta), credenciais OAuth condicionais, URIs de callback (readonly), 2FA obrigatório + métodos (App/E-mail), rotação JWT (dias/carência) + botão "Rotacionar Chaves JWT" (AJAX `rotacionar-jwt`); gravação no `.env`. Validação: `php -l` (admin-environment.php, config.php) + `node --check` (admin-environment.js) + JSON OK.
- [x] **Slice 4 — perfil-usuario / Segurança**: rota `?configurar-seguranca=sim` com seção 2FA (App TOTP + QR client-side via qrcodejs, E-mail) e contas sociais (vincular/desvincular); AJAX `seguranca-2fa-ativar`/`-desativar`/`-email-enviar`/`-social-vincular`/`-social-desvincular`; desativação exige senha (`password_verify`) + código. Validação: `php -l` + `node --check` + JSON OK.
- [x] **Slice 5 — login admin**: render dinâmico do form (senha condicional + botões sociais), interceptador 2FA no `signin` (fail-safe: só age com 2FA ativo/obrigatório), tela `signin-2fa` unificada (verify/setup), login social (`social-login` → `oauth-callback`) com vínculo/login por e-mail. Novas páginas `signin-2fa`/`social-login`/`oauth-callback` (pt-br/en) + i18n. Validação: `php -l` + `node --check` + JSON OK.
- [x] **Slice 6 — endurecimento**: `bibliotecas/seguranca.php` (Session Hijacking UA+bloco IP, CSRF helpers); validação no `gestor_permissao_token()` (fail-safe) + registro nos pontos de login. Teste PHPUnit `SegurancaTest` (bloco IP/UA). Validação: `php -l` (seguranca.php, gestor.php) + PHPUnit.
- Suíte PHPUnit completa: **32 testes / 84 asserts OK** (1 skip gated, 1 deprecation pré-existente do PHPUnit).

#### Observações de contrato adicionais (Slices 3–6)

- **Callback OAuth via rota do módulo**: `oauth_redirect_uri()` aponta para `{url}/oauth-callback/?provider={provider}` (rota do módulo perfil-usuario), não `/_api/auth/callback/{provider}` como no req §2 — evita alterar o roteador de API genérico; o admin-environment exibe a URI calculada.
- **Estado de fluxo 2FA/OAuth no store de sessão do sistema** (`gestor_sessao_variavel` → `sessoes_variaveis`): `pending_2fa_*`, `oauth_state`/`provider`/`action`/`link_user`. Iniciado automaticamente por `gestor_sessao_iniciar` (não usa `$_SESSION` nativo).
- **CSRF**: infra pronta; aplicação estrita global pendente (rollout incremental) para não quebrar AJAX legados.

#### Pendências com o operador (runtime)

- [ ] Aplicar migrações (`Update => Core` / `phinx migrate`): colunas 2FA em `usuarios` + tabela `usuarios_provedores`.
- [ ] Registrar recursos novos no banco (pipeline): páginas `signin-2fa`/`social-login`/`oauth-callback`, variáveis i18n de segurança/login social, recalcular checksums.
- [ ] Validação funcional: login tradicional (com senha desativada também), login social Google/Meta (exige credenciais OAuth reais), 2FA App (Google Authenticator) e E-mail, rotação de chaves JWT (token assinado pela chave antiga em carência), e session hijacking (mudança de UA/bloco de IP derruba a sessão).


## BATCH-031 - Estruturação de Framework de Testes Unitários e E2E

- [x] **Configuração e Estrutura Física**:
  - [x] Pasta `tests/` estruturada com subdiretórios Unit, Integration e E2E.
  - [x] Arquivo `phpunit.xml` e bootstrap PHP configurados.
  - [x] Arquivo `vitest.config.js` e mock do DOM configurados.
- [x] **Testes de Backend (PHPUnit)**:
  - [x] PHPUnit integrado ao Composer.
  - [x] Testes unitários do core/helpers e integração de banco de dados/rotas implementados.
- [x] **Testes de Frontend (Vitest)**:
  - [x] Vitest rodando e testando funções javascript dos módulos.
- [x] **Testes Funcionais E2E (Playwright)**:
  - [x] Playwright configurado e executando fluxos completos de login, destaques e index.
- [x] **Integração de CI/CD**:
  - [x] Script de execução local e workflow do GitHub Actions configurados.

### Evidência de Validação (BATCH-031)

- [x] Suítes de testes unitários e de integração executadas com sucesso localmente.
- [ ] Execução bem-sucedida do pipeline de testes na nuvem.


---
## BATCH-032 - Login sem Senha por E-mail e Auxílio de Configuração OAuth (req-032)

- [x] **Configurações Globais no admin-environment**:
  - [x] Checkbox `auth_method_email_active` habilitado e gravado no `.env` como `AUTH_METHOD_EMAIL_ACTIVE`.
  - [x] Instruções passo a passo ("How-To") e links para console do Google API e portal do Meta Developer integrados na interface.
- [x] **Interface do Perfil e Login (acessar-sistema)**:
  - [x] Abas/Toggles de alternância dinâmica entre "Entrar com Senha" e "Entrar com Código por E-mail" visíveis quando ambos estão ativos.
  - [x] Ocultação completa do campo de senha quando apenas o login por e-mail está ativo.
  - [x] Links do Google e Meta abrindo em novas abas do navegador.
- [x] **Autenticação sem Senha**:
  - [x] Inserção de e-mail ativo gerando código de 6 dígitos temporário e enviando por e-mail via `two_factor_email_send_code`.
  - [x] Redirecionamento correto para `signin-2fa` com as variáveis de sessão `pending_2fa_user`, `pending_2fa_mode = 'verify'`, e `pending_2fa_type = 'email'` salvas no banco.
  - [ ] Verificação e conclusão bem-sucedida da autenticação ao digitar o código recebido.

### Evidência de Validação (BATCH-032)
- [ ] Testes manuais do login sem senha concluídos.
- [x] Linting estático (`php -l`, `node --check`, `JSON.parse`, `git diff --check`) limpo nos arquivos alterados.


---
## BATCH-033 - Segurança no Acesso e Geração de Chaves de API (req-033)

- [ ] **Configurações Globais no admin-environment (Aba API)**:
  - [ ] Nova aba "API" visível nas configurações globais.
  - [ ] Checkboxes com a lista de perfis do sistema (`usuarios_perfis`) funcionando e salvando IDs em `AUTH_API_ALLOWED_PROFILES`.
  - [ ] Toggles para ativar/desativar métodos de login (Senha/E-mail) para API salvos no `.env`.
  - [ ] Toggles para obrigar 2FA na API (`AUTH_API_2FA_REQUIRED`) e selecionar métodos (App/E-mail) salvos no `.env`.
- [ ] **Geração de Chaves (`oauth-authenticate`)**:
  - [ ] Usuários sem o perfil autorizado listado em `AUTH_API_ALLOWED_PROFILES` recebem bloqueio imediato na rota.
  - [ ] Formulário `oauth-authenticate` suporta alternância dinâmica de login (senha vs e-mail code) de forma reativa.
  - [ ] Interceptador 2FA ativo: se o usuário logar com sucesso e o 2FA for exigido, o token de resposta é mantido retido em `pending_oauth_tokens` no banco de sessões e o fluxo é redirecionado para `oauth-authenticate-2fa/`.
- [ ] **Verificação de Segundo Fator para API (`oauth-authenticate-2fa`)**:
  - [ ] Nova página `oauth-authenticate-2fa` renderizada para entrada do código de 6 dígitos.
  - [ ] Verificação bem-sucedida do código liberando e entregando o token final armazenado na sessão (via JSON ou redirecionamento OAuth).
  - [ ] Reenvio de e-mail 2FA para chaves OTP funcionando na interface.

### Evidência de Validação (BATCH-033)
- [ ] Testes manuais do controle de perfil e 2FA na emissão de token de API concluídos.
- [ ] Linting estático (`php -l` e `node --check`) limpo nos arquivos alterados.


---
## BATCH-034 - Aprimoramento do Editor HTML Visual (req-034)

- [ ] **Expansão de Tags Editáveis**:
  - [ ] Verificar se tags de contêineres e blocos complexos (ex: `div`, `section`, `table`, `ul`) são detectadas sob hover.
  - [ ] Validar que ao tentar editar um contêiner, o editor abre no formato `'code'` (outerHTML) no CodeMirror.
- [ ] **Isolamento de Hover e Seleção Persistente**:
  - [ ] Validar que passar o mouse destaca os elementos (hover overlay dinâmico).
  - [ ] Validar que clicar em um elemento fixa um contorno de seleção persistente e exibe a barra de ferramentas flutuante.
  - [ ] Confirmar que tirar o mouse do elemento selecionado mantém o destaque de seleção e a barra de ferramentas exibidos.
  - [ ] Confirmar que passar o mouse sobre outros elementos continua a mostrar o hover overlay de forma isolada.
- [ ] **Barra de Ferramentas Flutuante**:
  - [ ] Duplicar: Validar que clicar em duplicar cria um elemento irmão idêntico logo abaixo no DOM e transfere a seleção para ele.
  - [ ] Excluir: Validar que clicar no lixo abre o `confirm()` do navegador. Rejeitar mantém o elemento; aceitar remove-o do DOM.
  - [ ] Editar: Validar que abre o modal de edição correspondente ao tipo de tag.
- [ ] **Drag and Drop (DnD)**:
  - [ ] Validar que segurar o botão de arrastar e mover o mouse exibe a linha horizontal tracejada `.conn2flow-dnd-placeholder`.
  - [ ] Confirmar que soltar o botão move o elemento fisicamente para a nova posição no DOM e atualiza o CodeMirror pai.
- [ ] **Inclusão de Novos Elementos e Widgets**:
  - [ ] Validar que clicar no botão "+" no cabeçalho do visual-modal abre o popup dropdown categorizado.
  - [ ] Confirmar que selecionar uma opção HTML ou Widget (obtendo slugs do backend via AJAX) entra no modo de inclusão e exibe placeholder de drop.
  - [ ] Confirmar que clicar insere o elemento/widget no DOM do iframe.
- [ ] **Histórico de Estados (Desfazer / Refazer)**:
  - [ ] Confirmar que o histórico é salvo no stack a cada alteração estrutural no DOM.
  - [ ] Validar que clicar nas setas de desfazer/refazer restaura o DOM do iframe e o CodeMirror da janela pai perfeitamente.
  - [ ] Validar que os atalhos de teclado `Ctrl+Z` e `Ctrl+Y` disparam o desfazer/refazer em ambos os contextos (janela pai e iframe).
  - [ ] Validar que o limite parametrizado `config.undoLimit` (padrão `30`) descarta estados mais antigos quando ultrapassado.
- [ ] **Breadcrumb DOM e Quick Tailwind Styler**:
  - [ ] Validar que a trilha horizontal exibe a árvore correta de ancestrais do nó ativo.
  - [ ] Confirmar que clicar em um item da trilha transfere a seleção para o elemento correspondente.
  - [ ] Validar que o styler lista as classes do elemento em tags removíveis e que clicar no "x" remove as classes.
  - [ ] Validar que digitar novas classes no input e pressionar enter aplica as regras ao nó.
- [ ] **Resize Handles e Widget Wrappers**:
  - [ ] Confirmar que arrastar as alças laterais do contêiner do iframe redimensiona a largura e exibe a largura em pixels.
  - [ ] Validar que os comentários de widgets são envelopados na `div.conn2flow-widget-wrapper` com borda tracejada amarela.
  - [ ] Confirmar que o conteúdo interno do widget é bloqueado de interações individuais e tratado de forma atômica no DOM.
  - [ ] Confirmar que o salvamento converte as divs de wrapper de volta para os comentários originais de widget no CodeMirror.

### Evidência de Validação (BATCH-034)
- [x] Linting estático limpo (2026-06-13): `php -l gestor/bibliotecas/html-editor.php` OK; `node --check` OK em `html-editor.js`, `html-editor-interface.js`, `html-editor-visual-controls.js` (novo) e `html-editor-helper.js`; `JSON.parse` OK em `resources/{pt-br,en}/variables.json`.
- [ ] Testes de interação do editor visual (hover, seleção, DnD, duplicação, exclusão e inclusão) validados — **pendente com o operador** (runtime no navegador).
- [ ] Testes de Undo/Redo, Breadcrumb, Tailwind Styler, Resize Handles e Widget Wrappers validados — **pendente com o operador**.
- Arquivos: novo `gestor/assets/interface/html-editor-visual-controls.js`; reescrito `html-editor.js`; `html-editor.php` (rota AJAX `html-editor-widgets-list` + inclusões); `html-editor-interface.js` (handler `.screenPagina` removido + `getCleanHtml()` no save); `html-editor-visual-modal.html` (pt-br/en); `variables.json` (pt-br/en). Decisões: [DEC-047](../decisions/DECISION-LOG.md) (design) e [DEC-048](../decisions/DECISION-LOG.md) (execução).
- Pendência de deploy: `🗃️ Projects - Update => Core` (sincroniza assets/componentes, compila `VariaveisData.json` a partir do `variables.json`, recalcula checksums).

---
## BATCH-035 - Refinamentos e Ajustes no Editor HTML Visual (req-035)

- [ ] **Toolbar à Direita**:
  - [ ] Verificar se `#html-editor-floating-toolbar` é exibido alinhado à direita da caixa de seleção do elemento selecionado (em vez do lado esquerdo).
  - [ ] Confirmar que o comportamento de exibição acima ou abaixo do elemento é preservado e funciona em elementos com pouco espaço ou próximos à borda da tela.
- [ ] **Labels "Ancestrais:" e "Filhos:"**:
  - [ ] Validar a presença das labels de texto cinza "Ancestrais:" e "Filhos:" nos breadcrumbs correspondentes.
- [ ] **Seletor de Filhos (Children Breadcrumb)**:
  - [ ] Validar que, se o elemento selecionado tiver filhos editáveis directos, a lista de filhos é renderizada com o separador `|` (atualizado de `/`).
  - [ ] Confirmar que o fundo da caixa de filhos é ligeiramente mais claro do que o de ancestrais para diferenciar os contêineres.
  - [ ] Passar o mouse sobre um filho na lista e confirmar que o hover overlay correspondente é acionado.
  - [ ] Clicar em um filho da lista e confirmar que ele é selecionado e os controles são transferidos para ele.
  - [ ] Confirmar que se o elemento selecionado não possui filhos válidos, a barra de filhos fica oculta.
- [ ] **Destaque de Hover nos Breadcrumbs**:
  - [ ] Confirmar que passar o mouse sobre os links de Ancestrais ou Filhos no breadcrumb desenha uma borda tracejada roxa (tom da seleção) no elemento físico do iframe.
  - [ ] Confirmar que tirar o mouse oculta esse destaque roxo.
  - [ ] Confirmar que o hover normal azul no preview direto com o cursor continua a funcionar.
- [ ] **Empilhamento Dinâmico**:
  - [ ] Confirmar o empilhamento vertical correto (Ancestrais -> Filhos -> Classes Tailwind) sem sobreposições.

### Evidência de Validação (BATCH-035)
- [x] Linting estático limpo (2026-06-13): `node --check` OK em `gestor/assets/interface/html-editor.js` e `html-editor-interface.js`.
- [ ] Testes de interação (toolbar à direita, seletor de filhos, hover roxo, empilhamento) — **pendente com o operador** (runtime no navegador, após `🗃️ Projects - Update => Core`).
- Arquivos: `gestor/assets/interface/html-editor.js` (todo o escopo do lote) e `html-editor-interface.js` (`sistemaSel` do save). Decisão: [DEC-049](../decisions/DECISION-LOG.md).

---
## BATCH-036 - Copiar/Colar e Embrulhar Elementos (req-036)

- [ ] **Copiar e Colar (Copy/Paste)**:
  - [ ] Verificar se os botões "Copiar" e "Colar" estão presentes na barra flutuante.
  - [ ] Confirmar que o botão Colar fica inativo/invisível por padrão e ativo/visível apenas após copiar um elemento.
  - [ ] Selecionar um elemento, clicar em Copiar (ou usar o atalho `Ctrl + C`), selecionar outro elemento (alvo), clicar em Colar (ou usar `Ctrl + V`) e confirmar que o clone foi inserido exatamente abaixo (como irmão) do alvo.
  - [ ] Confirmar que colar o mesmo item múltiplas vezes funciona sem limitações.
- [ ] **Embrulhar Elemento (Wrap)**:
  - [ ] Verificar se o botão "Embrulhar" está presente na barra flutuante.
  - [ ] Clicar nele e verificar se abre um popup com as opções de tag (`div`, `section`, `a`, `p`, `article`, `aside`).
  - [ ] Selecionar uma tag e confirmar no DOM/Breadcrumbs que o elemento selecionado agora é filho direto do novo elemento embrulho criado.
  - [ ] Confirmar que o foco da seleção visual se manteve no elemento original (filho), e não no wrapper recém-criado.

### Evidência de Validação (BATCH-036)
- [x] Linting estático limpo (2026-06-13): `node --check` OK em `gestor/assets/interface/html-editor.js` e `html-editor-visual-controls.js`.
- [ ] Testes de interação (copiar/colar por botão e atalho; embrulhar com foco preservado) — **pendente com o operador** (runtime no navegador, após `🗃️ Projects - Update => Core`).
- Arquivos: `gestor/assets/interface/html-editor.js` (clipboard, botões e wrap) e `html-editor-visual-controls.js` (atalhos Ctrl+C/V na janela pai). Decisão: [DEC-050](../decisions/DECISION-LOG.md).

---
## BATCH-037 - Painel Auxiliar de Formatação Visual (req-037)

- [ ] **Interface de Duas Colunas (Visual UI Helper)**:
  - [ ] Verificar se `#html-editor-tailwind-styler` é dividido verticalmente em duas colunas (Esquerda: tags/autocomplete; Direita: Visual Helper).
  - [ ] Validar se o layout se ajusta e se renderiza perfeitamente alinhado.
  - [ ] **Empilhamento Responsivo**: Selecionar um elemento estreito (ex: `< 400px`) e validar se a interface se ajusta para empilhamento vertical automaticamente. Selecionar um elemento largo e verificar se ela volta ao padrão horizontal.
- [ ] **Controles de Alinhamento e Espaçamento**:

  - [ ] Clicar nos botões de alinhamento e confirmar que as classes correspondentes (`text-left`, `text-center`, etc.) são aplicadas e as conflitantes são limpas do elemento.
  - [ ] Clicar nos botões de padding e bordas e confirmar a aplicação correta e remoção de redundâncias.
- [ ] **Controles de Cores (Texto e Fundo)**:
  - [ ] Clicar nos botões coloridos de texto e fundo e verificar se as classes `text-[cor]` e `bg-[cor]` são atualizadas corretamente e limpas dos conflitos.
- [ ] **Sincronização Bidirecional**:
  - [ ] Confirmar que selecionar um elemento com classes pré-existentes destaca os botões corretos do painel visual.
  - [ ] Confirmar que cliques no painel visual atualizam instantaneamente a lista de tags da coluna esquerda.

### Evidência de Validação (BATCH-037)
- [x] Linting estático limpo (2026-06-13): `node --check gestor/assets/interface/html-editor.js` OK.
- [ ] Testes de interação (2 colunas, empilhamento <400px, grupos exclusivos, paletas de cor sem afetar alinhamento/`bg-cover`, sincronização) — **pendente com o operador** (runtime no navegador, após `🗃️ Projects - Update => Core`).
- Arquivo: `gestor/assets/interface/html-editor.js`. Decisão: [DEC-051](../decisions/DECISION-LOG.md).

---
## BATCH-038 - Inversão e Expansão do Painel Auxiliar de Formatação Visual (req-038)

- [ ] **Inversão de Colunas**:
  - [ ] Verificar se `#html-editor-tailwind-styler` renderiza a coluna de controles visuais (Visual UI Helper) à **esquerda** e a coluna de tags/autocomplete à **direita**.
  - [ ] Confirmar que no empilhamento vertical (largura < 400px), a coluna visual fica acima das tags.
- [ ] **Deslocamento da Toolbar na Borda Inferior**:
  - [ ] Selecionar um elemento encostado no topo da tela (forçando a toolbar a aparecer na borda inferior) e verificar se os overlays inferiores (Ancestrais, Filhos e Styler) são empurrados para baixo da barra flutuante, sem qualquer sobreposição.
- [ ] **Novas Propriedades Visuais**:

  - [ ] Validar a presença e funcionamento dos novos grupos: tamanho de texto (`text-sm` a `text-xl`), peso de texto (`font-normal` a `font-bold`), margem externa (`m-0` a `m-8`), espessura de borda (`border-0` a `border-4`), paleta de cor de borda (8 cores), opacidade (`opacity-100` a `opacity-25`) e layout/display (`block`, `inline-block`, `flex`, `grid`).
- [ ] **Limpeza e Sincronização Estendidas**:
  - [ ] Validar que cliques nos novos botões aplicam a classe correta, removem as concorrentes do mesmo grupo e atualizam as tags do lado direito.
  - [ ] Confirmar que carregar elementos com estas classes pré-definidas destaca os botões corretos com `.active`.

### Evidência de Validação (BATCH-038)
- [x] Linting estático limpo (2026-06-13): `node --check gestor/assets/interface/html-editor.js` OK.
- [ ] Testes de interação (colunas invertidas, empilhamento <400px, toolbar embaixo sem sobrepor, 20 grupos aplicando/sincronizando sem afetar classes de outros grupos) — **pendente com o operador** (runtime no navegador, após `🗃️ Projects - Update => Core`).
- Expansão criativa autorizada: 20 grupos em 4 seções (Texto/Layout/Caixa/Aparência). Arquivo: `gestor/assets/interface/html-editor.js`. Decisão: [DEC-052](../decisions/DECISION-LOG.md).

---
## BATCH-039 - Melhorias e Aprimoramentos do Editor HTML Visual (req-039)

- [ ] **Seção "Fundo" (Background)**:
  - [ ] Verificar se uma nova seção "Fundo" (Background) é criada no visual helper, contendo a cor de fundo (bgColor), seleção de imagem, repeat, size e position.
  - [ ] Clicar em "Selecionar Imagem" no painel de Fundo e confirmar que o ImagePicker abre, e ao selecionar uma imagem, ela é aplicada como estilo inline `background-image` e o preview de miniatura aparece no styler.
  - [ ] Clicar em "Limpar" e confirmar que a imagem de fundo inline é removida e a miniatura desaparece.
  - [ ] Validar a aplicação das classes Tailwind de repetição, tamanho e posição da imagem de fundo.
- [ ] **Deseleção e Toggle de Seleção**:
  - [ ] Confirmar que o novo botão "Deselecionar" (classe `.he-tb-deselect`, com visual diferenciado) aparece na barra flutuante. Clicar nele e verificar se a seleção é totalmente limpa (some overlays, toolbar, styler).
  - [ ] Clicar em um elemento selecionado ativo no editor visual e verificar se ele é deselecionado (toggle).
- [ ] **Preservação de Rolagem no Histórico (Scroll Sync)**:
  - [ ] Editar um elemento que exija rolagem (fora do viewport inicial), fazer alterações, rolar o scroll, e dar Ctrl+Z ou Ctrl+Y. Verificar se o scroll vertical do iframe retorna exatamente para onde estava quando o snapshot foi salvo.
- [ ] **Wrapping de Breadcrumbs**:
  - [ ] Selecionar um elemento na borda direita do iframe com breadcrumbs longos e confirmar que os breadcrumbs (ancestrais e filhos) quebram linha em vez de sumir da tela, e que a sua coordenada `left` é recalculada para não transbordar à direita.
- [ ] **Elemento Fantasma (Ghost Element)**:
  - [ ] Clicar no botão "+" e escolher um elemento ou widget. Mover o cursor sobre o iframe e confirmar que uma réplica fantasma do elemento com opacidade 0.6 e borda tracejada segue o cursor perfeitamente.
- [ ] **Highlight de Append Completo**:
  - [ ] Durante arraste ou inserção, posicionar o cursor em áreas de contêiner sem posição em linha clara. Confirmar que o contêiner de destino completo fica realçado por uma borda amarela tracejada de 4 lados.
- [ ] **Renderização de Widgets Real (AJAX)**:
  - [ ] Inserir um novo widget (ex: menus ou galleries) e confirmar que o editor visual exibe o esqueleto HTML renderizado real (via endpoint AJAX `html-editor-widget-render` que invoca `widgets_get()`) dentro do `.conn2flow-widget-inner` da div virtual.

### Evidência de Validação (BATCH-039)
- [x] Linting estático limpo (2026-06-14): `php -l gestor/bibliotecas/html-editor.php` OK; `node --check` OK em `html-editor.js`, `html-editor-interface.js` e `html-editor-visual-controls.js`.
- [ ] Testes de interação manual no navegador de todas as funcionalidades descritas — **pendente com o operador** (runtime no navegador, após `🗃️ Projects - Update => Core`).
- Arquivos: `gestor/assets/interface/html-editor.js`, `gestor/assets/interface/html-editor-visual-controls.js`, `gestor/assets/interface/html-editor-interface.js`, `gestor/bibliotecas/html-editor.php`. Decisão: [DEC-053](../decisions/DECISION-LOG.md).

---
## BATCH-040 - Ajustes Finais no Pré-visualizador de Widgets e Elemento Fantasma do Cursor (req-040)

- [ ] **Renderização de Widgets no Pré-visualizador (`#iframe-visualizacao-pagina`)**:
  - [ ] Ao clicar no botão "Voltar" (`previsualizarVoltar`) e retornar ao pré-visualizador de página, confirmar se os widgets contidos na página (ex: menus, galerias, destaques) são carregados e renderizados dinamicamente via AJAX no iframe de pré-visualização, mostrando o conteúdo real em vez de marcadores vazios ou ocultos.
- [ ] **Elemento Real no Cursor Fantasma (`#html-editor-insert-ghost`)**:
  - [ ] No modo de inserção (após selecionar um elemento ou widget no "+" do modal), confirmar se o contêiner fantasma que segue o cursor renderiza o elemento físico real (como um parágrafo formatado, link `Novo link`, ou imagem padrão) ou o widget completo renderizado via AJAX, acompanhando o cursor do mouse de forma fluida.
  - [ ] Verificar se o contêiner do cursor fantasma possui visualização limpa e elegante, livre de bordas duras roxas sobrecarregadas ou corte de texto interno (`white-space: nowrap`/`overflow: hidden` desativados).

### Evidência de Validação (BATCH-040)
- [x] Linting estático limpo (2026-06-14): `node --check` OK em `gestor/assets/interface/html-editor-interface.js` e `html-editor.js`.
- [ ] Testes de interação manual no navegador de todas as funcionalidades descritas — **pendente com o operador** (runtime no navegador, após `🗃️ Projects - Update => Core`):
  - Voltar do editor visual e ver os widgets renderizados no pré-visualizador da página.
  - Modo de inserção: o elemento/widget real renderizado acompanha o cursor numa caixa flutuante.
- Arquivos: `gestor/assets/interface/html-editor-interface.js`, `gestor/assets/interface/html-editor.js`. Decisão: [DEC-054](../decisions/DECISION-LOG.md).


---
## BATCH-041 - Correções no Módulo publisher-index (Busca, Acentuação, Duplicações, CRUD e Métricas) (req-041)

- [ ] **Busca Insensível a Acentos no Widget**:
  - [ ] Pesquisar por "Títu" (com acento) no campo de busca do widget (`.publisher-index-search`) e garantir que a publicação "Título dessa página" seja retornada mesmo se gravada de forma Unicode corrompida.
- [ ] **Correção de Escape Unicode**:
  - [ ] Confirmar se textos renderizados no widget que possuíam caracteres corrompidos sem barra (ex: `Tu00edtulo dessa pu00e1gina`) são devidamente exibidos com os acentos corretos (ex: `Título dessa página`).
- [ ] **Filtragem de Páginas Index / Duplicados**:
  - [ ] Verificar se apenas registros de publicações reais associados ao publicador são exibidos no widget index (e que a página pai do índice ou páginas comuns sem registros em `publisher_pages` são ignoradas através do `INNER JOIN`).
- [ ] **Métricas de Paginação**:
  - [ ] Validar que as variáveis `[[page_count]]` e `[[page_total]]` são resolvidas no widget.
  - [ ] Validar que ao realizar busca, ordenação ou paginação, os contadores dinâmicos `[data-page-count]` e `[data-page-total]` atualizam seus valores na tela corretamente.
- [ ] **Limpeza de Campos no CRUD**:
  - [ ] Acessar as páginas administrativas do `publisher-index` (adicionar, editar, clonar) e confirmar que o campo redundante "Quantidade máxima de itens" foi removido.
  - [ ] Confirmar que o layout do grid realinhou os campos "Regra de Alimentação" e "Ordenação" em 8 colunas cada, preenchendo a linha de 16 colunas.
- [ ] **Mover Seletor Manual e Corrigir Typo**:
  - [ ] Confirmar que o selecionador de itens manuais (`#manual-items-wrapper`) agora aparece dentro da seção de "Regra de Curadoria" (antes de "Controles de Exibição do Índice").
  - [ ] Verificar que não existem typos de sintaxe (como `div\ class`) na estrutura do DOM do manual items.
- [ ] **Integração com o Editor HTML Visual**:
  - [ ] Confirmar que no Editor Visual, as variáveis do tipo `[[item#X]]` são mapeadas corretamente no autocomplete lateral do alvo `publisher-index`.
  - [ ] Confirmar que o modo de simulação no Editor Visual renderiza a lista de itens com base na quantidade definida em `items_per_page`.

### Evidência de Validação (BATCH-041)

- Validação estática executada em 2026-06-15 (todos OK):
  - `php -l gestor/modulos/publisher-index/publisher-index.widget.php` → `No syntax errors detected`.
  - `node --check` OK em `publisher-index.widget.js`, `publisher-index.js` e `gestor/assets/interface/html-editor-interface.js`.
  - `JSON.parse` OK em `publisher-index.json` (após registro dos 2 novos templates + bumps de versão).
  - Grep de regressão: nenhuma ocorrência residual de `name="count"`/`id="count"` nas 6 páginas do CRUD, nem do typo `div\ class`/`</div\>`, nem de `four wide field`/`six wide field`.
- Arquivos alterados:
  - `gestor/modulos/publisher-index/publisher-index.widget.php` (helpers `publisher_index_widget_unicode_escape`, `publisher_index_widget_corrigir_unicode`, `publisher_index_widget_clausula_busca`, `publisher_index_widget_contar_publicacoes`; INNER JOIN; busca disjuntiva; decodificação Unicode no título/campos; métricas `[[page_count]]`/`[[page_total]]` + `total` no AJAX).
  - `gestor/modulos/publisher-index/publisher-index.widget.js` (helper `atualizarMetricas` atualizando `[data-page-count]`/`[data-page-total]` ao concluir busca/ordenação/load-more).
  - `gestor/modulos/publisher-index/publisher-index.js` (remoção da dependência do `#count`: hidratação, listener, submit e preview — `schema.count` mantido como valor seguro).
  - 6 páginas CRUD (`publisher-index-{adicionar,editar,clonar}` pt-br/en): remoção do `#count`, `rule`/`order_by` em `eight wide field`, `#manual-items-wrapper` movido para dentro da "Regra de Curadoria" e typo `div\ class` corrigido.
  - 2 templates existentes (`publisher-index-lista`/`-grid` pt-br/en): bloco de métricas "Exibindo X de Y" com `[data-page-count]`/`[data-page-total]`.
  - `gestor/assets/interface/html-editor-interface.js` (`alvoUsaItemVars` + `backupCallbackMap` + ocultar `.publisher-design-mode-simulation` + branch de simulação por `#items_per_page` + `window.publisher_index_update_target_variables`).
- Arquivos criados (pedido adicional do Engenheiro Chefe Humano nesta rodada — 2 novos modelos):
  - `publisher-index-timeline` (Linha do Tempo) pt-br/en — trilho vertical com marcadores.
  - `publisher-index-agenda` (Agenda) pt-br/en — cartões horizontais com bloco de data em destaque.
  - Ambos usam apenas variáveis garantidas (`[[item#url]]`/`[[item#titulo]]`/`[[item#data]]`), globais (`[[grupo_slug]]`/`[[publisher_id]]`/`[[items_per_page]]`/`[[ordenacao]]`/`[[page_count]]`/`[[page_total]]`) e os blocos `search-input`/`sort-select`/`item`/`no-item`/`load-more`; registrados em `publisher-index.json` (pt-br/en, `version` 1.0, checksums vazios para o pipeline calcular).
- Versionamento: `versao` do módulo 1.0.0 → 1.1.0; templates lista/grid e páginas adicionar/editar/clonar 1.1 → 1.2; checksums mantidos (recálculo automático pelo pipeline UPSERT).
- Decisão registrada: [DEC-055](../decisions/DECISION-LOG.md#dec-055---2026-06-15---accepted) (+ nota de execução sobre os 2 templates extras).
- [ ] Testes de interação manual no navegador — **pendentes com o operador** (após `🗃️ Projects - Update => Core` que registra os 2 novos templates, recompila as páginas/templates alterados e recalcula checksums):
  - [ ] Busca acentuada ("Títu") retorna publicações com Unicode corrompido no banco; títulos/campos exibidos com acentos corretos.
  - [ ] INNER JOIN remove a própria página de índice e páginas comuns sem registro em `publisher_pages`.
  - [ ] Métricas "Exibindo X de Y" atualizam em busca/ordenação/"Carregar mais".
  - [ ] CRUD sem o campo "Quantidade máxima de itens"; `rule`/`order_by` ocupando 8+8 colunas; `#manual-items-wrapper` na seção de Regra de Curadoria.
  - [ ] Editor Visual: autocomplete `[[item#X]]` do alvo `publisher-index` e simulação multiplicando por `items_per_page`.
  - [ ] Novos modelos "Linha do Tempo" e "Agenda" disponíveis no dropdown de modelos e renderizando corretamente no preview e no site.

---
## BATCH-042 - Controle de Métricas no Módulo publisher-index e Suíte de Testes (req-042)

- [x] **Controle show_metrics no Schema e CRUD**:
  - [x] Validar que o campo `show_metrics` foi adicionado ao `fields_schema` do módulo.
  - [x] Verificar nos arquivos de CRUD (`adicionar.html`, `editar.html`, `clonar.html` em `pt-br`/`en`) a presença do checkbox `#show_metrics` com o label correspondente.
  - [x] Validar que `publisher-index.js` carrega e salva o valor do checkbox corretamente na serialização de dados.
- [x] **Condicional de Métricas no Widget**:
  - [x] Garantir que o bloco `<!-- metrics < --> ... <!-- metrics > -->` em torno das métricas é ocultado/exibido no widget renderizado conforme o estado do toggle `show_metrics`.
  - [x] Verificar nos templates físicos (lista, grid, timeline, agenda em ambos os idiomas) que a div `.publisher-index-metrics` está envolvida adequadamente pelos delimitadores de bloco condicional.
- [x] **Suíte de Testes Unitários e de Integração**:
  - [x] Executar os testes unitários em `tests/Integration/PublisherIndexWidgetTest.php` e validar a cobertura para `publisher_index_widget_unicode_escape()` e `publisher_index_widget_corrigir_unicode()`.
  - [x] Confirmar o funcionamento dos testes integrados conectando-se ao banco de dados temporário `conn2flow_test` no Docker, validando a busca disjuntiva, INNER JOIN e contagens sem interferir no banco de desenvolvimento real.

### Evidência de Validação (BATCH-042)
- [x] Linting estático limpo (`node --check` / `php -l`).
- [x] Testes automatizados executados com sucesso via PHPUnit (`composer test`).
- [ ] Testes manuais no navegador validados pelo operador.

Evidência automatizada em 2026-06-15:
- `php -l gestor/modulos/publisher-index/publisher-index.php` → `No syntax errors detected`.
- `php -l gestor/modulos/publisher-index/publisher-index.widget.php` → `No syntax errors detected`.
- `php -l tests/Integration/PublisherIndexWidgetTest.php` → `No syntax errors detected`.
- `node --check gestor/modulos/publisher-index/publisher-index.js` → OK.
- `composer test` → OK (`36 tests`, `97 assertions`, `2 skipped`).
- Teste integrado MySQL dedicado com `CONN2FLOW_DB_DATABASE=conn2flow_test` → OK (`1 test`, `8 assertions`).

---
## BATCH-043 - Curadoria Manual no Módulo publisher-index, Novos Templates e Variáveis de Widget Inline (req-043)

- [ ] **Curadoria Manual no publisher-index**:
  - [ ] Ao selecionar a regra `manual`, verificar se o widget renderiza apenas os itens salvos em `selected_items` no page load e AJAX de paginação/busca.
  - [ ] Validar que a ordenação respeita exatamente a ordem em que foram selecionados em `selected_items`.
  - [ ] Validar que buscas textuais são filtradas e paginadas corretamente em PHP.
- [ ] **4 Novos Modelos com Imagem Destaque**:
  - [x] Verificar se os novos templates (`publisher-index-grid-imagem` e `publisher-index-lista-imagem` em `pt-br`/`en`) foram criados e registrados em `publisher-index.json`.
  - [ ] Validar que exibem imagens destaques usando a variável `@[[item#imagem]]@`.
- [ ] **Variável de Widget no CRUD**:
  - [ ] Acessar os 4 módulos (`publisher-index`, `publisher-highlights`, `menus`, `galleries`) nas 3 telas CRUD (adicionar, editar, clonar, pt-br/en).
  - [ ] Validar a presença do campo "Variável do Widget" com input read-only `#hep-widget-val` e o botão "Copiar".
  - [ ] Validar que o clique em "Copiar" coloca com sucesso a variável (ex: `[[widgets#modulo->render(...)]]`) na área de transferência.
- [ ] **Suporte a Widgets Inline no Editor e Pré-visualizador**:
  - [ ] No pré-visualizador de página, verificar se widgets adicionados em formato de variável inline `[[widgets#...]]` ou `@[[widgets#...]]@` são devidamente interpretados e renderizados.
  - [ ] No Editor Visual (iframe), verificar se as variáveis de widget inline são convertidas em wrappers visuais operacionais de widget, possibilitando edição visual direta.
- [ ] **Prefixagem Dinâmica de URL Raiz para Campos de Imagem**:
  - [x] No `publisher-index`, verificar se campos customizados do tipo `'image'` têm a URL raiz (`$_GESTOR['url-raiz']`) adicionada de forma dinâmica. *(coberto por `testPrefixagemImagemUrlRaizEmCamposDeImagem` + `testPrefixarUrlRaizPreservaAbsolutasEPrefixaRelativas`)*
  - [ ] No `publisher-highlights`, verificar se campos customizados do tipo `'image'` têm a URL raiz (`$_GESTOR['url-raiz']`) adicionada de forma dinâmica. *(lógica idêntica ao publisher-index; sem teste PHPUnit dedicado — validar em runtime)*
  - [x] Validar que as consultas à tabela `publisher` utilizam cache estático em memória no PHP para otimização de performance. *(cache `static $cache` por `language|publisher_id` em ambas as helpers)*
- [ ] **Correção de Stubs no Vitest**:
  - [x] Validar que os stubs `children()` e `not()` em [jquery-stub.js](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/tests/Unit/JS/helpers/jquery-stub.js) corrigem a quebra nos testes unitários do widget.
  - [x] Executar `npm run test` e verificar se toda a suíte de testes JS passa sem erros.


### Evidência de Validação (BATCH-043)

Evidência automatizada registrada em 2026-06-15:

- Linting estático:
  - `php -l gestor/modulos/publisher-index/publisher-index.widget.php` → `No syntax errors detected`.
  - `php -l tests/Integration/PublisherIndexWidgetTest.php` → `No syntax errors detected`.
  - `node --check` OK em `publisher-index.js`, `publisher-highlights.js`, `menus.js`, `galleries.js`, `gestor/assets/interface/html-editor-interface.js` e `gestor/assets/interface/html-editor.js`.
  - `JSON.parse` OK em `publisher-index.json` (1.2.0), `menus.json` (1.0.3), `galleries.json` (1.0.2) e `publisher-highlights.json` (1.2.1).
- Testes automatizados (host com `mbstring`+`mysqli`, MySQL Docker em `127.0.0.1:3306`):
  - PHPUnit completo com `CONN2FLOW_RUN_DB_TESTS=1 CONN2FLOW_DB_DATABASE=conn2flow_test` → **40 tests, 132 assertions, 0 falhas** (1 deprecation pré-existente). Inclui `testCuradoriaManualRespeitaOrdemBuscaEPaginacao` (ordem exata da curadoria ignorando ORDER BY; IDs sem join/inativos descartados; `array_slice` de paginação; busca filtrada em PHP; contagem `count(selected_items)` sem busca e filtrada com busca; `selected_items` vazio → `[]`/`0`), `testItemCasaBuscaComparaTituloECamposCustom` (casa título/campos custom case-insensitive; ignora id/URL/data) e — para a §6 — `testPrefixarUrlRaizPreservaAbsolutasEPrefixaRelativas` (puro: relativo→raiz sem barra dupla; http/https/`//`/`data:` preservados) e `testPrefixagemImagemUrlRaizEmCamposDeImagem` (integrado: campo `image` relativo recebe `/base/`; campo `text` `resumo` intacto; campo `image` absoluto preservado).
  - Vitest (`npm run test`) → **3 tests, 2 files, 0 falhas** (após adicionar `.children()`/`.not()` ao stub jQuery, que estavam ausentes e quebravam `publisher-index.widget.test.js` no baseline).
- Cobertura de regressão: o teste integrado reutiliza o seed/`tearDown` de `conn2flow_test` (cria/dropa apenas `paginas`+`publisher_pages`+`publisher`), sem tocar o banco real. A tabela `publisher` (com `noticias` → `resumo`=text, `imagem`=image) foi adicionada ao seed porque `montar_itens` passou a consultá-la (§6).

Itens marcados acima refletem o que é verificável estaticamente/por teste automatizado; os itens de runtime em navegador (clipboard real, render do preview, edição visual, exibição da imagem destaque) seguem pendentes com o operador após `🗃️ Projects - Update => Core`.

- [ ] Linting estático limpo (`node --check` / `php -l`).
- [ ] Testes automatizados executados com sucesso via PHPUnit (`composer test`).
- [ ] Testes manuais no navegador validados pelo operador.

---
## BATCH-044 - Correção de Caracteres Especiais nos Widgets, Suporte AJAX no Preview e Refatoração de Módulos (req-044)

- [x] **Mapeamento Baseado em IDs (widgetsMap)**:
  - [x] Validar que wrappers de widgets (`div.conn2flow-widget-wrapper`) usam atributos limpos e alfanuméricos (`data-widget-id`/`data-widget-type`/`data-widget-slug`/`data-widget-variable`), evitando caracteres especiais do PHP/JSON no DOM do iframe (atributo `data-widget-signature` removido).
  - [x] Verificar se as assinaturas originais contendo `->`, `"`, `{`, `}` são mantidas em um mapa na memória do editor (`this.widgetsMap`, chaveado por `data-widget-id`).
  - [x] Confirmar que a edição/clonagem gera um **novo id exclusivo** copiando os metadados (`editWidgetWrapper`), evitando conflito entre clones.
  - [x] Validar que ao salvar/voltar, os widgets que vieram de variáveis (`isVariable = true`) voltam exatamente ao formato `[[widgets#...]]` (via token, sem re-escape) e os que eram comentários voltam como comentários, ambos sem caracteres corrompidos.
- [x] **Diferenciação e Unescape**:
  - [x] Variáveis (`widgets-var#`) e comentários (`widgets#`) diferenciados na carga; unescape das entidades HTML (incl. duplo escape `&amp;gt;`) antes de registrar/enviar ao backend (helper `htmlUnescape`/`unescapeEntities` via `<textarea>`).
- [x] **Inclusão Automática de widget.js no Preview**:
  - [x] `previewHtmlConteudo` extrai as assinaturas (comentários + variáveis inline) e injeta `<script src="{raiz}{modulo}/widget.js">` no `<head>` para os módulos com controlador (`galleries`/`publisher-index`/`menus`).
  - [x] Múltiplos widgets do mesmo módulo geram o script exatamente uma vez (desduplicado por módulo).
- [x] **Suporte a AJAX de Widgets no Preview**:
  - [x] `previewHtmlConteudo` injeta no `<head>` `window.gestor = Object.assign({}, window.parent.gestor); window.gestor.widgetsToAjax = "SIG1<#;>SIG2…"` (assinaturas únicas). Contrato confirmado em `widgets.php`: cada item de `widgetsToAjax` é a assinatura completa repassada a `widgets_get` como `$id`.
- [x] **Refatoração para html-editor-modules.js**:
  - [x] Novo `gestor/assets/interface/html-editor-modules.js` contém as 26 funções de simulação `menus`/`galleries`/`publisher` (+ `MENUS_SIM_FALLBACK`/`GALLERIES_SIM_FALLBACK` + `publisher_table_tr_skeleton`), anexadas ao `window`.
  - [x] `html-editor-interface.js` expõe no `window` `CodeMirrorHtml`/`CodeMirrorHtmlExtraHead`/`publisher_fields_schema` + auxiliares `frameworkCSS`/`previewHtml`/`regexVariaveisGlobal`/`alvoUsaItemVars`.
  - [x] `html-editor.php` inclui `html-editor-modules` **antes** de `html-editor-interface`.

### Evidência de Validação (BATCH-044)

- Validação estática executada em 2026-06-16:
  - `node --check` OK em `html-editor.js`, `html-editor-interface.js`, `html-editor-modules.js` (novo), `html-editor-visual-controls.js`, `html-editor-helper.js`.
  - `php -l gestor/bibliotecas/html-editor.php` → `No syntax errors detected`.
  - `npm run test` (Vitest) → **3/3** (baseline preservado; confirma ausência de regressão nos stubs).
  - `composer test` (PHPUnit) → **40/40 (112 asserts, 4 skipped** de banco gated por `CONN2FLOW_RUN_DB_TESTS`**)**.
- Arquivos alterados:
  - `gestor/assets/interface/html-editor.js` (Slice 1: `widgetCounter`/`widgetsMap`, `nextWidgetId`/`htmlUnescape`/`getWidgetSignature`; `createWrapperEl`/`editWidgetWrapper`/`requestWidgetRender`/`convertWidgetCommentsToWrappers`/`extractUserHtml` reescritos; `data-widget-signature` deixou de ser persistido).
  - `gestor/assets/interface/html-editor-interface.js` (Slices 2/3/4: `unescapeEntities` no `widgetPreviewBootstrap`; `extrairAssinaturasWidgets`/`montarWidgetAssetsHead`; injeção de `widgetAssetsHead` nos dois caminhos; Slice 5: exposições no `window` + remoção das 26 funções de simulação).
  - `gestor/bibliotecas/html-editor.php` (Slice 5: inclusão de `html-editor-modules` antes do interface).
- Arquivo criado:
  - `gestor/assets/interface/html-editor-modules.js` (Slice 5: 26 funções de simulação + constantes/estado, anexadas ao `window`).
- Decisão registrada: [DEC-059](../decisions/DECISION-LOG.md#dec-059---2026-06-16---accepted).
- Testes manuais/runtime pendentes com o operador (após `🗃️ Projects - Update => Core`):
  - Inserir/editar widget cuja assinatura contenha `->` e aspas; salvar e voltar ao editor de código confirmando que a variável `[[widgets#...]]` (ou o comentário) volta **sem** corrupção (`&gt;`/`&amp;gt;`/`&quot;`).
  - Página com `galleries`/`publisher-index`/`menus`: confirmar `<script .../widget.js>` no `<head>` do preview (uma vez por módulo) e `window.gestor.widgetsToAjax` preenchido; interagir com busca/paginação do `publisher-index` sem erro 500.
  - Confirmar que as abas "Simular"/"Variáveis" de `menus`/`galleries`/`publisher-*` seguem funcionando com as funções servidas por `html-editor-modules.js`.

---
## BATCH-045 - Correção de Erros de Inicialização (Temporal Dead Zone — TDZ) no Editor HTML Visual (req-045)

- [x] **Reorganização da ordem de inicialização** (`html-editor-interface.js`):
  - [x] 1ª iteração (insuficiente): removida a chamada síncrona de `contentPageTabHandler()` do meio do `$(document).ready` e movida para o fim. O erro **persistiu** em runtime porque o gatilho principal era outro.
  - [x] 2ª iteração (correção real): o `onLoad` que o Fomantic dispara SÍNCRONAMENTE ao inicializar `$('.menuContainerPagina .item').tab({...})` (linha ~751) também chama `previewHtml()`/`pageModificationContainerMove()`. Esse bloco de init do `.tab()` foi movido para o fim do `ready`, junto ao `contentPageTabHandler()` (handler antes da init, ordem original preservada), garantindo que `WIDGET_SCRIPT_MODULES` (`const`) e `total_sessoes` (`let`) já estejam fora da TDZ.
  - [x] Confirmado que o outro tab (`.menuPaginas`, ~L688) **não** precisa mover (seu `onLoad` só faz `CodeMirror*.refresh()`), e que as inits intermediárias (dropdowns `.frameworkCSS`/`.publisher-design-mode-*`) apenas registram callbacks `onChange` (não disparam na carga).
- [x] **Overlays do editor sobrevivem a widget-variável** (`html-editor.js`):
  - [x] `convertWidgetCommentsToWrappers()` não reescreve mais `document.body.innerHTML` para converter `[[widgets#...]]` (isso destruía os overlays/toolbar anexados ao body, quebrando a seleção quando havia widget em formato de variável). Nova `convertWidgetVariablesToComments()` faz a conversão cirurgicamente via TreeWalker `SHOW_TEXT` (pulando a UI `html-editor-*`) + `replaceChild` de fragmento, preservando a UI.

### Evidência de Validação (BATCH-045)

- Diagnóstico confirmado pelo runtime: o stack trace do console (v=1.3.1) tem todos os frames em `html-editor-interface.js` (`onLoad` L756 → `previewHtml` → `previewHtmlConteudo` → `montarWidgetAssetsHead` → `WIDGET_SCRIPT_MODULES`), originando na init `$('...').tab({...})` (L751) — **não** no `html-editor-modules.js`.
- Investigação de efeito colateral da refatoração (Slice 5 do BATCH-044) — **descartado**: grep em `html-editor-modules.js` não encontra `WIDGET_SCRIPT_MODULES`/`montarWidgetAssetsHead`/`total_sessoes`; a única chamada relacionada é `previewHtml()` dentro de `publisherValuesUpdate()` (runtime, não na carga); o arquivo não tem execução top-level além de declarações + bloco `window.X = X`.
- Validação estática executada em 2026-06-16:
  - `node --check gestor/assets/interface/html-editor-interface.js` → OK (após mover a init do `.tab()`).
  - Verificação por grep: `contentPageTabHandler()` e a init `$('.menuContainerPagina .item').tab({...})` são as únicas execuções síncronas que cascateiam em `previewHtml`/`pageModificationContainerMove`, e ambas estão agora no fim do `ready`.
- Arquivos alterados:
  - `gestor/assets/interface/html-editor-interface.js` (remoção da chamada síncrona de `contentPageTabHandler()` e do bloco de init do `.tab()` do meio do arquivo; ambos recolocados no fim do `ready`).

- [x] **Reorganização da ordem de inicialização** (`html-editor-interface.js`):
  - [x] 1ª iteração (insuficiente): removida a chamada síncrona de `contentPageTabHandler()` do meio do `$(document).ready` e movida para o fim. O erro **persistiu** em runtime porque o gatilho principal era outro.
  - [x] 2ª iteração (correção real): o `onLoad` que o Fomantic dispara SÍNCRONAMENTE ao inicializar `$('.menuContainerPagina .item').tab({...})` (linha ~751) também chama `previewHtml()`/`pageModificationContainerMove()`. Esse bloco de init do `.tab()` foi movido para o fim do `ready`, junto ao `contentPageTabHandler()` (handler antes da init, ordem original preservada), garantindo que `WIDGET_SCRIPT_MODULES` (`const`) e `total_sessoes` (`let`) já estejam fora da TDZ.
  - [x] Confirmado que o outro tab (`.menuPaginas`, ~L688) **não** precisa mover (seu `onLoad` só faz `CodeMirror*.refresh()`), e que as inits intermediárias (dropdowns `.frameworkCSS`/`.publisher-design-mode-*`) apenas registram callbacks `onChange` (não disparam na carga).
- [x] **Overlays do editor sobrevivem a widget-variável** (`html-editor.js`):
  - [x] `convertWidgetCommentsToWrappers()` não reescreve mais `document.body.innerHTML` para converter `[[widgets#...]]` (isso destruía os overlays/toolbar anexados ao body, quebrando a seleção quando havia widget em formato de variável). Nova `convertWidgetVariablesToComments()` faz a conversão cirurgicamente via TreeWalker `SHOW_TEXT` (pulando a UI `html-editor-*`) + `replaceChild` de fragmento, preservando a UI.

### Evidência de Validação (BATCH-045)

- Diagnóstico confirmado pelo runtime: o stack trace do console (v=1.3.1) tem todos os frames em `html-editor-interface.js` (`onLoad` L756 → `previewHtml` → `previewHtmlConteudo` → `montarWidgetAssetsHead` → `WIDGET_SCRIPT_MODULES`), originando na init `$('...').tab({...})` (L751) — **não** no `html-editor-modules.js`.
- Investigação de efeito colateral da refatoração (Slice 5 do BATCH-044) — **descartado**: grep em `html-editor-modules.js` não encontra `WIDGET_SCRIPT_MODULES`/`montarWidgetAssetsHead`/`total_sessoes`; a única chamada relacionada é `previewHtml()` dentro de `publisherValuesUpdate()` (runtime, não na carga); o arquivo não tem execução top-level além de declarações + bloco `window.X = X`.
- Validação estática executada em 2026-06-16:
  - `node --check gestor/assets/interface/html-editor-interface.js` → OK (após mover a init do `.tab()`).
  - Verificação por grep: `contentPageTabHandler()` e a init `$('.menuContainerPagina .item').tab({...})` são as únicas execuções síncronas que cascateiam em `previewHtml`/`pageModificationContainerMove`, e ambas estão agora no fim do `ready`.
- Arquivos alterados:
  - `gestor/assets/interface/html-editor-interface.js` (remoção da chamada síncrona de `contentPageTabHandler()` e do bloco de init do `.tab()` do meio do arquivo; ambos recolocados no fim do `ready`).
  - `gestor/assets/interface/html-editor.js` (correção do overlay: nova `convertWidgetVariablesToComments()` cirúrgica; `convertWidgetCommentsToWrappers()` não reescreve mais `document.body.innerHTML`).
  - `gestor/bibliotecas/html-editor.php` (`versao` da biblioteca `html-editor` bumpada pelo operador para cache-bust dos assets — 1.3.x no working tree; não alterada pelo executor).
- Validação estática adicional: `node --check gestor/assets/interface/html-editor.js` → OK; Vitest 3/3 (baseline preservado).
- Decisão registrada: [DEC-060](../decisions/DECISION-LOG.md#dec-060---2026-06-16---accepted).
- Testes manuais/runtime pendentes com o operador:
  - Carregar o Editor HTML Visual, abrir o console (F12) e confirmar ausência de `ReferenceError` ("Cannot access 'WIDGET_SCRIPT_MODULES'/'total_sessoes' before initialization").
  - Confirmar que a troca de abas e a pré-visualização de widgets continuam funcionando (inclusive abrindo direto na aba `visualizacao-pagina`).
  - Abrir o `editorHtmlVisual` numa página que contenha um widget em **formato de variável** (ex.: `[[widgets#menus->render({"grupo_slug": "teste"})]]`) e confirmar que o overlay de seleção de elementos funciona (antes ficava inerte); comparar com o mesmo widget em formato de comentário (deve seguir funcionando).

---
## BATCH-049 - Refatoração Dinâmica do Módulo Forms com html-editor (req-049)

- [x] Migração criada para `forms.html`, `forms.css`, `forms.css_compiled` e `forms.html_extra_head`.
- [x] `forms` integrado ao `html_editor_componente`, ao popup de widgets do editor visual e ao mapa de scripts públicos do preview.
- [x] CRUD administrativo de Forms substitui o JSON manual por abas de metadados, e-mail, redirects, campos e template/editor.
- [x] Widget público `forms.widget.php` e script `forms.widget.js` criados.
- [x] Alvo/modo de IA `forms` registrado no manifesto do módulo e nos data files atuais.

### Evidência de Validação (BATCH-049)

- `php -l gestor/modulos/forms/forms.php` -> OK.
- `php -l gestor/modulos/forms/forms.widget.php` -> OK.
- `php -l gestor/db/migrations/20260706110000_add_html_and_css_to_forms_table.php` -> OK.
- `php -l gestor/bibliotecas/html-editor.php` -> OK.
- `node --check gestor/modulos/forms/forms.js` -> OK.
- `node --check gestor/modulos/forms/forms.widget.js` -> OK.
- `node --check gestor/assets/interface/html-editor-interface.js` -> OK.
- `node --check gestor/assets/interface/html-editor-modules.js` -> OK.
- JSON parse OK em `forms.json`, `AlvosIaData.json` e `ModosIaData.json`.
- Checagem direta de `forms_widget_render_inline()` -> OK para input/select/options.
- `composer test` -> OK (`40 tests`, `112 assertions`, `4 skipped`, `1 deprecation`).
- `npm run test` -> OK (`2 files`, `3 tests`) após reexecução fora do sandbox; a primeira tentativa falhou por permissão do esbuild ao carregar `vitest.config.js`.

### Pendências Runtime

- Executar `Update => Core` / pipeline de recursos para registrar templates, ai modes e checksums calculados.
- Aplicar a migração Phinx no ambiente alvo.
- Validar no navegador a edição visual, preview, cópia da variável `[[widgets#forms->render(...)]]` e renderização pública do formulário.

---
## BATCH-050 - Escape HTML, Correção de Abas Aninhadas e Novos Modelos no Forms (req-050)

- [x] Parâmetro `html_specialchars` aceito em `html_editor_componente()` e ativo no backend ao renderizar `#pagina-html#` e `#pagina-html-extra-head#`.
- [x] O backend de Forms em `forms.php` envia os parâmetros escapados para o editor e preserva o HTML bruto no fluxo de gravação/submissão.
- [x] Inicialização das abas no `forms.js` usa `context: 'parent'` e `context: '[data-tab="forms-template"]'` para isolamento.
- [x] Criados 4 novos modelos físicos de templates em português e 4 em inglês baseados em Tailwind CSS.
- [x] Novos modelos registrados em `forms.json` e atualizados pelo compilador de recursos (`atualizacao-dados-recursos.php`).

### Evidência de Validação (BATCH-050)

- `php -l gestor/bibliotecas/html-editor.php` -> OK.
- `php -l gestor/modulos/forms/forms.php` -> OK.
- `node --check gestor/modulos/forms/forms.js` -> OK.
- `node -e` para validar `forms.json` e os 8 registros de template -> OK.
- `node -e` para validar marcadores `<!-- item < -->`, `<!-- item > -->`, blocos `type-*` e variáveis nos 8 arquivos HTML -> OK.
- `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` -> OK, 2222 recursos e nenhum problema detectado.
- `composer test` -> OK, 40 testes, 112 assertions, 4 skipped, 1 deprecation.
- `npm run test` -> falhou no sandbox com `Access is denied` ao resolver `vitest.config.js`; reexecutado com permissão escalada -> OK, 2 arquivos e 3 testes.
- `git diff --check` -> permanece com aviso em `sdd/human-requests/CURRENT.md:8` (linha em branco no EOF, arquivo de intake não alterado pelo executor).

### Pendências Runtime

- Carregar o formulário contendo `<textarea>` e confirmar que abre sem corromper o CodeMirror.
- Clicar na aba "Widget" da tela de template e testar se os demais menus de abas permanecem funcionando.
- Validar se os 4 novos modelos Tailwind (pt-br/en) estão disponíveis e funcionam no preview e publicação.

---
## BATCH-051 - Escape HTML Global no Editor e Divisão de Opções nos Selects (req-051)

- [x] `html_editor_componente()` realiza escape de tags automaticamente se `html` ou `html_extra_head` forem fornecidos no array `$params` (comportamento global por padrão).
- [x] A chamada de `html_editor_componente()` no Forms (`forms.php`) foi limpa removendo `'html_specialchars' => true`.
- [x] A função `forms_widget_options_html()` em `forms.widget.php` suporta a divisão de valor/rótulo pelas sintaxes `:` e `|`.
- [x] `forms.widget.php` renderiza opções de `radio` e `checkbox` como inputs dentro de labels, usando `name[]` para checkbox.
- [x] `forms.js` adiciona `radio` e `checkbox` no dropdown de tipos e mostra `.forms-field-options` apenas para `select`, `radio` e `checkbox`.
- [x] Os 5 templates físicos em `pt-br` e `en` receberam blocos `type-radio` e `type-checkbox`.
- [x] Compilador de recursos executado após alteração dos templates.

### Evidência de Validação (BATCH-051)

- `php -l gestor/bibliotecas/html-editor.php` -> OK.
- `php -l gestor/modulos/forms/forms.php` -> OK.
- `php -l gestor/modulos/forms/forms.widget.php` -> OK.
- `node --check gestor/modulos/forms/forms.js` -> OK.
- Teste PHP via stdin para `forms_widget_options_html()` com select, radio e checkbox -> OK.
- Validação Node dos marcadores `type-radio`/`type-checkbox` nos 10 templates -> OK.
- `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` -> OK, 2222 recursos e nenhum problema detectado.
- `composer test` -> OK, 40 testes, 112 assertions, 4 skipped, 1 deprecation.
- `npm run test` -> falhou no sandbox com `Access is denied` ao resolver `vitest.config.js`; reexecutado com permissão escalada -> OK, 2 arquivos e 3 testes.
- `git diff --check` -> permanece com aviso em `sdd/human-requests/CURRENT.md:8` (linha em branco no EOF, arquivo de intake não alterado pelo executor).

### Pendências Runtime

- Testar o CRUD de Forms, criar um campo `select` com opções no formato `sp:São Paulo` e `rj|Rio de Janeiro` e validar a renderização pública do `<select>` gerado (com values e labels diferentes).
- Testar campos `radio` e `checkbox` no CRUD, preview e submissão pública.
- Assegurar que os demais formulários carregam no editor sem quebra do CodeMirror.

---
## BATCH-052 - Disponibilidade do Menu com Visibilidade Condicional no Módulo Menus (req-052)

- [x] **Novo Schema de Dados e Retrocompatibilidade**:
  - [x] Schema suporta as chaves `availability`, `conditions` e `menus`.
  - [x] Retrocompatibilidade garante que se `availability` estiver ausente, trata como `todos` e popula a árvore a partir de `selected_items`.
- [x] **UI Administrativa (CRUD de Menus)**:
  - [x] Seção renomeada para "Disponibilidade do Menu" (PT/EN).
  - [x] Select com opções "Visível a Todos" e "Visibilidade Condicional" funcional.
  - [x] Modo "Visível a Todos" renderiza a árvore de itens padrão.
  - [x] Modo "Visibilidade Condicional" exibe painel CRUD de condições (público / logado / perfil_usuario), controle de slugs e abas para gerenciar cada menu de forma isolada.
- [x] **Widget Público e Resolução de Condições**:
  - [x] Widget integrado a `gestor_usuario()` para buscar dados do usuário.
  - [x] Condições `publico`, `logado` e `perfil_usuario` (resolvendo contra tabela `usuarios_perfis` do banco) avaliadas na ordem.
  - [x] Renderização dinâmica do bloco `<!-- menu-conditional-SLUG < -->` ou fallback para `<!-- menu-visible < -->` / HTML completo em caso de ausência.
- [x] **Templates Físicos e IA**:
  - [x] Templates nativos envelopados com os delimitadores `<!-- menu-visible < -->`.
  - [x] Instruções de IA PT/EN atualizadas com referências aos comentários de controle de visibilidade.
- [x] **Suíte de Testes Unitários**:
  - [x] Testes unitários novos implementados em `tests/Unit/PHP/MenusWidgetConditionalVisibilityTest.php` validando as regras de visibilidade.

### Evidência de Validação (BATCH-052)

Evidência automatizada reportada pelo executor em 2026-06-22:
- Linting estático:
  - `php -l gestor\modulos\menus\menus.php` → OK
  - `php -l gestor\modulos\menus\menus.widget.php` → OK
  - `node --check gestor\modulos\menus\menus.js` → OK
- Testes automatizados executados:
  - PHPUnit novo [MenusWidgetConditionalVisibilityTest.php](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/tests/Unit/PHP/MenusWidgetConditionalVisibilityTest.php): **4/4 testes OK**.
  - Suíte completa: **44 testes OK**.

### Pendências Runtime
- Rodar `Update => Core` (compilador de recursos) para sincronizar configurações e templates modificados.
- Testar manualmente no navegador o fluxo de adicionar condições no CRUD de Menus, alternar abas e verificar a persistência dos menus.
- Validar se o widget público alterna corretamente de acordo com o login e o perfil do usuário ativo.

---
## BATCH-053 - Ajustes e Melhorias na Disponibilidade Dinâmica de Menus Condicionais (req-053)

- [x] **Autocomplete de Perfis de Usuário**:
  - [x] Endpoint AJAX `profiles-search` implementado em `menus.php`.
  - [x] Autocomplete/tags na UI do admin para múltiplos perfis na condição `perfil_usuario` funcional.
  - [x] IDs de perfis selecionados persistidos no `fields_schema.conditions` do menu.
- [x] **Duplicação Automática de Blocos HTML no CodeMirror**:
  - [x] Ao adicionar uma nova condição, o JS do editor duplica cirurgicamente o bloco `menu-visible` no editor CodeMirror como `menu-conditional-SLUG`.
- [x] **Live Preview Sincronizado com Aba Ativa**:
  - [x] Alternar abas no construtor de menu passa o parâmetro `preview_slug` para o widget de pré-visualização.
  - [x] O widget interpreta `preview_slug` e renderiza apenas o respectivo bloco condicional HTML e árvore de itens.
- [x] **Resolução Dinâmica do Widget Público**:
  - [x] Widget público valida o perfil do usuário logado (`id_usuarios_perfis`) contra a lista `condition.profile_ids`.
  - [x] Mantém fallback de suporte a string/slug único de perfil para retrocompatibilidade.
- [x] **Suíte de Testes Unitários**:
  - [x] Testes em `tests/Unit/PHP/MenusWidgetConditionalVisibilityTest.php` atualizados cobrindo múltiplos perfis, perfis ausentes da lista e suporte a `preview_slug` (7/7).

### Evidência de Validação (BATCH-053)

Evidência automatizada reportada pelo executor em 2026-06-22:
- Linting estático:
  - `php -l gestor\modulos\menus\menus.php` → OK
  - `php -l gestor\modulos\menus\menus.widget.php` → OK
  - `node --check gestor\modulos\menus\menus.js` → OK
- Testes automatizados executados:
  - PHPUnit [MenusWidgetConditionalVisibilityTest.php](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/tests/Unit/PHP/MenusWidgetConditionalVisibilityTest.php): **7/7 testes OK**.
  - Suíte completa: **47 testes OK**.

### Pendências Runtime
- Rodar `Update => Core` para compilar os recursos e carregar as rotas no sistema.
- Validar visualmente no navegador a adição de condições do tipo `perfil_usuario`, selecionando múltiplos perfis no autocomplete e testando se as tags são geradas e podem ser removidas pelo `x`.
- Confirmar no Live Preview que ao clicar nas abas de cada condição, o menu exibido no iframe de preview se altera dinamicamente.
- Testar a renderização pública do menu com diferentes usuários e perfis para validar o redirecionamento/visibilidade de cada menu.

---
## BATCH-054 - Ajustes Visuais de Margem e Tooltip com Detalhe de Perfis no Módulo Menus (req-054)

- [x] **Ajuste de Margem nas Tags**:
  - [x] Margem aplicada às tags de perfis selecionados (`#condition-profile-tags` / `.condition-profile-tag`) para garantir espaçamento adequado em relação aos botões do formulário.
- [x] **Abas de Condição com Contador e Tooltip**:
  - [x] Abas de condição `perfil_usuario` exibem contador `slug (Perfil de usuário - N)`.
  - [x] Abas recebem tooltip Fomantic-UI formatado (`Usuário Perfis: Perfil 1, Perfil 2...`) com os nomes legíveis dos perfis.
  - [x] Fallback automático para os IDs se os nomes/labels dos perfis não estiverem salvos.

### Evidência de Validação (BATCH-054)

Evidência automatizada reportada pelo executor em 2026-06-22:
- Linting estático:
  - `php -l gestor\modulos\menus\menus.php` → OK
  - `php -l gestor\modulos\menus\menus.widget.php` → OK
  - `node --check gestor\modulos\menus\menus.js` → OK
- Verificação de diff: `git diff --check` passou limpo nos arquivos do lote.

### Pendências Runtime
- Sincronizar os recursos via `Update => Core`.
- Validar visualmente no navegador que as tags do CRUD possuem espaçamento inferior e não encostam no botão de confirmação.
- Validar que ao passar o mouse nas abas das condições, o tooltip do Fomantic-UI renderiza o nome legível dos perfis vinculados e o título exibe o número de itens selecionados.


## BATCH-056 - Sincronização Declarativa de Recursos, Deleção e Atualização Forçada (req-056)

- [x] **Gerador (`atualizacao-dados-recursos.php`)**:
  - [x] `normalizarConfigTabela()` aceita `config` objeto OU array de objetos (objeto → array de 1); resolve `tabela_nome`; agrega `deletar`/`forcar_atualizacao` por elemento e por bloco (retrocompat).
  - [x] `coletarConfigsTabelas()` (motor compartilhado) reaproveitado por `gerarSchemaMetadata()` e `coletarRecursos()`.
  - [x] `gerarSchemaMetadata()` consolida `deletar` e novo `forcar_atualizacao` (mapas de topo) no `schema-metadata.json`; campos de varredura ficam fora do contrato.
  - [x] Varredura `sync_resources`: `lerMetadadosDinamicos()` (externo/inline por idioma), `processarRegistroDinamico()` (`field_types` `json`/`file:<ext>`, BOM removido, colunas padronizadas), `checksumRegistroDinamico()` (reuso de versão).
  - [x] `atualizarDados()` gera `<PascalCase>Data.json` dinâmico via `dataFileNameFromTable()`, pulando as 9 tabelas fixas reservadas.
- [x] **Config global (`tables_config.json`)**: `_comment` estendido (config objeto/array, `tabela_nome`, `sync_resources`/`resources_dir`/`metadata_file`/`field_types`/`deletar`/`forcar_atualizacao`); 4 tabelas existentes mantidas (retrocompat).
- [x] **Atualizador (`atualizacoes-banco-de-dados.php`)**: `schemaMetadata()` lê `forcar_atualizacao`; `forcarAtualizacaoLista()` + `$isForced()`; bypass de `project`/`user_modified` + reset `user_modified=0` + preservação de `project` nos 3 caminhos (PK, chave natural, fallback). Correção `${var}`→`{$var}` (deprecation PHP 8.4).
- [x] **Documentação**: 6 docs × pt-br/en atualizados (Recursos, Atualizações, Módulos Detalhado, Módulos Overview, Multilíngue, Proteção de Banco).

### Evidência de Validação (BATCH-056)

Evidência automatizada reportada pelo executor em 2026-06-23 (ambiente: PHP 8.4.8, mbstring/pdo_sqlite/pdo_mysql):
- `php -l gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` → OK
- `php -l gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php` → OK
- `json_decode` de `gestor/resources/tables_config.json` → VALID
- Regeneração do contrato via `SDD_NO_AUTORUN` + `gerarSchemaMetadata()` → 17 tabelas preservadas, chave de topo `forcar_atualizacao` presente (vazia, pois nada declara ainda).
- Smoke da varredura dinâmica (fixtures temporárias): `file:html`/`file:css` com BOM removido, `field_types: json` codificado, injeção de `language`/`status`/`module`, checksum estável e sensível a mudança de arquivo, leitura inline e externa → OK.
- Teste de regressão `tests/Unit/PHP/ForcarAtualizacaoTest.php` (PDO SQLite + contrato temporário): forçado por chave natural (payload completo + `user_modified→0`, `project` preservado), forçado por PK (bypass de `project`), preservação normal de `user_modified=1`, proteção de projeto → `updated=2`, `same=2`, todas as asserts PASS.
- `composer test` (suíte completa) → **48 testes, 142 assertions, OK** (4 skipped = testes de banco gated; 1 PHPUnit deprecation pré-existente, não relacionada).

### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` (regenera `schema-metadata.json` + `<Pascal>Data.json` dinâmicos e recalcula checksums) e validar a sincronização em MySQL.
- Validação manual end-to-end do `forcar_atualizacao` em MySQL: configurar uma tabela com registro em `forcar_atualizacao`, marcar `user_modified=1` no banco, rodar o deploy e confirmar que o dado físico foi reintegrado e `user_modified` voltou a `0`.

---
## BATCH-057 - Correção de Tipagem e Validação de Perfil Anônimo em Menus Condicionais (req-057)

- [x] Normalização de perfil anônimo em array (converter para `false` se `id` for `'_anonimo'` ou `id_usuarios` for `0`).
- [x] Obtenção de profile ID sem warnings (ler `$params['_profile_slug']` ou `id_usuarios_perfis` quando `$perfil_usuario` for array).
- [x] Validação estática (`php -l`) no arquivo `gestor/modulos/menus/menus.widget.php` sem erros.
- [x] Suíte de testes `tests/Unit/PHP/MenusWidgetConditionalVisibilityTest.php` executada sem falhas ou warnings.

### Evidência de Validação (BATCH-057)

- Validação estática e de testes executada em 2026-06-24:
  - `php -l gestor/modulos/menus/menus.widget.php` → `No syntax errors detected`.
  - **Baseline (antes da correção)**: `MenusWidgetConditionalVisibilityTest` → 2 falhas (`testRenderizaMenuPublicoParaUsuarioAnonimo`, `testRenderizaMenuDePerfilQuandoIdEstaEntreMultiplosPerfisPermitidos`) + 1 PHP warning (`Array to string conversion` em `menus.widget.php:288`).
  - **Pós-correção**: `vendor/bin/phpunit tests/Unit/PHP/MenusWidgetConditionalVisibilityTest.php` → **OK (7 tests, 19 assertions)**, sem falhas e sem warnings.
  - Suíte PHP completa (`composer test`) → **OK (48 tests, 142 assertions, 4 skipped gated por banco)**, sem novas falhas (a única `PHPUnit Deprecation` é pré-existente e alheia a este slice).
- Arquivo alterado: `gestor/modulos/menus/menus.widget.php` (apenas a função `menus_widget_condicao_valida`).
- Decisão registrada: [DEC-065](../decisions/DECISION-LOG.md#dec-065---2026-06-24---accepted).
- Observação de escopo: o intake (req-057) cita "9 testes", mas a suíte real `MenusWidgetConditionalVisibilityTest` contém **7** testes — todos passam. Nenhum teste novo foi criado, pois o req descreve apenas a correção do widget; a divergência de contagem foi registrada para rastreabilidade.
- Restrição respeitada: nenhum `git commit`/`git push` executado.




