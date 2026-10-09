# BATCH-227: painel em Tailwind — biblioteca de controles, diálogos, editor HTML e `admin-paginas` (req-219)

Execução das fatias 1 a 4 da [req-219](../../human-requests/archive/req-219.md). A fatia 5 (listagem, BL-026) segue na [req-220](../../human-requests/archive/req-220.md) / BATCH-228 com o agente paralelo; a fatia 6 (demais módulos) fica para lotes por grupo.

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## Commits

| Repositório | Commit | Conteúdo |
|---|---|---|
| core | `3b10355b` + `5fd924d0` | Fatias 1 e 2: biblioteca de controles e diálogos no lugar dos nativos |
| core | `7cf44d27` + `435fd83c` | Fatias 3 e 4: editor HTML e páginas de edição do `admin-paginas` em Tailwind |
| conn2flow-site | `639993ff` | Roteiro de navegador `sdd/validation/core/req219-controles-e2e.cjs` e evidências |

## O que mudou

### Fatias 1 e 2 — biblioteca de controles e diálogos

| Onde | Mudança |
|---|---|
| `gestor/assets/interface/controles.js` / `.css` | Biblioteca sem framework (`window.c2fControles`): classe `Controle` com `estender`/`registrar`/`criar`/`de`, eventos `c2f:<evento>`; select (busca sem acento, múltiplo com fichas, AJAX `ajax=sim&ajaxOpcao&q`), chave, abas; diálogos `alerta`/`confirmar`/`perguntar` com Promise e foco preso; avisos; carregando; dica. Classes `c2fc-*` próprias, porque classe gerada por JS ou PHP não passa pela compilação do Tailwind. |
| ponte Fomantic (no mesmo arquivo) | Só entra sem o Fomantic na página: `$.fn.dropdown`, `checkbox`, `tab`, `modal`, `dimmer`, `popup`, `search`, `transition` e `form` com a API que o core usa. |
| `gestor/bibliotecas/controles.php` | `controles_campo`, `controles_select`, `controles_chave`, `controles_abas`, `controles_botao`; incluída pelo `interface`. |
| editor HTML, `perfil-usuario` | `alert`/`confirm`/`prompt` nativos trocados pelos diálogos do painel. |

### Fatias 3 e 4 — editor HTML e `admin-paginas`

| Onde | Mudança |
|---|---|
| `gestor/resources/{pt-br,en}/components/*-tailwind` | Variantes de `html-editor`, `html-editor-modal`, `html-editor-visual-modal`, `html-editor-page-modification`, `html-editor-seo`, `html-editor-modelos`, `ia-prompt`, `ia-prompt-modais`, `ia-sem-servidor`, `interface-iframe-modal` e `widget-imagem`. Mantêm marcadores, ids, nomes, `data-tab` e as classes-gancho do JS legado; as classes `ui …` que sobram não têm CSS na página. |
| `html-editor.php`, `ia.php`, `interface.php`, `paginas-layouts-perfis.php` | Escolha da variante por `interface_componente_variante()`. Em modo Tailwind o `select` do `interface` sai com `data-c2f-select` e o modelo da IA vira `<option>`. |
| `admin-paginas` | `adicionar`/`editar`/`clonar` no `layout-administrativo-tailwind` com `tailwind_bundle` e `$_GESTOR['tailwind-page-bundle'] = true`; campos `c2fc`; componentes `layout-profile-*-tailwind`; selo de status em Tailwind; `admin-paginas.js` acha o invólucro das duas variantes. |
| ponte | Abas por grupo de irmãos, com `onLoad` (o editor tem abas aninhadas e atualiza o CodeMirror no `onLoad`); modal levado ao `body`; `.form()` mínimo (o `ia.js` parava sem ele); imagepick em JS puro, que era do `interface.js` legado. |
| `html-editor-interface.js` | Salvar sem `$.formSubmitNormal` (usa `requestSubmit`, que passa pela validação do `interface-tailwind.js`); seletores sem `.button` e `.ui.form`. |
| `controles.css` | Modal e dimmer Fomantic injetados por hooks só aparecem `.active`, o padrão do próprio Fomantic (achado: modal "Workspace Social" do conn2flow-site solto no rodapé). |

## Validação

- PHPUnit (Lab): 1.498 testes, 1 falha anterior e conhecida (`ProjectSshDeployReq034Test`, CRLF de script). Novos: `ControlesReq219Test` (4) e `HtmlEditorTailwindReq219Test` (contrato de cada variante contra a original nos dois idiomas, registro, metadados e HTML das páginas).
- Vitest: 474/474 (`controles.test.js` com abas aninhadas e `onLoad`).
- Navegador no Lab (`conn2flow-site-local`), `req219-controles-e2e.cjs`: **18/18**. Editor Fomantic (`admin-layouts`) sem mudança; `admin-paginas/editar` sem asset do Fomantic no documento do painel; abas aninhadas com CodeMirror; editor visual no modal; salvar sem 403; duas colunas a 1366 px; 390 px sem rolagem horizontal; `adicionar` mostra os campos de módulo; nenhum modal de hook solto; `perfil-usuario` com diálogo do painel.

## Limites e observações

- O iframe de pré-visualização carrega o Fomantic quando a página editada é Fomantic: é o framework da página, não do painel.
- O `layout-administrativo-tailwind` carrega a folha de ícones `fomantic-icon` de propósito (ícones estruturais do layout).
- Simulações do publisher, menus e galerias (`html-editor-*-simulation`, `publisher-controls`) não têm variante: só os alvos de `paginas` estão em Tailwind. Ficam para a fatia 6, com os módulos que as usam.
- O botão e o modal "Workspace Social" que o conn2flow-site injeta no `admin-paginas` são marcação Fomantic: funcionam pela ponte, mas sem estilo próprio. Variante no site, na fatia 6.
- Em 1 de 4 rodadas apareceu `Failed to construct 'URL'` sem pilha, provavelmente do iframe `srcdoc` da pré-visualização; não se repetiu em três rodadas seguidas.
