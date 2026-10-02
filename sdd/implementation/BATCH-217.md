# BATCH-217: prévia de widgets no editor, toque na galeria e ícones (req-209)

Execução da [req-209](../human-requests/req-209.md).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/html-editor.php` | `html-editor-widget-render` devolve também o CSS autoral do widget |
| `gestor/assets/interface/html-editor-interface.js` | Prévia aplica esse CSS; detecta widget com mockup dentro do marcador |
| `gestor/modulos/cookie-consent/cookie-consent.widget.js` | Inicia aviso que chega depois da carga |
| `gestor/modulos/galleries/galleries.widget.js` | Toque nos dois tipos de trilho |
| `gestor/modulos/dashboard/dashboard.php` | Desenho dos ícones `chartline` e `cookie bite` |
| `cli/src/Support/Docs/DocsBuilder.php` | `layout` por idioma e `tailwind_sources` nas páginas de docs (commit `72ed3fe4`) |

## Decisões

- **Galeria: dois casos de toque.** Trilho com rolagem própria já desliza pelo navegador; faltava sincronizar o estado. Trilho sem rolagem ganha o gesto. Grade e mosaico não têm trilho: nada a deslizar.

## Defeitos corrigidos

- Prévia do editor de páginas sem estilo: o CSS autoral do widget não acompanhava o HTML renderizado por AJAX.
- Controlador não carregado na prévia: a expressão que detecta widgets aceitava no máximo um caractere entre a abertura e o fechamento do marcador, então widget com mockup passava despercebido.
- Controlador rodava antes de o HTML do widget chegar.
- `chart line` não é ícone do Fomantic (o nome é `chartline`).
- Docs em inglês apontavam para um layout que só existe em português.

## Validação

- `CookieConsentReq208Test` e `DocsBuildReq178Test`.
- Navegador no Lab, pelos roteiros do projeto que usa os widgets: prévia do editor de páginas e toque na galeria (13/13).

## Limites

- O teste de toque da galeria usa eventos de toque sintéticos e, no trilho com rolagem, a rolagem aplicada direto. Não foi testado em aparelho.

## Pendências

- Revisão humana.
