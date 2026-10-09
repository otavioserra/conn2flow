# BATCH-252 — REQ-243: refinamentos visuais, usabilidade e controles do painel Tailwind, rodada 2 (Core)

- Status: implemented-pending-homologation. Os 17 itens do core implementados, publicados no Lab e validados em 2026-10-06.
- Projeto: conn2flow
- Raiz: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow` (execução na worktree `conn2flow-req243`, branch `feat/req-243`)
- Requisição: [REQ-243](../../human-requests/archive/req-243.md). Coordenação: conn2flow-site, REQ-108 / [BATCH-102](../../../../conn2flow-site/sdd/implementation/archive/BATCH-102.md).
- Autonomia: `autonomo_monitorado`. Commit e push na branch de trabalho; `main` não foi tocada.

## Live Todo List

- [x] `controles.js`: clique na opção do select isolado do que está atrás.
- [x] Histórico de alterações com quebra de linha.
- [x] Editor HTML: cartões compactos na aba Modelos.
- [x] Editor visual: palco (`#iframe-preview`) com a altura útil.
- [x] Alerta de arquivo inválido com `<b>NÃO</b>` interpretado com segurança.
- [x] `admin-arquivos`: dicas nos botões do topo e mesma altura dos botões de visualização.
- [x] `admin-categorias/adicionar-filho`: campo e botão com as classes oficiais.
- [x] `cookie-consent` e `menus`: mensagens em linha única; rádios do tipo de página na horizontal.
- [x] `galleries` e seletor de arquivos: bandeja sempre à vista, "Concluir seleção" que fecha o modal, alça sólida.
- [x] `pages-index`, `publisher`, `publisher-highlights`, `publisher-index`: mapeamento em 3 colunas, copiar compacto, selects do painel.
- [x] `publisher-pages`: filtro da listagem no padrão do `admin-paginas`; edição com respiro e mensagens em linha.
- [x] `variables`: sem tag crua, ícones sem contorno, botões no padrão.
- [x] `admin-cron` e `admin-environment`: selects, botões e dicas.
- [x] Compilação oficial, Vitest, PHPUnit, pipeline do Lab e roteiro de navegador.
- [ ] Homologação humana.

## O que a auditoria mostrou (causa de cada defeito)

| Defeito relatado | Causa |
| --- | --- |
| Clique no select aciona o botão de trás | A escolha fecha o painel no `mousedown`; o `mouseup` e o `click` do mesmo gesto caíam no elemento que ficava sob o cursor. Boa parte dos botões antigos do painel age em `mouseup`. |
| Palco do editor visual com ~150 px | `.c2fc-ponte-modal.fullscreen > .content iframe { height: auto !important }` (REQ-242, feita para o seletor de arquivos) valia para todo iframe de modal de tela cheia. |
| Cartões de modelos gigantes | A grade dependia de utilities responsivas; fora do bundle a ordem das folhas deixava `grid-cols-1` vencer. |
| Histórico com rolagem horizontal | Tabela com `min-w-max` e linha do item em `nowrap`. |
| `<b>NÃO</b>` escrito na tela | O diálogo do painel grava a mensagem como texto. |
| Botão sem estilo em `adicionar-filho` | A tela usa o formulário "incomum", que só existia na marcação do Fomantic. |
| Ícone acima do texto nas caixas informativas | `display: block` no estilo inline; o SVG do Lucide é bloco. |
| Rádios de menus empilhados e dica deslocada | Cada opção ocupava uma célula larga de grade; a dica nasce no centro do elemento. |
| Mapeamento espremido à esquerda | Grade externa de três colunas com uma única linha: o mapa interno cabia no primeiro terço. |
| Ações do campo personalizado empilhadas | A regra de linha existia, mas `.c2fc-campo` impõe `flex-direction: column`. |
| Tags `[[…]]` cruas em `variables` | A variante Tailwind do componente usa seis variáveis que não existiam no catálogo. |
| Bordas em volta dos ícones (`variables`, `host-manager`) | `outline` é variante de ícone no Fomantic (`edit outline`) e utility de contorno no Tailwind. |
| Balão de dica vazio em "Desativar" e "Excluir" | Botão de cabeçalho declarado sem `tooltip` saía com `data-c2f-dica=""`. |
| Botão "Adicionar" de imagem sem ação (site, `products/edit`) | `interface_historico()` substituía o bloco inteiro de variáveis JavaScript do `interface`, apagando a configuração do seletor de imagem que o módulo já tinha registrado. Só acontecia em registro com histórico. |

## Implementação

### Biblioteca de controles (`gestor/assets/interface/controles.js` e `controles.css`)

