# BATCH-263 — Linha 3.1: editor HTML e clonar no módulo "Páginas de Lousa"

- **Requisição:** [REQ-254](../../human-requests/archive/req-254.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.1` (branch `feat/req-254`, entregue na `3.1`). Não está na `main` nem na `3.0`.
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`)

## Live Todo List

- [x] Editor HTML em adicionar, editar e clonar
- [x] Modelo escolhido carrega HTML e CSS no editor
- [x] HTML do editor guardado por página; a página publicada sai dele mais os controles
- [x] Clonar, na listagem e na edição
- [x] Testes, publicação na instalação da 3.1 e roteiro de navegador
- [ ] Homologação humana

## O que mudou

| Item | Como ficou |
|---|---|
| **Editor HTML** | O mesmo componente dos outros módulos (`html_editor_componente`), com as abas Visualização, Modelos, Assistente IA e Código, nas três telas. Alvo de modelos: `dashboard-pages`. |
| **Modelo → editor** | Trocar o modelo busca o HTML e o CSS dele (ação `template-load`) e põe no editor, como no `publisher-index`. Ao abrir uma edição, vale o HTML guardado; o modelo só é carregado quando o usuário troca. |
| **O que é guardado** | `dashboard_pages.html_template` guarda o HTML do editor como foi escrito, com `[[lousa#widget]]` e `[[lousa#titulo]]` (como `publisher_pages.html_template`). `paginas.html` recebe a página montada: lousa e título nos lugares, controles aplicados. CSS, CSS compilado e cabeçalho extra do editor vão para a página. |
| **Variáveis do sistema** | `[[x]]` escrito no editor vai para o banco como `@[[x]]@` e volta como `[[x]]` na edição, como nos outros módulos. Os dois lugares da lousa são resolvidos antes dessa conversão. |
| **Clonar** | Botão na listagem e na edição. Abre o formulário com lousa, modelo, layout, acesso, controles e HTML da origem; nome e endereço em branco. Salvar cria outra página pelo mesmo caminho do adicionar. |
| **Envio retido** | Salvar enquanto o HTML do modelo ainda está chegando espera a carga e envia em seguida. |

## Defeito achado durante a validação

Primeira rodada do roteiro: 36/38. Trocar o modelo e salvar em seguida gravava o HTML do modelo anterior, porque a carga é assíncrona. Correção: o formulário segura o envio até a carga terminar. Teste de unidade e roteiro cobrem.

## Limites

- **A prévia do editor mostra `[[lousa#widget]]` e `[[lousa#titulo]]` como texto**, não a lousa renderizada.
- **Trocar o modelo substitui o conteúdo do editor**, sem pedir confirmação.
- Página criada na REQ-253 (antes do editor) abre o editor com o HTML do modelo dela.
- A troca do modelo foi exercitada no roteiro disparando o evento de mudança no campo; o clique no seletor visual não foi exercitado.
- As abas Modelos e Assistente IA do editor não foram exercitadas com o alvo `dashboard-pages`.

## Arquivos

- `gestor/modulos/dashboard-pages/dashboard-pages.php`, `.js`, `.json`; páginas `dashboard-pages-adicionar`, `-editar` e `-clonar` (nova) nos dois idiomas
- `gestor/db/migrations/20261007120000_add_html_template_to_dashboard_pages.php`
- `tests/Unit/PHP/DashboardPagesReq253Test.php`, `tests/Unit/JS/dashboard-pages-req253.test.js`
- `sdd/validation/req253/req253-browser.cjs` (o mesmo roteiro do módulo, agora com editor e clonar)

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte completa) | 1.710 testes, sem falha. O "erro intermitente" das entregas anteriores foi identificado (ver abaixo). |
| Vitest (suíte completa) | 648 testes, sem falha |
| `resources:sync`, `assets:minify`, `project:update-all conn2flow-v31-local` | código de saída 0; coluna `html_template` e página de clonar conferidas no banco |
| Navegador, `req253-browser.cjs` | 38/38 |
| Regressão no mesmo ambiente | REQ-252 26/26 |

Imagens do formulário de clonar e da página com o HTML do editor conferidas.

### O erro "intermitente" do PHPUnit era do comando de teste

Capturado nesta entrega: `CoreHelpersTest::testCriptografiaBasicaComChavesRsa`, com "no such file" para o arquivo de configuração do OpenSSL. Não é intermitente nem do antivírus: acontece sempre que a suíte roda num terminal Git Bash com `MSYS_NO_PATHCONV=1`, variável que os roteiros de navegador pedem. Com ela o caminho de `OPENSSL_CONF` chega ao PHP no formato do Bash (`/c/Users/...`) e o PHP não acha o arquivo. Conferido: 3 execuções do teste sem a variável, 3 passam; 3 com ela, 3 falham. O produto não tem defeito aqui. As execuções "com 1 erro" das REQ-252 e REQ-253 foram as que rodei no mesmo comando dos roteiros.

## Critérios de aceite

- [x] Editor HTML em adicionar, editar e clonar.
- [x] Trocar o modelo carrega HTML e CSS dele no editor.
- [x] O que foi escrito no editor aparece na página e volta ao editor.
- [x] Os controles continuam valendo sobre o HTML do editor.
- [x] Clonar cria página nova, com outro endereço, sem alterar a origem.
- [x] PHPUnit e Vitest verdes; roteiro de navegador na 3.1; textos nos dois idiomas.

## Pendências

- Homologação humana (roteiro em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`).
- Prévia do editor com a lousa renderizada, se for desejada.
