# BATCH-267 — Linha 3.1, fase 4 da lousa: modelos de lousa prontos

- **Requisição:** [REQ-258](../human-requests/req-258.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.1` (branch `feat/req-258`, entregue na `3.1`). Não está na `main` nem na `3.0`.
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`)

## Live Todo List

- [x] Modelo de lousa como recurso do sistema (alvo `dashboard-boards`, arranjo em JSON)
- [x] Três modelos entregues com o módulo, nos dois idiomas
- [x] "Criar a partir do modelo" no pop-up de layouts do Dashboard
- [x] Ligação dos widgets aos registros da instalação
- [x] Modo IA e alvo de IA
- [x] Testes, publicação na instalação da 3.1 e roteiro de navegador
- [ ] Homologação humana

## O que mudou

| Item | Como ficou |
|---|---|
| **Modelo de lousa** | Um modelo do cadastro de modelos com o alvo `dashboard-boards`. O conteúdo (campo HTML do modelo) é o arranjo em JSON: `{"modo": "grade" ou "lousa", "widgets": [...]}`, no mesmo formato das lousas. Viaja com a atualização do sistema e pode ser criado, editado ou clonado no `admin-templates`. |
| **Widget no modelo** | Traz só o tipo (`menus`, `forms`, `publisher-index`…) e a aparência. O registro fica vazio, porque registro é da instalação. |
| **Criar a partir do modelo** | No Dashboard, Opções > Layouts > Lousas do sistema: escolher o modelo, opcionalmente dar um nome, e criar. Sem nome, a lousa recebe o nome do modelo. |
| **Ligação aos registros** | Cada widget recebe o primeiro registro ativo daquele tipo na instalação. Tipo sem registro fica de fora e o painel diz quantos ficaram. Objeto livre entra sempre. |
| **Três modelos** | **Boas-vindas** (título, frase, menu, índice de páginas, botão), **Marketing** (título, destaques e formulário lado a lado, índice de publicações, botão) e **Vitrine de conteúdo** (título, galeria, índice de páginas e busca). Todos em grade de 12 colunas, com textos por idioma. |
| **Modo IA** | Modo e alvo de IA `dashboard-boards`, nos dois idiomas, descrevendo o formato do arranjo (itens, widgets, objetos, opções, limites). |

## Servidor

- `dashboard_lousas_modelo_arranjo()`: lê o JSON pelo mesmo caminho das lousas (`dashboard_widgets_layout_ler`), então o conteúdo de um modelo passa pela normalização de sempre. Conteúdo que não é um arranjo (HTML, JSON inválido, lista vazia) devolve nulo.
- `dashboard_lousas_modelo_ligar()`: liga os widgets aos registros; uma consulta por tipo.
- Ações `lousa-modelos` e `lousa-de-modelo`, pelo mesmo guarda das demais ações de lousa: só quem tem `widgets-administrar`.
- Modelo cujo conteúdo não é arranjo não é listado nem aceito. Modelo de outro alvo não é aceito.

## Decisões tomadas no lote

- **Arranjo em JSON dentro do modelo**, e não um tipo novo de recurso: reaproveita o cadastro de modelos, a sincronização de recursos e o modo IA.
- **Modelos em grade**, não em lousa de posição livre: a grade se ajusta a qualquer largura; o autor muda para lousa depois, se quiser.
- **Só widgets do core** nos três modelos. O pedido citou análise e últimas vendas: esses widgets não existem no core hoje (análise) ou são do site (loja, planos). Um modelo de marketing com eles entra quando existirem, ou como modelo do site.
- **Primeiro registro ativo do tipo**: escolha simples e previsível. O usuário troca o registro depois, no próprio widget.

## Limites

- **No `admin-templates`, o conteúdo de um modelo de lousa aparece como JSON no editor de HTML.** Funciona para editar, mas a prévia do editor não desenha a lousa.
- Modelo de lousa não tem miniatura (o teste de guarda de miniaturas é da linha 3.0 e ainda não está na 3.1; quando a `main` entrar na `3.1`, estes modelos vão precisar de miniatura).
- Em instalação sem registro de um tipo, o widget fica de fora: numa instalação nova a lousa pode nascer só com os objetos.
- O Assistente IA não foi exercitado (o Lab da 3.1 não tem servidor de IA).

## Arquivos

- `gestor/modulos/dashboard/dashboard.php`, `dashboard.js`, `dashboard.json`
- `gestor/modulos/dashboard/resources/{pt-br,en}/templates/dashboard-boards-*/`, `ai_modes/dashboard-boards/`
- `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html`
- `tests/Unit/PHP/DashboardLousaModelosReq258Test.php`, `tests/Unit/JS/dashboard.widgets-req258.test.js`
- `sdd/validation/req258/req258-browser.cjs`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte completa) | 1.716 testes, sem falha |
| Vitest (suíte completa) | 654 testes, sem falha |
| `resources:sync`, `assets:minify`, `project:update-all conn2flow-v31-local` | código de saída 0; três modelos por idioma e o modo de IA conferidos no banco |
| Navegador, `req258-browser.cjs` em `v3.1-conn2flow.local` | 13/13 |

O roteiro abre o pop-up de layouts, confere os três modelos na lista, tenta criar sem modelo, cria uma lousa pelo modelo "Boas-vindas" com nome próprio, confere os objetos, os widgets ligados a registro e o aviso de quantos ficaram de fora, abre a lousa na área de widgets, cria outra sem nome, tenta modelos inválidos e repete com um usuário sem a operação. No fim exclui as lousas e devolve o layout do administrador.

## Critérios de aceite

- [x] Três modelos de lousa no cadastro de modelos, nos dois idiomas.
- [x] O Dashboard lista os modelos e cria uma lousa do sistema a partir de um deles.
- [x] Widget sem registro na instalação fica de fora e o painel avisa; objetos livres entram sempre.
- [x] Conteúdo do modelo passa pela normalização das lousas; conteúdo inválido é recusado.
- [x] Só quem administra widgets lista modelos e cria lousa por eles.
- [x] Modo IA e alvo de IA registrados nos dois idiomas. *Assistente IA não exercitado.*
- [x] PHPUnit e Vitest verdes; roteiro de navegador na 3.1; textos nos dois idiomas.
