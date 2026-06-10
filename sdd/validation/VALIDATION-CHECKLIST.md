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

- [ ] **Resolução de Framework CSS do Template (`framework_css`)**:
  - [ ] Para evitar que o pré-visualizador (`live widget-preview`) falhe ao abrir um modelo `-modificado` devido à falta do framework CSS, o PHP deve selecionar o `framework_css` dos templates e disponibilizá-lo como um atributo de dados `data-framework` nos elementos `<option>` de templates.
  - [ ] O JavaScript deve ler este atributo no page load (inicialização) e em eventos `change` para inicializar a variável `gestor.html_editor.framework_css` de forma síncrona nos módulos:
    - [ ] `menus.js`
    - [ ] `galleries.js`
    - [ ] `publisher-highlights.js`
- [ ] **Extração de Variáveis em Destaques (Highlights)**:
  - [ ] No JavaScript de `publisher-highlights.js`, ao carregar/re-selecionar a variante `-modificado`, as variáveis `[[item#X]]` devem ser extraídas localmente do HTML do banco de dados (`initialHtml` ou cached HTML) usando expressão regular client-side para manter o painel de mapeamento de variáveis populado, chamando `renderItemVars()` e `syncEditorVariables()`.

### Evidência de Validação (BATCH-027)

- [ ] Validação estática de sintaxe executada:
  - [ ] `node --check gestor/modulos/menus/menus.js`
  - [ ] `node --check gestor/modulos/galleries/galleries.js`
  - [ ] `node --check gestor/modulos/publisher-highlights/publisher-highlights.js`
  - [ ] `php -l gestor/modulos/menus/menus.php`
  - [ ] `php -l gestor/modulos/galleries/galleries.php`
  - [ ] `php -l gestor/modulos/publisher-highlights/publisher-highlights.php`
- [ ] Testes manuais/runtime pendentes com o operador:
  - [ ] Confirmar que o pré-visualizador (`live widget-preview`) funciona perfeitamente logo no carregamento inicial da edição de um registro com modelo `-modificado` nos três módulos.
  - [ ] Mudar o modelo para o original e de volta para `-modificado`, conferindo que o previewer renderiza com o framework CSS correto (`gestor.html_editor.framework_css`).
  - [ ] Abrir um registro de Destaques em `-modificado` e verificar que a aba de mapeamento de variáveis de item é populada instantaneamente com as variáveis extraídas localmente via regex.
- [ ] Decisão registrada: [DEC-040](../decisions/DECISION-LOG.md#dec-040---2026-06-10---accepted)


