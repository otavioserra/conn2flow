# BATCH-229: módulos do editor, listagens e correções da revisão (req-219, fatia 6)

Parte da fatia 6 da [req-219](../../human-requests/archive/req-219.md) que fica com o agente da req-219; as demais frentes estão nas req-221 a req-224. Inclui a integração da [req-220](../../human-requests/archive/req-220.md) / BATCH-228 e os achados da revisão do Engenheiro Chefe no `admin-paginas`.

**Status**: `in-review` (validado no Lab; revisão humana no fim do programa).

## Commits (core)

| Commit | Conteúdo |
|---|---|
| `d045b736` | Reserva das req-221 a req-224 e skill `c2f-tailwind-module-migration` (nos cinco espelhos de skills) |
| `8b13286d` | `interface_status_selo()` (26 módulos) e SEO sem placeholder em comentário |
| `467d979d` | Merge da req-220 / BATCH-228 (listagem Tailwind, piloto `modulos-grupos`) |
| `cef65283` + `f627a0f2` | Backup do editor em Tailwind, ícones do painel "+", minificação antes da cópia |
| `70069c3b` + `9f0e24a1` + seguinte | `admin-layouts`, `admin-componentes` e listagem do `admin-paginas` |

## O que mudou

| Onde | Mudança |
|---|---|
| `interface.php` | `interface_status_selo($status)`: mensagem Fomantic igual à antiga no Fomantic, selo `c2fc-selo` no Tailwind; os 27 trechos escritos à mão nos módulos usam o ajudante. Select de backup por variante. |
| `interface-tailwind.js` | Troca de versão pelo select de backup (`backup-campos-mudou`) em JS puro, com o mesmo evento `callback` no `#gestor-listener`. |
| `interface-listar-tailwind` | `id="_gestor-interface-listar"`, que o JS dos módulos usa para ligar filtros da lista. |
| `html-editor.php` | Em página Tailwind inclui só a folha de ícones do Fomantic: o painel "+" do editor visual desenha ícones por nome (a req-220 tirou a folha do layout). |
| `ProjectUpdateAllCommand` / `ManagerUpdateAllCommand` | Minificação passa a ser a primeira etapa. Antes era depois da cópia: o destino ficava com o `.min.js` antigo sob o hash de versão novo e o navegador guardava o arquivo velho (o Ctrl+F5 que o Engenheiro Chefe precisou dar). |
| `admin-layouts`, `admin-componentes` | Listar, adicionar e editar no `layout-administrativo-tailwind` com bundle e campos `c2fc`. |
| `admin-paginas` | Raiz com a listagem nova e o filtro de tipo/módulo em variante (`lista-pagina-ou-sistema-tailwind`). |
| `html-editor-seo-tailwind` | O comentário citava o marcador do imagepick e a troca acontecia dentro dele. |

## Validação

- PHPUnit (Lab): 1.505 testes, 1 falha anterior (CRLF). Novos ou ampliados: `HtmlEditorTailwindReq219Test` (backup, placeholder em comentário, módulos do editor e listagens) e `PipelineMinificaAntesReq219Test`.
- Vitest: 484/484 (inclui os testes da req-220).
- Navegador no Lab, `conn2flow-site/sdd/validation/core/req219-controles-e2e.cjs`: **31/31**. Inclui backup trocando a versão no CodeMirror, SEO com imagepick, listagem do `admin-paginas` com filtro, `admin-layouts`/`admin-componentes` salvando sem 403, listagem do `modulos-grupos` (req-220) e o `admin-templates` ainda Fomantic como controle.
- Pipeline com JS alterado em uma rodada só: conferência por hash sem arquivo diferente.

## Pendências

- `admin-templates` migra depois da req-221: usa o alvo `publisher` do editor, cujas simulações são variantes daquela frente.
- Barra de rolagem do menu das docs na pré-visualização: estilos computados iguais aos da página publicada (barra nativa, sem regra própria nos dois lados). Não reproduzido; pedido de print no arquivão.
- "Workspace Social" do conn2flow-site no `admin-paginas`: funciona pela ponte, sem estilo próprio.
- Skill nova ainda não propagada pelo fluxo do `conn2flow-ai-workspace` (kits e templates).
