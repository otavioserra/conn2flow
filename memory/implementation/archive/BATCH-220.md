# BATCH-220: modal de edição do editor visual no documento Tailwind (req-212)

Execução da [req-212](../../human-requests/archive/req-212.md).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## Causa

A req-156 pôs a folha do Fomantic do iframe do editor visual numa camada (`c2f-editor-chrome`) abaixo das do Tailwind, para ela parar de reger o conteúdo da página. Resolveu o conteúdo e quebrou o próprio modal: o reset do Tailwind (`*{margin:0;padding:0;border:0 solid}`, camada `base`) fica acima dessa camada e vence o Fomantic. Medido no Lab: todos os elementos do `#html-editor-modal` com `padding: 0`, `margin: 0` e borda 0; cores e fontes sobreviviam, porque o reset não as declara.

Não há ordem de camadas que sirva às duas coisas: com o Fomantic acima de `base`, as regras dele sobre `a`, `h1`–`h5`, `p` e `body` voltam a reger o conteúdo que não tem utility.

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/assets/interface/html-editor-interface.js` | No documento Tailwind, `htmlEditorVisualFrameworkIncludes()` não carrega mais a folha do Fomantic, e `htmlEditorVisualModalHtml()` deixa o modal Fomantic de fora |
| `gestor/assets/interface/html-editor.js` | O modal portátil (`ensureFallbackModal`, o mesmo do editor ao vivo) passa a atender o iframe do painel: o botão de imagem chama o gerenciador de arquivos da janela pai, e o CodeMirror do campo de código nasce ao abrir (`ensureModalCodeMirror`) |
| `tests/Unit/JS/html-editor-paridade-visual.test.js`, `tests/Unit/PHP/ParidadeVisualReq156Test.php` | Contrato novo: sem folha do Fomantic no documento Tailwind |

Com isso saem do iframe a camada `c2f-editor-chrome`, o `@import` de 1,7 MB e a correção de `rem` que só existia por causa dele. A declaração de ordem das camadas ficou; o nome da camada do chrome continua reservado.

Documento com framework Fomantic: sem mudança (folha por `<link>`, modal Fomantic).

## Varredura dos demais elementos gráficos

Abertos um a um dentro do iframe do editor visual, no Lab, e conferidos por captura e por medição (classes sem regra de CSS; controles sem espaçamento, borda e fundo):

| Elemento | Resultado |
|---|---|
| Barra flutuante, contorno de seleção | íntegro |
| Painéis de navegação e de estilização | íntegros |
| Menu "Embrulhar" | íntegro |
| Código customizado, Modelos de sessão, Assistente de IA | íntegros |
| Modal de elemento embutido | íntegro |
| Menus da janela do painel ("+", Opções de Exibição) | íntegros |

Só o modal de edição dependia da folha do Fomantic; o resto da interface do motor traz os próprios estilos. As classes sem regra de CSS encontradas (`he-tb-edit`, `c2f-he-embed-box` e semelhantes) são ganchos de JavaScript, não resíduo.

## Validação

- `html-editor-paridade-visual.test.js` (26) e a suíte JS inteira (461); PHPUnit completa.
- Navegador no Lab, roteiro em `conn2flow-site/sdd/validation/website/req212-editor-visual-modal.cjs`: 8/8 (documento sem Fomantic, três modos do modal, salvar aplica, seletor de arquivos abre uma vez e devolve a escolha).
- Roteiros do site que passam pelo editor (`req091-slides-crud-e-previa` 30/30, `req090-cookies-slides-legal` 114/114) sem regressão.

## Limites

- O modal portátil não tem o quadro de pré-visualização da imagem escolhida (miniatura, nome e tipo) que o modal Fomantic tinha.
- Editor visual de layout (`admin-layouts`) usa o mesmo caminho; não foi aberto no navegador.
- Observado e não tratado: na página pública, o aviso de cookies (`z-index` 2147483000) fica por cima do modal do editor ao vivo enquanto o visitante-administrador não decide os cookies.

## Pendências

- Revisão humana.