- **Select**: `mousedown` e `click` da opção param na opção; o restante do gesto (`pointerup`, `mouseup`, `click`) é engolido na captura até o botão ser solto. O controle acompanha quem escreve `select.value` ou `selectedIndex` por script e quem troca as opções (sem evento `change`). Select com `data-c2f-select` que entra depois da carga (linha clonada de `<template>`, bloco por AJAX) é montado sozinho.
- **Diálogo**: opção `formatado`, que aceita só tags de ênfase (`b`, `strong`, `i`, `em`, `u`, `br`, `code`), sem atributos; o resto entra como texto. O seletor de imagem do painel abre o alerta assim. Exposto como `c2fControles.formatado`.
- **Folha**: regra do iframe restrita a `.iframe-container`; histórico com `table-layout: fixed` e quebra em qualquer ponto; `c2fc-modelos-grade` (colunas pela largura do cartão, até 240 px); `c2fc-mapa` e `c2fc-mapa-titulo` (três colunas iguais com cabeçalho em destaque); ações do campo personalizado em linha; busca do campo do modelo com os ícones dentro do campo; ícone sem contorno; dica vazia sem balão; item atual de barra de navegação (`aria-current="page"`).
- `admin-tailwind.js`: tira a classe `outline` do ícone convertido para Lucide.

### `interface.php`

- Formulário "incomum" usa a variante Tailwind do formulário de inclusão em página Tailwind.
- Botão de cabeçalho sem dica usa o rótulo.
- `interface_historico()` mescla as variáveis dele às que a página já registrou.

### Módulos

- `admin-arquivos`: dicas e altura dos botões; no seletor de várias imagens a bandeja fica sempre à vista, com orientação quando vazia; "Concluir seleção" aplica o que está marcado e avisa o documento de fora (`admin-arquivos-seletor`), "Cancelar" fecha sem aplicar. `galleries` fecha o modal ao receber o aviso; alça de arrasto com opacidade 1 e a mesma caixa dos demais botões.
- `admin-categorias/adicionar-filho`: campos `c2fc-campo`.
- `cookie-consent`, `menus`, `pages-index`, `publisher-highlights`, `publisher-index` (adicionar, editar, clonar; dois idiomas): caixas informativas `inline-flex items-center gap-2` com o texto num `<span>`. `menus`: rádios em `flex flex-wrap items-center gap-4`, dica `top left`.
- Família publisher: mapeamento sem a grade externa, cabeçalhos `c2fc-mapa-titulo` (em `publisher`: "Variáveis do Modelo", "Campos do Publicador", "Vinculados (Variável/Campo)"), copiar compacto ao lado do campo, selects `template_id`, `rule`, `order_by` e `publisher_id` com `data-c2f-select`. `publisher-index` e `pages-index`: painéis das abas e alertas sem as classes de diálogo usadas por engano.
- `publisher-pages`: filtro da listagem refeito sobre o do `admin-paginas`; telas com invólucro `space-y-4`, botões do publicador em linha com respiro, endereço em caixa de linha única, nome e caminho lado a lado, editor fora do campo flexível.
- `variables` (componentes `configuracao-widget-tailwind` e `configuracao-campos-tailwind`): sete variáveis criadas no catálogo global, ícones Lucide, botões `c2fc-botao`, campos `c2fc-campo-entrada`, dicas.
- `admin-cron`: selects com o controle, botões do topo, do modal e das linhas com classe oficial e dica (a da linha diz a ação e a tarefa). `admin-environment`: selects com o controle, oito botões com classe oficial e dica.

## Guardas de teste

- `tests/Unit/PHP/RefinamentosReq243Test.php` (26 testes, 914 asserções): contratos de marcação nos dois idiomas e as três mudanças do `interface.php`.
- `tests/Unit/JS/req243-refinamentos.test.js` (18 testes): clique e `mouseup` que não vazam, select que acompanha script, formatador (inclusive tentativa de script e de atributo), bandeja e aviso do seletor, regras de folha.
- Ajustados por mudança de contrato pedida na requisição: `req242-refinamentos.test.js` (regra do iframe) e `InterfaceBotoesTailwindTest` (botão sem dica deixa de sair com dica vazia).

## Evidências

| Verificação | Resultado |
| --- | --- |
| `php cli/c2f.php resources:sync` | 511 recursos Tailwind, "Nenhum problema detectado". O aviso de `dist/` sem `PUBLIC_PATH` é o conhecido. |
| Vitest do core | 48 arquivos, 569 testes, sem falhas |
| PHPUnit do core (ordem padrão) | 1.655 testes, sem falhas (5 pulados, 4 depreciações do PHP e 3 do PHPUnit) |
| Pipeline `project:update-all conn2flow-site-local` | 6 rodadas, todas com saída 0 nas 8 etapas; a última com 0 arquivos diferentes na conferência por hash |
| Roteiro `sdd/validation/req243/req243-browser.cjs` | 140 de 140 conferências (27 grupos, core e site), em 1366 px e 390 px |
| `git diff --check` | limpo |

