# BATCH-212: Layout por perfil 1:N, compilação multi-layout, editor multi-layout, popup da página inicial e versão pela API (req-204)

Parte do core da REQ-085 do `conn2flow-site` (lote irmão: BATCH-079 de lá). Intake: [req-204](../human-requests/req-204.md).

**Status**: `in-review` (implementado e validado no Lab pelo projeto `conn2flow-site-local`; revisão humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-req203`, branch `feat/req-204`.

> A numeração nasceu como req-203/BATCH-211 e foi trocada: enquanto o lote rodava, outra requisição foi gravada com esses números na árvore compartilhada.

## Desenho

| Peça | O quê |
|---|---|
| `gestor_layouts_perfis_mapa()` (`bibliotecas/gestor.php`) | leitura única de `paginas.layouts_users_profiles`, agora `{perfil: layout}`. Usada pelo roteador, pelo formulário e pelos dois compiladores |
| `paginas-layouts-perfis.php` | grava indexado por perfil; perfil repetido fica com a primeira linha |
| Migração `20261001150000_reindex_page_layout_mapping_by_profile` | inverte o registro em que todas as entradas têm cara do formato antigo (chave é layout, valor é perfil); o resto fica como está |
| `css-regenerar.php` (`css:rebuild`) | página com mapeamento compila com o HTML dela, do layout padrão e de cada alternativo (do banco), mais as dependências declaradas; entra no escopo mesmo sem `user_modified`; o conjunto de layouts faz parte da procedência |
| `tailwind-recursos.php` (build offline) | página cujo manifesto declara `layouts_users_profiles` recebe como fonte os layouts que existirem na árvore; layout só de projeto fica para o `css:rebuild` |
| Carimbo `/*! c2f-layouts:a,b */` | primeira linha do CSS derivado da página; `gestor_css_layouts_marcador()` e `gestor_css_layouts_cobertos()` |
| Roteador (`gestor.php`) | layout em uso está no carimbo: CSS da página sozinho (modo bundle). Layout trocado e fora do carimbo: mantém o sidecar do layout, em vez de descartá-lo |
| `HtmlEditorCssCapture` (`html-editor.js`) | folhas `style[data-c2f-baseline-alt]` são as cascatas dos layouts não visualizados; só fica fora do `css_compiled` o que **todas** entregam (regras, camada `base`, tokens de tema). `extract()` devolve também `layouts` |
| `html-editor-interface.js` | seletor "Visualizar como" no menu do editor; as cascatas alternativas entram com `media="not all"`; o salvamento refaz a captura quando o conjunto de layouts capturado difere do da página, esperando o iframe recarregar |
| `html-editor.php` | entrega o pré-compilado do recurso sozinho (`cssPrecompiledRecursoBase64`) e o rótulo do seletor |
| `home-page-autocomplete` + `usuarios-perfis.js` | `[hidden]{display:none!important}` no componente; o JS alterna só o atributo |
| `/_api/system/update`, `action=version` | devolve `versao` (`$_GESTOR['versao']`), autenticada |

## Validação

### Automatizada
- `Req204LayoutMultiPerfilTest` (6): dois perfis no mesmo layout, mapa tolerante, gravação indexada por perfil (processo isolado), carimbo estável, fontes e modo de compilação da página mapeada, uso do carimbo na regeneração e no roteador.
- `Req196LayoutPorPerfilTest` e o fixture de componentes ajustados ao formato novo.
- Vitest: 34 arquivos, 461 testes — 6 novos (três da captura cumulativa, três do preview por layout) e o da busca de página inicial estendido.
- PHPUnit no Lab, árvore principal: 1.361 testes, 1 falha de ambiente (`project-transport.sh` com CRLF lido pelo WSL). Na worktree, mais quatro falhas da mesma natureza (`Stripe*Test` e um de `CssRegeneracaoTest`: regex só-LF sobre checkout CRLF); os 125 testes ligados ao lote passam.

### Lab (`conn2flow-site-local`)
- Migração aplicada: `{"layout-portal-cliente":"cloud-starter"}` virou `{"cloud-starter":"layout-portal-cliente"}`.
- Pela tela do `admin-paginas`, a página `perfil-usuario` recebeu `cloud-nano` e `cloud-pro` no mesmo layout; os três pares foram gravados e relidos.
- `css:rebuild`: `perfil-usuario` regenerada com o carimbo `layout-administrativo-tailwind,layout-portal-cliente`.
- `/perfil-usuario/` sob o portal (cliente Cloud Nano) e sob o administrativo (admin), a 1280 e 390 px: só a folha `page-precompiled`, nenhuma classe sem regra, sem rolagem horizontal.
- Editor: seletor com os dois layouts, troca de preview sem mexer no layout padrão, salvamento com `css_compiled` de 5.091 bytes.
- `usuarios-perfis`: caixa fechada ao abrir, aberta sob o campo ao digitar, fechada ao clicar fora e ao escolher.

## Achados
- **Página bundle sem mapeamento, editada online**: o `css:rebuild` compila só utilities para ela, e o runtime (modo bundle) descarta o layout. Anterior ao lote; não corrigido.
- **Tamanho**: a compilação multi-layout sai com 266 KB, porque o `input.css` do projeto usa `@import "tailwindcss"` sem `source(none)` e o Tailwind varre a pasta inteira. É o mesmo comportamento dos layouts e bundles do projeto.
- **Árvore compartilhada**: rodar o pipeline com a req-202 em andamento nela danificou o Lab (relato no BATCH-079 do `conn2flow-site`). O lote passou a rodar desta worktree.

## Pendências
- Revisão humana e mesclagem.
- As alterações deste lote também estão, sem commit, na árvore compartilhada `conn2flow` (foi dela que o Lab recebeu as primeiras rodadas). Depois da mesclagem, conferir se não sobrou diferença lá.
