# BATCH-258 — Widgets do Dashboard: navegação para fora do quadro, aba de widgets em primeiro e plano da lousa

- **Requisição:** [REQ-249](../human-requests/req-249.md), coordenada com a REQ-111 / BATCH-105 do site
- **Status:** `implemented-pending-homologation`
- **Branch:** `feat/req-249`
- **Data:** 2026-10-06
- **Modo:** autônomo monitorado; publicação só no Lab local.

## Live Todo List

- [x] Reproduzir no Lab os links que abriam dentro do widget e o botão de carrinho sem efeito
- [x] Link, formulário e navegação por script saem para a página de fora
- [x] Loja dentro do widget: botão de carrinho leva à página do produto (site, REQ-111)
- [x] Aba de widgets antes da de módulos, guardada nos layouts e nos perfis
- [x] Testes, Lab e roteiro de navegador
- [x] Plano de evolução da lousa
- [ ] Homologação humana

## Diagnóstico

| Sintoma relatado | Causa medida no Lab |
|---|---|
| Menus e rodapé navegam dentro do quadro | os links trazem `target="_self"` no próprio elemento; o `base target="_top"` só vale para link sem destino próprio |
| "Adicionar ao carrinho" não faz nada | não é link: `store.js` chama `cart/` com a sessão do visitante; o documento do widget é isolado, sem cookie, e a chamada é barrada sem aviso |
| Busca não envia (achado pelo roteiro) | o isolamento sem `allow-forms` barra todo envio de formulário; a busca nunca funcionou dentro do widget |

## O que mudou

- **Clique em link**: no documento isolado, todo `a[href]` que não é âncora nem `javascript:` recebe `target="_top"` no momento do clique, qualquer que seja o `target` escrito. Âncora interna continua dentro do widget.
- **Formulário**: o envio recebe `target="_top"`; o isolamento ganhou `allow-forms`.
- **Navegação por script** (`location.href = …`): interceptada pela API de navegação do navegador, quando ela existe, e desviada para a página de fora.
- **Identificação**: `gestor.dashboardWidget = true` dentro do documento, para o script de um módulo adaptar o que depende da sessão.
- **Isolamento preservado**: `sandbox="allow-scripts allow-forms allow-top-navigation-by-user-activation"`, sem `allow-same-origin`. O widget não lê cookie nem a página do painel, e só tira o usuário do painel por clique dele.
- **Aba de widgets em primeiro**: chave "Widgets antes dos módulos" no menu de opções. Troca a ordem das duas abas e abre a que passou à frente. Preferência `dashboard_widgets_primeiro`; guardada nos layouts salvos (`first`) e no layout publicado por perfil (`primeiro`). Quem só visualiza recebe a ordem do perfil; quem ainda não escolheu aba abre na primeira.

## Guardas de teste

- `tests/Unit/JS/dashboard.widgets-req249.test.js`: 8 testes. O script que roda dentro do widget é tirado do `srcdoc` e executado: destino forçado em `_self` e `_blank`, âncora dentro, formulário, navegação por script, casos ignorados (recarga, âncora, `about:`, download); ordem das abas, visualizador, padrão do perfil, layout salvo e publicação.
- `tests/Unit/PHP/DashboardWidgetsPermissoesReq247Test.php`: ordem das abas no layout publicado e recusa da preferência para quem só visualiza.
- Site: `tests/Unit/PHP/LojaNoWidgetDoDashboardReq111Test.php`.

## Evidências

- Core: PHPUnit **1.685** e Vitest **614**. Site: PHPUnit **56** e Vitest **13**. Sem falhas.
- Pipeline do Lab com saída 0.
- Roteiro `sdd/validation/req249/req249-browser.cjs`: **11/11**, com os widgets que o administrador tem no Lab: menus (`target="_self"`), índice de páginas, busca, loja (imagem e carrinho), âncora interna, ordem das abas e persistência.
- Regressões no Lab: ver "Regressões" abaixo.

## Limites e observações

- **Navegação por script depende do navegador**: a interceptação usa a API de navegação, presente no Chrome e no Edge; onde ela não existe, um script que faz `location.href` ainda navega dentro do quadro. Links e formulários não dependem dela.
- **O carrinho não é alterado de dentro do Dashboard**: o botão leva à página do produto. Para adicionar direto seria preciso dar a sessão ao widget, o que desfaz o isolamento.
- O formulário de busca do Lab tem campos obrigatórios de teste; o roteiro desliga a validação nativa só para conferir o destino do envio.
- `allow-forms` permite a um widget enviar formulário; o envio só leva a página de fora por clique do usuário.
- A ordem das abas e a aba aberta são coisas diferentes: quem já escolheu uma aba continua abrindo nela.
- A documentação pública do Dashboard continua sem cobrir estes lotes.