Resultado e capturas: `sdd/validation/req243/evidencias/` (`resultado.json` e 43 imagens).

O roteiro achou três defeitos que os testes de unidade não pegavam, corrigidos antes da rodada final: o `mouseup` do gesto do select ainda chegava ao elemento de trás; os botões de visualização do `admin-arquivos` ficavam 4 px mais baixos que os de ação; e o título da listagem do Stripe continuava à vista (site).

## Segunda passada, com o relato original do Engenheiro Chefe

Depois da primeira entrega o Engenheiro Chefe enviou as três mensagens originais da auditoria. Comparadas com a requisição, duas descrições tinham chegado resumidas a ponto de esconder o defeito:

| O que o relato original dizia | O que a requisição trouxe | O que foi feito agora |
| --- | --- | --- |
| `publisher-pages/editar`: "tem o escapamento", abaixo do editor "está aparecendo código, um iframe, cada hora aparece uma coisa dependendo do que eu clico, se eu der um F5…" | "Correção de vazamento de código/iframe e tags desformatadas abaixo do editor HTML" | Reproduzido no Lab: a aba SEO & Compartilhamento ficava marcada sem trocar o painel, porque o painel dela estava fora do editor. **Causa**: o módulo entregava o HTML da publicação cru ao `<textarea>` do editor (o `admin-paginas` usa `htmlentities`), e os valores dos campos do publicador iam crus para `<textarea>` e `value="…"`. Conteúdo com `</textarea>` ou entidades fechava o campo antes da hora e o resto do componente vazava. Escape aplicado na edição e na clonagem; o tipo `html` dos campos segue como marcação. |
| `publisher-pages/editar`: "Campos da página com o ícone em uma linha e o escrito na outra", "Campos do publicador está com problema também" | "mensagens inline" | Títulos das seções com `flex items-center gap-2`; etiqueta de variável de cada campo (`[[publisher#…]]`) e caixas de descrição com ícone e texto na mesma linha (`publisher-fields-tailwind`). |
| `galleries`: "conforme eu fosse clicando nos **botões selecionar**, aparecesse quais foram selecionados **na barra do cancelar**" | "bandeja com as miniaturas das imagens marcadas" | No seletor de várias imagens o botão Selecionar de cada arquivo (e o da visualização ampliada) marca o arquivo em vez de enviá-lo na hora; a escolha vai para a galeria em "Concluir seleção". O rodapé do modal, com um segundo Cancelar, sai de cena enquanto o seletor múltiplo está aberto. |

A primeira passada tinha reorganizado a tela do `publisher-pages` sem achar a causa: o roteiro conferia que nada do editor aparecia fora do formulário, mas não trocava de aba. Agora ele abre uma página de documentação com exemplos de código, percorre as cinco abas, recarrega e confere que a aba marcada e o painel visível são os mesmos.

## Concorrência no Lab

O lote da REQ-244 / REQ-109 foi publicado no mesmo Lab durante a validação, três vezes. Efeitos observados e como foram tratados:

- O `rsync -u` não repõe arquivo que o outro lote entregou com data mais nova: 399 arquivos ficaram diferentes numa rodada. Antes de cada publicação seguinte as datas da origem foram atualizadas (`touch`) e a conferência por hash voltou a 0.
- `auth:cookie` gera sessão para o mesmo administrador e derruba a do outro agente no meio do roteiro. O roteiro aceita `C2F_COOKIES` e passou a usar arquivo próprio (`temp/agent-cookies-req243.txt`).
- Entregar por etapas avulsas (`sync-core`, `sync-files`, `sync-db`) não equivale ao pipeline: os recursos do core no banco não foram repostos. A validação final usou só `project:update-all`.
- O estado final do Lab é o deste lote. A publicação da REQ-244 foi desfeita e precisa ser refeita depois da integração.

## Revisão da REQ-244 / REQ-109 (pedida pelo Engenheiro Chefe)

Lidos `feat/req-244` (`4bd4c57b`) e `feat/req-109` (`cb72f1ff`); suítes rodadas na worktree dele (PHPUnit 1.629 e Vitest 553, sem falhas).

