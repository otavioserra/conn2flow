# BATCH-156 — Integridade visual dos templates Tailwind no preview

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-154.md`
- **Data de abertura**: 2026-09-02
- **Modo**: supervisionado

## Escopo aprovado

1. Auditar os 72 templates do core em `pt-br` e `en`, seus HTMLs e sidecars
   `.precompiled.css`.
2. Comparar o conteúdo autoral, os `TemplatesData.json` e os registros de banco usados pelo
   preview do editor.
3. Corrigir a composição do iframe Tailwind de `admin-paginas/adicionar/`, preservando a cascata
   do framework e o baseline de páginas que recebem novas seções.
4. Criar guardas automatizadas para cobertura dos artefatos e para a composição do preview.
5. Recompilar/sincronizar pelos comandos oficiais e registrar as evidências de validação.

## Diagnóstico medido

- Os manifests têm **72 templates por idioma**: 36 Tailwind e 36 Fomantic.
- Os **72 templates Tailwind** (36 por idioma) têm sidecar não vazio; HTML, sidecar e
  `TemplatesData.json` estão sincronizados nos exemplos reportados.
- O banco do ambiente `conn2flow-site-local` também contém `css_precompiled` não vazio para os
  modelos afetados, inclusive `landing-page-alta-conversao` do projeto.
- Renderização isolada com HTML + sidecar preserva paddings, gaps, grids, fundos, sombras e CTAs.
- A divergência nasce na composição do iframe: depois do baseline Tailwind em camadas, o preview
  carrega `semantic.min.css` sem camada. Pela cascata CSS, regras não estratificadas do Fomantic
  vencem as utilities estratificadas do Tailwind.
- Ao inserir uma seção, `modeloSelecionar()` substitui o baseline completo da página pelo sidecar
  da seção, deixando o restante da página dependente da compilação assíncrona do browser.

## Live Todo List

- [x] Classificar intake e abrir o BATCH-156.
- [x] Auditar manifests, arquivos, sidecars, `TemplatesData.json` e banco do ambiente local.
- [x] Reproduzir os templates afetados em Chromium isolado e comparar com thumbnails.
- [x] Isolar o CSS de framework no preview Tailwind.
- [x] Preservar o baseline acumulado ao inserir seções.
- [x] Adicionar testes de regressão JS/PHP.
- [x] Executar recompilação/sincronização oficial.
- [x] Executar suítes e inspeção visual isolada A/B em Chromium.
- [x] Consolidar evidências no checklist e submeter para homologação.
- [ ] Homologar o fluxo autenticado em `admin-paginas/adicionar/` com o operador.

## Implementação

- `htmlEditorPreviewFrameworkIncludes()` não injeta `semantic.min.css` em previews Tailwind. Os
  scripts de jQuery/Fomantic foram preservados para compatibilidade com widgets legados; somente a
  folha visual concorrente foi isolada.
- `htmlEditorCssPrecompiledAtualizar()` mantém o baseline da página e concatena o sidecar quando o
  alvo da modificação é uma seção; substituições integrais continuam trocando o baseline.
- O derivado `html-editor-interface.min.js`, o manifesto de minificação e a versão determinística
  do owner `interface` foram atualizados pelo pipeline oficial.
- `TemplatesTailwindIntegrityTest` cobre os 72 registros por idioma, HTML e thumbnail de todos os
  modelos, 36 sidecars Tailwind por idioma e cada utility essencial usada de padding, margin, gap,
  spacing, background, rounded e shadow.
- `html-editor-template-preview.test.js` cobre isolamento de framework e acumulação/substituição do
  baseline.

## Evidências

- A/B em Chromium com `semantic.min.css` depois do sidecar: `py-20` = 70px, `gap-12` = 42px e CTA
  `bg-white` transparente. Sem a folha concorrente: 80px, 48px e `rgb(255,255,255)`.
- `resources:sync --force`: 237/237 recursos Tailwind recompilados, 0 erros. Segunda execução:
  237/237 em cache, 0 erros.
- `assets:minify --verificar`: 65 arquivos de autoria, 0 derivados desatualizados.
- PHPUnit focado: 2/2 testes, 2.695 asserções.
- Vitest completo: 27/27 arquivos, 382/382 testes.
- PHPUnit completo: 1.073/1.073 testes, 7.418 asserções, 4 skips esperados.
- Review findings-first: nenhum finding crítico/alto/médio no escopo após preservar os scripts
  legados no iframe; gates residuais abaixo.

## Gates residuais

- A rota real redireciona para login. A geração de uma sessão administrativa temporária via SSH foi
  recusada pelo gate de permissão; não houve contorno. A homologação autenticada permanece humana.
- A publicação opcional em `dist/` avisou que `PUBLIC_PATH` não está configurado. A sincronização de
  recursos concluiu com sucesso e o controlador `arquivo-estatico` permanece como fallback.

## Restrições

- Nenhum commit, push, release ou deploy sem autorização do Humano-no-Loop.
- Nenhuma cópia manual para mirrors; sincronização somente pelo pipeline oficial.
- Alterações preexistentes da árvore de trabalho devem ser preservadas.
