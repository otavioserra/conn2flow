# BATCH-256 — Área de widgets do Dashboard: permissões, layout por perfil, layouts salvos e ferramentas do card

- **Requisição:** [REQ-247](../human-requests/req-247.md)
- **Status:** `implemented-pending-review`
- **Branch:** `feat/req-247`, nascida da `integ/widget-apresentacao` (REQ-243, REQ-245, REQ-244 e as configurações por widget). Entra na `main` depois da consolidação da REQ-246.
- **Data:** 2026-10-06
- **Modo:** autônomo monitorado; publicação só no Lab local.

## Live Todo List

- [x] Duas operações do módulo `dashboard` e vínculo com `administradores`
- [x] Tabela `dashboard_layouts` (migração) e layout por perfil, com `*` para todos
- [x] Guardas no servidor em toda ação que grava, publica, lista ou renderiza
- [x] Blocos do componente retirados no servidor conforme a permissão
- [x] Próprio layout x padrão do perfil, com cópia do padrão
- [x] Layouts salvos (salvar, aplicar, excluir)
- [x] Duplicar widget, atalho de edição do registro, larguras de 2 a 12 colunas
- [x] Menu: todos os cabeçalhos e tela cheia
- [x] Testes PHP e JS, Lab com um usuário de cada tipo
- [ ] Revisão da chefia e homologação humana

## O que mudou

### Permissões

| Operação | Identificador | Efeito |
|---|---|---|
| `widgets-administrar` | `administrar-widgets-do-dashboard` | edita a área, mantém layout próprio, salva layouts e define o layout dos perfis. Ligada ao perfil `administradores` na origem. |
| `widgets-visualizar` | `visualizar-widgets-do-dashboard` | vê a área com o layout do perfil, sem controles. Não vem ligada a nenhum perfil. |

Sem nenhuma das duas, a aba de widgets não chega ao navegador. A concessão é feita na tela de perfis (`usuarios-perfis`), que já lista as operações de cada módulo.

`dashboard_widgets_pode_administrar()` e `dashboard_widgets_pode_ver()` decidem. O servidor recusa com a mensagem `widgets-sem-permissao`:

| Ação AJAX | Exige |
|---|---|
| `salvar-preferencias` com chave `dashboard_widgets_*` | administrar |
| `widgets-catalogo`, `widgets-registros` | administrar |
| `widgets-layouts`, `widgets-layout-publicar`, `widgets-layout-remover` (novas) | administrar |
| `widget-render` | visualizar ou administrar |

O componente ganhou blocos marcados (`widgets-aba`, `widgets-painel`, `widgets-menu-ver`, `widgets-menu-admin`, `widgets-aviso-admin`, `widgets-vazio-admin`, `widgets-modais-admin`) que o PHP retira antes de entregar a página.

### Layout por perfil

Tabela `dashboard_layouts` (`perfil` único, `layout` em JSON, `id_usuarios` de quem publicou). `perfil = '*'` é o layout de todos; um perfil sem linha própria usa o de todos. O layout publicado passa por `dashboard_widgets_layout_normalizar()`: só as chaves conhecidas, no máximo 40 widgets, largura de 2 a 12, altura de 120 a 960 px, opções de aparência dentro das listas.

Quem administra alterna, no menu de opções, entre o próprio layout e o padrão do perfil (preferência `dashboard_widgets_fonte`). No padrão, a edição fica travada, aparece um aviso e há o botão de copiar o padrão para o próprio.

### Layouts salvos

Preferência `dashboard_widgets_salvos` do usuário: até 20 arranjos com nome. Salvar com nome repetido substitui. Aplicar copia para o próprio layout com instâncias novas.

### Ferramentas

- **Duplicar**: cópia independente logo depois do original, com tamanho e configurações.
- **Abrir o registro para editar**: o servidor devolve `edit_url` quando o módulo do widget tem página com opção `editar` e o usuário acessa o módulo; o cliente só aceita endereço do próprio painel; abre em nova aba.
- **Larguras**: de 2 a 12 colunas, uma a uma. No tablet (malha de 6) as larguras caem em 2, 3 ou 6. Layouts antigos continuam válidos.
- **Menu**: mostrar ou esconder todos os cabeçalhos; tela cheia da área de widgets.

## Guardas de teste

- `tests/Unit/PHP/DashboardWidgetsPermissoesReq247Test.php`: 11 testes. Operações nos dois idiomas, quem vê e quem administra, normalização, queda para o layout de todos, publicação só para perfil existente, recusa de cada ação, atalho de edição, blocos do componente por permissão, migração.
- `tests/Unit/JS/dashboard.widgets-req247.test.js`: 10 testes com o HTML real do componente.
- Ajustados por dependência nova: `DashboardWidgetStylesReq238Test` (funções novas na renderização) e `Req203LanguageAgnosticResourcesTest` (vínculos de operação de 3 para 4).

## Evidências

- PHPUnit **1.683** testes, sem falhas. Vitest **588**.
- Pipeline `project:update-all conn2flow-site-local` com saída 0; migração aplicada (tabela criada), operações e vínculo no banco do Lab conferidos por consulta.
- Roteiro `sdd/validation/req247/req247-browser.cjs`, **43/43** em quatro fases:

| Fase | Usuário | Conferências |
|---|---|---|
| `admin` | administrador | 22: botões do card, atalho de edição abrindo a tela certa, duplicar, largura coluna a coluna e mínima de 2, todos os cabeçalhos, tela cheia, salvar layout, publicar em dois perfis, 390 px, padrão do perfil com edição travada, persistência depois de recarregar |
| `sem` | perfil `cloud-nano` sem operação | 7: aba e grade ausentes, nenhum controle, servidor recusa gravar, publicar, listar, catálogo e renderizar |
| `ver` | o mesmo, com `widgets-visualizar` concedida no Lab | 10: vê o layout publicado com os widgets carregados, nenhum botão nos cards, servidor recusa gravar e publicar, renderiza sem atalho de edição |
| `limpar` | administrador | 4: preferências e layouts publicados de volta ao que eram |

- Roteiros anteriores repetidos sobre esta branch: configurações do widget 16/16 e precedência da apresentação 24/24.
- Imagens em `sdd/validation/req247/evidencias/`.

## Limites e observações

- **Instalação existente**: depois da atualização só o perfil `administradores` edita widgets. Outros perfis que usavam a área deixam de vê-la até receberem uma das operações. É a consequência pedida, mas muda o que alguns usuários veem.
- **Perfil criado pelo usuário com o mesmo papel de administrador** não recebe a operação sozinho.
- A concessão pela tela de perfis não foi exercitada no navegador; no Lab a operação foi concedida e retirada por SQL.
- Tela cheia conferida em navegador sem interface; falta olhar num monitor de verdade.
- O layout publicado guarda o registro de cada widget. Se o usuário do perfil não tem o registro (idioma, registro inativo), o card mostra a mensagem de erro do widget.
- O atalho de edição supõe a rota `?id=<registro>` da página `editar` do módulo, que é o padrão do painel.
- Roteiro que usa o administrador do Lab concorre com o uso humano do mesmo usuário; ele guarda e devolve o estado, mas uma mudança feita por fora durante a execução se perde.