- Sem achado bloqueante. Filtros de idioma, status e alvo, escape de SQL, `sandbox="allow-scripts"` mantido e ordem base → tema → compilador → folhas conferidos.
- Cruzamento com este lote: quatro arquivos no core (`asset-versions.json`, `minify-manifest.json`, `ComponentesData.json`, `cookie-consent.json`) e nenhum no site. A mesclagem simulada dá um conflito, em `asset-versions.json`, que o pipeline regenera.
- Observações para a integração:
  1. As suítes dele rodaram sobre a `main`; depois da mesclagem é preciso rodar `resources:sync` e as duas suítes de novo (este lote muda `controles.js`, `controles.css` e `interface.php`).
  2. `dashboard.js` passou a escrever utilities (`border-0`, `p-0`, `overflow-hidden`, `min-h-0`) que não existem no CSS compilado da página; o efeito vem das regras equivalentes que ele pôs em `dashboard-cards-tailwind.css`. Funciona, mas as classes são peso morto e contrariam a regra de utility só em HTML de recurso.
  3. Cada widget do Dashboard carrega o compilador Tailwind do navegador dentro do próprio iframe. É decisão de desenho coerente com o editor; o custo cresce com o número de widgets na tela.
  4. `sdd/implementation/` passa a ter 12 relatórios na raiz com os dois lotes (limite 10): rodar `php cli/c2f.php ai:archive-sdd --keep=10 --repair-links` depois da integração.

## Terceira passada: pente fino do Engenheiro Chefe (2026-10-06)

Relato humano depois de percorrer os módulos do painel. Os acertos do core estão na `feat/req-245` (`f59f8a00`), que contém a `feat/req-243` inteira; os do site, na `feat/req-108` (`60835db6`).

| Apontamento | Causa | Correção |
|---|---|---|
| `forms-submissions/view`: select de status nativo | faltava `data-c2f-select` | select montado pelo controle do painel |
| `variables`: select do tipo não abre na caixa de adicionar | a caixa é clonada depois da carga; a cópia trazia a casca do controle sem os eventos | `observarSelects()` monta selects que chegam depois e refaz cópias (casca viva marcada por propriedade `c2fcViva`) |
| `admin-arquivos`: galeria colada no canto e botões sem leitura | `<dialog>` sem centralização; botões sem estilo do painel | `position: fixed; inset: 0; margin: auto`, botões redondos escuros, ícones criados após abrir |
| Máscaras de percentual e dinheiro fora de formulário padrão | `campo-moeda.js` só entrava com o formulário do `interface` | `controles_incluir()` inclui `campo-moeda.js`; máscara `data-c2f-mascara="percentual"` (0 a 100, duas casas, envia com ponto) |
| `docs/sdd/00-baseline-architecture/` quebrada no Lab | registro gravado pelo editor quando ele ainda não escapava o HTML (415 KB, `user_modified=1`) | registro do Lab devolvido ao pipeline (`user_modified=0`, `versao=0`); o defeito de origem já estava corrigido na segunda passada |

Roteiro `sdd/validation/req243/req243-browser.cjs`: grupo 27 novo, **158/158** conferências. PHPUnit 1.672 e Vitest 573 verdes (contagem já com a REQ-244 junta, ver abaixo).

## Widget de apresentação no Dashboard: precedência entre folhas (2026-10-06)

Pedido do Engenheiro Chefe: a apresentação funciona como página (`apresentacoes/conn2flow-widget/`) e quebra como widget do Dashboard a partir do segundo slide. Detalhe e evidências em `BATCH-253.md`, seção "Correção posterior". Entrega na branch `integ/widget-apresentacao`, que soma `feat/req-245` e `feat/req-244` com os conflitos já resolvidos.

## Arquivos fora do escopo que a compilação alterou

Versão, checksum e CSS pré-compilado de módulos que este lote não editou (`forms`, `admin-templates`, `usuarios`, `perfil-usuario`, entre outros) e os `*Data.json`. São derivados: os bundles dessas páginas incluem os componentes compartilhados alterados aqui (formulários de edição e visualização, modelos do editor).

## Limites e observações

- **Publicações já salvas com o editor quebrado**: enquanto o HTML ia sem escape, salvar uma publicação com entidades (`&lt;div&gt;`) podia gravá-las como marcação. Não conferi se alguma página do Lab foi afetada; vale abrir uma das páginas de documentação editadas pelo painel e comparar com a fonte.
- **`html_extra_head` e `css`** continuam indo sem escape para os campos do editor, igual ao `admin-paginas`. Um `</textarea>` dentro deles teria o mesmo efeito; não foi alterado para não divergir do módulo de referência.
- **Filtro "todos" de `sales-reports`**: no select nativo o filtro já funcionava; o defeito vinha do select dentro do `<label>`. Marcação refeita e fluxo conferido no navegador.
- **`admin-categorias.js` não minifica** no passo 1 do pipeline (falha já presente na `main`; `node --check` passa). O arquivo é servido na versão de autoria. Não é deste lote.
- **Paridade entre idiomas já existente**: o filtro da listagem do `publisher-pages` em inglês não tem os selects de módulo e publicador (nem no componente original); `admin-environment` em inglês tem cinco selects, contra sete em português. Mantido como estava.
- **Não conferido em navegador**: telas em inglês (o Lab roda em pt-br; a paridade é coberta pelos testes de contrato) e as telas `clonar`.
- `sdd/human-requests/` não foi alterado: a troca de status em `CURRENT.md` fica com o Arquiteto.
