# BATCH-259 — Linha 3.1, fase 1 da lousa: objetos livres, esconder por largura, imagem de fundo e Google Fonts

- **Requisição:** [REQ-250](../human-requests/req-250.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.1` (branch `feat/req-250`, entregue na `3.1`). Não está na `main` nem na `3.0`.
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`)

## Live Todo List

- [x] Objetos livres: texto, forma, imagem, ícone e botão de chamada
- [x] Esconder por largura de tela, em três limiares
- [x] Imagem de fundo com opacidade e preenchimento
- [x] Google Fonts no texto dos objetos e no título das caixas
- [x] Servidor: normalização de objetos e opções no layout publicado
- [x] Testes, publicação na instalação da 3.1 e roteiro de navegador
- [ ] Homologação humana

## O que mudou

| Item | Como ficou |
|---|---|
| **Objeto livre** | Item do layout com o identificador reservado `objeto` e os atributos em `object`. É uma caixa como a de um widget: mesma posição em células, arrasto, redimensionamento, duplicação e remoção, na grade e na lousa. Não há chamada ao servidor para desenhar. |
| **Adicionar objeto** | Item no menu de opções. Cria um texto sem cabeçalho nem moldura, liga o modo de edição e abre as configurações, onde se escolhe o tipo. |
| **Texto** | Até 2.000 caracteres, com quebras de linha; fonte, tamanho (10 a 160 px), peso, alinhamento e cor. Entra como texto: o que o usuário escreve não vira HTML. |
| **Forma** | Retângulo, retângulo arredondado, círculo ou linha, com cor de preenchimento. |
| **Imagem** | Escolhida no gerenciador de arquivos do painel; encaixe cobrir ou conter; descrição. |
| **Ícone** | Nome de ícone do Lucide e cor. |
| **Botão de chamada** | Rótulo, destino (endereço http(s) ou caminho do painel), cores, fonte e opção de nova aba. No modo de edição não navega. |
| **Esconder em tela estreita** | Por widget ou objeto: abaixo de 640, de 1024 ou de 1280 px de largura da janela. Fora da edição o item some e a lousa não guarda o lugar dele; na edição aparece com contorno tracejado. |
| **Imagem de fundo** | Em qualquer caixa, do gerenciador de arquivos, com opacidade (0 a 100) e preenchimento (cobrir, conter, repetir). Soma-se à cor de fundo. |
| **Google Fonts** | Treze famílias, no texto dos objetos e no título das caixas. Uma folha só, pedida ao Google com as famílias em uso; some quando nenhuma é usada. |
| **Seletor de imagem** | Pop-up com o `admin-arquivos` em modo de seleção. Só aceita mensagem do próprio painel e arquivo de imagem. |

Arquivos: `gestor/modulos/dashboard/dashboard.js` (`normalizeObject`, `drawObject`, `useFonts`, `hiddenNow`, seletor de imagem), `dashboard.php` (`dashboard_widgets_objeto_normalizar`, `dashboard_widgets_imagem`, `dashboard_widgets_fontes`, opções novas), componente `dashboard-cards-tailwind` (campos do pop-up, seletor, folha), 50 variáveis por idioma.

### Segurança

- Imagem (do objeto e de fundo): só caminho do próprio painel, sem `..` nem `//`, com extensão de imagem. Vale no cliente e no servidor.
- Destino do botão: `http(s)://…` ou caminho começando por `/`; `javascript:`, `data:` e `//host` são descartados.
- Ícone: só letras minúsculas, números e hífen.
- Fonte: só as da lista; a lista do cliente e a do servidor são conferidas por teste.
- O layout publicado por perfil passa pela normalização no servidor.

## Defeito achado pelo roteiro e corrigido

O seletor de imagem abria atrás do pop-up de configurações (mesma camada) e não recebia clique. Passou a uma camada acima.

## Guardas de teste

- `tests/Unit/JS/dashboard.widgets-req250.test.js`: 9 testes. Cada tipo de objeto, entradas inseguras, criação pelo menu e troca de tipo, duplicação, imagem de fundo e fonte do título, opções novas no pop-up, esconder por largura liberando o lugar, seletor de imagem (origem e tipo), lista de fontes do pop-up.
- `tests/Unit/PHP/DashboardObjetosReq250Test.php`: 6 testes. Lista de fontes igual no servidor e no cliente, opções, imagem, objeto, layout publicado, campos do componente nos dois idiomas e dentro dos blocos de quem administra.
- Ajustados: expectativas de opções em `dashboard.widget-config.test.js` e `DashboardWidgetsPermissoesReq247Test.php`; contagem de chaves no roteiro de configurações.

## Evidências

- PHPUnit **1.691** e Vitest **623**, sem falhas.
- Pipeline `project:update-all conn2flow-v31-local` com saída 0 (40 segundos com o cache).
- Roteiro `sdd/validation/req250/req250-browser.cjs` em `https://v3.1-conn2flow.local/`: **23/23**.
- Regressões na mesma instalação: lousa **23/23**, links e ordem das abas **11/11**, configurações do widget **16/16**.
- Imagens em `sdd/validation/req250/evidencias/`.

## Limites e observações

- **A escolha de um arquivo pelo gerenciador não foi exercitada no navegador**: a instalação nova não tem arquivo enviado. O roteiro abre o seletor e confere que o gerenciador carrega; a imagem usada nos testes de exibição é uma que o próprio painel serve, posta no campo pelo roteiro. A troca de mensagens do seletor está coberta só em teste de unidade.
- **Imagem de fundo não aparece atrás de widget com fundo próprio opaco** (a apresentação, por exemplo): o conteúdo do widget cobre a caixa. Aparece em objeto, em widget transparente, na margem interna e no cabeçalho.
- **Google Fonts é um pedido a um serviço externo**: o navegador de quem abre o Dashboard fala com o Google quando há fonte em uso. Vale avaliar com a política de privacidade antes de usar em página pública (fases seguintes).
- Peso 700 não existe na Bebas Neue; nela o negrito é sintetizado pelo navegador.
- O limiar de esconder olha a largura da janela, não a da lousa. Em janela cheia ou com o menu lateral recolhido o limiar é o mesmo.
- O ícone é digitado pelo nome; não há catálogo para escolher.
- O objeto não tem edição direta na tela (clicar e digitar); tudo é pelo pop-up.
- A documentação pública do Dashboard não cobre este lote.