## Plano de evolução da lousa (sem implementação neste lote)

Ideias do Engenheiro Chefe durante o lote, com a análise pedida dos módulos `admin-paginas` e `publisher-pages`.

### Princípio

Todo objeto livre (texto, forma, imagem, ícone, desenho) é uma caixa ancorada nas mesmas células dos widgets. A responsividade é a que a lousa já tem: `arrange()` não precisa saber o que há dentro da caixa.

### Decisões do Engenheiro Chefe sobre o plano (2026-10-06)

- **Linha de versão**: o que está entregue fecha a 3.0 (branches `main` e `3.0`). As melhorias abaixo são da `3.1`.
- **A lousa não ganha estrutura própria para o que um módulo já faz.** Funcionalidade nova entra como widget:
  - captura de contato: módulo `forms`, não um formulário novo;
  - medição: módulo de analytics do site (GA4), ampliado se preciso;
  - modelos prontos: um tipo novo no sistema de templates (`admin-templates`), como recurso do sistema;
  - navegação entre lousas ou seções: módulo `menus` (um modelo de menu que lista as lousas), sem "seções" dentro da lousa.
- **Edição por link exige login e permissão de escrita** na lousa, por operação de módulo, como as da REQ-247. Sem edição anônima.
- **Desenho à mão livre fica por último.**

### Fases (branch `3.1`)

| Fase | Entrega | Observação |
|---|---|---|
| 1 | Objetos livres básicos: texto (com Google Fonts), forma, imagem do gerenciador de arquivos, ícone, chamada para ação (botão com link). Esconder por largura de tela (três limiares). Imagem de fundo no widget e no objeto, com opacidade e modo de preenchimento. Google Fonts no título das caixas. | Os objetos entram no mesmo layout; a fonte carrega por `<link>` do Google Fonts só para as famílias usadas |
| 2 | Lousa nomeada, duplicar lousa inteira e versões | base para publicar e compartilhar |
| 3 | Widget "dashboard" (conjunto de widgets) e módulo `dashboard-pages`: lousa publicada como página, só leitura | ver "Como publicar" abaixo |
| 4 | Modelos de lousa pelo `admin-templates`; parâmetros da URL repassados a cada widget (cupom, produto, plano); widget de plano de assinatura, se ainda não existir | conferir antes o que o módulo de assinaturas já expõe como widget |
| 5 | Medição por lousa pelo módulo de analytics (visitas, cliques por objeto) | |
| 6 | Compartilhamento com papel (ver, comentar, editar), sempre com login e operação de módulo | |
| 7 | Desenho à mão livre | |

### Como publicar (análise de `admin-paginas` e `publisher-pages`)

Os dois módulos gravam na tabela `paginas` e já resolvem caminho, layout, layout por perfil, framework CSS, permissão (`sem_permissao`), mapa do site e o editor HTML com inclusão de widgets. O `publisher-pages` acrescenta o vínculo com um publicador (`publisher_id`) e a montagem do HTML a partir de modelo e campos.

Proposta em duas peças, na linha indicada pelo Engenheiro Chefe:

1. **Widget "dashboard" (conjunto de widgets)**: um registro é uma lousa nomeada (modo, widgets, objetos). O `render` devolve o contêiner da lousa com cada widget renderizado pelo sistema de widgets da própria página, sem iframe. Com isso qualquer página do `admin-paginas` inclui uma lousa pelo editor, como inclui um menu ou uma galeria, e a página pública não carrega um documento por widget.
2. **Módulo `dashboard-pages`**, espelhado no `publisher-pages`: cria a página em `paginas` já ligada a uma lousa (uma página, uma lousa), reaproveitando caminho, layout por perfil, permissão e mapa do site.

Pontos a decidir antes: onde mora a lousa nomeada (evoluir `dashboard_layouts` ou tabela própria com identificador, idioma e status) e como a lousa pública trata widget que depende de sessão do painel.

### Sobre iframe por widget

No Dashboard cada widget roda no próprio iframe isolado, e continua assim. Na página pública a proposta é diferente: o widget "dashboard" entrega os widgets renderizados pelo sistema de widgets da própria página, como qualquer widget incluído pelo editor, sem iframe. Parâmetro da URL, no Dashboard, é repassado a cada iframe; na página pública, cada widget já lê a URL da página.
