# BATCH-262 — Linha 3.1, fase 3B da lousa: módulo "Páginas de Lousa" (`dashboard-pages`)

- **Requisição:** [REQ-253](../human-requests/req-253.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.1` (branch `feat/req-253`, entregue na `3.1`). Não está na `main` nem na `3.0`.
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`)

## Live Todo List

- [x] Leitura do `publisher-index` e da casca do `publisher-pages`
- [x] Módulo com listar, adicionar e editar; tabela de apoio `dashboard_pages`
- [x] Controles de exibição e dois modelos (alvo `dashboard-pages`)
- [x] Widget "Lousa" aceita os controles como parâmetros
- [x] Menu, perfil de administradores e operação de permissão da página
- [x] Testes, publicação na instalação da 3.1 e roteiro de navegador
- [ ] Homologação humana

## De onde veio cada parte

| Origem | O que foi aproveitado |
|---|---|
| `publisher-pages` | O registro é uma página em `paginas` (nome, endereço, layout, acesso, status, mapa do site). Uma tabela de apoio (`dashboard_pages`) guarda o vínculo, como `publisher_pages`. A listagem mostra só as páginas criadas pelo módulo. |
| `publisher-index` | No lugar de publicador e campos variáveis: **controles de exibição** num esquema JSON e **modelo** escolhido no cadastro de modelos, com atalho para criar outro no `admin-templates`. |
| `admin-paginas` | A página criada é uma página comum. A tela de edição tem o atalho "Editar no módulo de páginas". |

## O que o módulo faz

- **Adicionar**: nome, endereço (acompanha o nome enquanto não é mexido), lousa do sistema, modelo, layout do site, acesso e controles. Cria a página e abre a edição.
- **Editar**: troca qualquer um deles. Mostra o endereço publicado, com atalho para abrir a página.
- **Listar**: nome, endereço e data; editar, ativar, desativar e excluir, que valem para a página.
- **Controles de exibição**: título da página (e o texto dele; em branco usa o nome), títulos dos itens, molduras, fundos, objetos livres e arranjo (o da lousa, sempre grade ou sempre lousa). A lousa em si não muda: os controles viram parâmetros do widget naquela página.
- **Modelos**: "Simples" (conteúdo centrado em até 1280 px) e "Largura total". São HTML com o lugar da lousa, o lugar do título e um bloco de título que sai quando o controle está desligado. Modelo novo criado no `admin-templates` com o alvo `dashboard-pages` aparece na lista.
- **Acesso**: página pública ou restrita. Só quem tem a operação `permissao-pagina` do módulo muda isso; sem ela, página nova nasce restrita.

## Como a página é montada

`dashboard_pages_html()` pega o HTML do modelo, mantém ou tira o bloco do título, põe o título escapado e troca o lugar da lousa pelo marcador do widget com os controles (`widgets#dashboard->render({...,"titulos":false,...})`). Estilo e cabeçalho extra vêm do modelo. **Salvar no módulo monta o HTML de novo**: ajuste feito à mão no módulo de páginas é substituído (a tela de edição avisa).

## Decisões tomadas no lote

- **Sem editor de HTML dentro do módulo**: quem quer mexer no HTML usa o `admin-paginas` ou cria um modelo. Mantém o módulo enxuto, como pedido.
- **Sem clonar**: duplicar é adicionar outra página com a mesma lousa.
- **O identificador da página não muda** quando o nome muda; o endereço muda pelo campo próprio.
- **Layouts oferecidos**: todos os ativos, menos os administrativos.

## Limites

- Modelo em Tailwind criado no `admin-templates` só leva estilo se o cadastro de modelos tiver gerado o CSS dele; os dois modelos do módulo usam CSS próprio, sem depender disso.
- Excluir a página deixa a linha de `dashboard_pages` no banco.
- Lousa excluída depois: a página continua no ar, sem a lousa.
- A lista de layouts ainda mostra layouts que não são de página de site (e-mail, impressão, iframe).
- Os limites do widget (REQ-252) valem aqui: widget que se mede pela janela aparece recortado.

## Arquivos

- `gestor/modulos/dashboard-pages/` (módulo novo: `dashboard-pages.php`, `.js`, `.json`, páginas e modelos nos dois idiomas)
- `gestor/db/migrations/20261007110000_create_dashboard_pages_table.php`
- `gestor/modulos/dashboard/dashboard.widget.php` (controles como parâmetros)
- `gestor/resources/{pt-br,en}/modules.json`, `module_operations.json`; `gestor/resources/user_profiles_modules.json`, `user_profiles_modules_operations.json`
- `tests/Unit/PHP/DashboardPagesReq253Test.php`, `tests/Unit/JS/dashboard-pages-req253.test.js`; contagens em `Req203LanguageAgnosticResourcesTest.php`
- `sdd/validation/req253/req253-browser.cjs`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte completa) | 1.709 testes. Sem falha em 6 de 7 execuções; uma acusou 1 erro que não se repetiu (ver abaixo). |
| Vitest (suíte completa) | 646 testes, sem falha |
| `resources:sync`, `assets:minify`, `project:update-all conn2flow-v31-local` | código de saída 0; tabela, modelos, páginas e módulo conferidos no banco |
| Navegador, `req253-browser.cjs` | 28/28 |
| Regressão no mesmo ambiente | REQ-252 26/26, REQ-251 18/18, REQ-250 23/23 |

O roteiro cria uma página pelo formulário, abre como visitante, confere a edição, troca modelo, controles e endereço, grava título próprio, tenta endereço repetido e lousa inexistente, desativa, reativa, exclui e repete o acesso com um usuário sem o módulo. As imagens do formulário e da página foram conferidas.

**Primeira rodada: 23/26.** Duas falhas eram do roteiro: chamava desativar e excluir por endereço sem o token da sessão, e o painel recusa (corretamente). O roteiro passou a usar os botões da tela e ganhou a conferência de que a ação sem token não tem efeito. A terceira era uma conferência minha mal escrita.

### Erro intermitente do PHPUnit

Em duas entregas (REQ-252 e esta) uma execução da suíte completa acusou 1 erro que sumiu nas seguintes. As duas vezes foram logo depois de rodar roteiros de navegador. Repeti cinco vezes guardando a saída para capturá-lo e ele não voltou. **Não sei qual teste é.** Fica como pendência.

## Critérios de aceite

- [x] Módulo no menu, com listar, adicionar e editar, restrito a quem tem acesso.
- [x] Adicionar cria a página com lousa, modelo e controles; a página abre no endereço informado.
- [x] Editar troca lousa, modelo, controles, endereço, layout e acesso. *Troca de lousa, de layout e de acesso não foram exercitadas no navegador; modelo, controles e endereço foram.*
- [x] Cada controle desligado some com a parte correspondente.
- [x] Modelos vêm do cadastro de modelos; o módulo entrega dois.
- [x] Endereço repetido recusado; lousa inexistente recusada. *Modelo e layout inexistentes: teste de unidade.*
- [x] Desativar ou excluir vale para a página.
- [x] PHPUnit e Vitest verdes; roteiro de navegador na 3.1; textos nos dois idiomas.

## Pendências

- Homologação humana (roteiro em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`).
- Descobrir o erro intermitente da suíte PHP.
- Criar um modelo novo pelo `admin-templates` com o alvo `dashboard-pages` e usá-lo (não exercitado).
