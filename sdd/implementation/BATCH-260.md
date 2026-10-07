# BATCH-260 — Linha 3.1, fase 2 da lousa: lousa nomeada, duplicar e versões

- **Requisição:** [REQ-251](../human-requests/req-251.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.1` (branch `feat/req-251`, entregue na `3.1`). Não está na `main` nem na `3.0`.
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`)

## Live Todo List

- [x] Tabelas `dashboard_boards` e `dashboard_boards_versions`
- [x] Servidor: listar, criar, gravar por cima, abrir, duplicar, excluir, versões e restaurar
- [x] Cliente: seção "Lousas do sistema" no pop-up de layouts
- [x] Textos nos dois idiomas
- [x] Testes, publicação na instalação da 3.1 e roteiro de navegador
- [ ] Homologação humana

## O que mudou

| Item | Como ficou |
|---|---|
| **Lousa do sistema** | Registro com identificador, nome, modo (grade ou lousa) e os itens (widgets e objetos, com posição e opções). Todo usuário com a operação `widgets-administrar` vê a mesma lista. |
| **Onde fica** | Menu de opções da área de widgets → "Layouts": a seção "Lousas do sistema" é a primeira do pop-up, acima de "Meus layouts salvos" e de "Layout padrão por perfil", que não mudaram. |
| **Criar** | Nome + "Criar com o layout atual": grava o que está na tela e o modo em uso. O identificador sai do nome (`Campanha de Outubro` → `campanha-de-outubro`); nome repetido ganha sufixo (`-2`, `-3`…). |
| **Abrir** | Traz modo, widgets, objetos e posições para o layout próprio de quem abriu, com instâncias novas, e grava. A lousa do sistema não muda quando a pessoa mexe depois: para devolver a alteração usa "Gravar por cima". |
| **Gravar por cima** | Troca o conteúdo da lousa pelo que está na tela, sobe o número da versão e guarda a versão anterior. |
| **Versões** | Lista sob a lousa, da mais nova para a mais antiga, com modo, quantidade de itens e data. "Restaurar" devolve o conteúdo daquela versão e guarda a que estava (restaurar também sobe a versão). Ficam as 20 mais recentes por lousa. |
| **Duplicar** | Lousa nova, independente, com o nome seguido de "(cópia)", na versão 1 e sem o histórico da original. |
| **Excluir** | A lousa sai da lista (status `D`); o registro e as versões continuam no banco. O identificador não é reaproveitado. |

## Servidor

- Migração `20261007100000_create_dashboard_boards_tables.php`: `dashboard_boards` (único por `id` + `language`) e `dashboard_boards_versions`.
- Sete ações AJAX no módulo `dashboard`: `lousas-listar`, `lousa-salvar` (cria sem `id`, grava por cima com `id`), `lousa-obter`, `lousa-duplicar`, `lousa-excluir`, `lousa-versoes`, `lousa-restaurar`.
- Todas passam por `dashboard_lousas_acao()`: recusa quem não tem `widgets-administrar` antes de qualquer consulta e, em falha de banco, responde a mensagem genérica e registra o detalhe só no log do servidor.
- O conteúdo passa por `dashboard_widgets_layout_normalizar()`, o mesmo filtro do layout por perfil (atributos conhecidos, listas fechadas, imagem e destino só do próprio painel).
- Identificador só é consultado se casar com `^[a-z0-9-]{1,120}$`.

## Limites desta fase

- A lousa é por idioma do painel, como os demais registros: criada em pt-br, não aparece para quem usa o painel em inglês.
- Não há trava de edição simultânea: dois administradores gravando por cima da mesma lousa geram duas versões em sequência, e a última vale. A anterior fica no histórico.
- Lousa excluída não tem tela de recuperação; o registro fica no banco.
- Quem só visualiza widgets não vê lousas do sistema: continua recebendo o layout do perfil. Usar uma lousa como página ou como padrão de perfil é a fase 3.

## Arquivos

- `gestor/db/migrations/20261007100000_create_dashboard_boards_tables.php`
- `gestor/modulos/dashboard/dashboard.php`, `dashboard.js`, `dashboard.json`
- `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.{html,css}`
- `tests/Unit/PHP/DashboardLousasReq251Test.php`, `tests/Unit/JS/dashboard.widgets-req251.test.js`
- `sdd/validation/req251/req251-browser.cjs`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte completa) | 1.697 testes, sem falha (5 pulados, como antes) |
| Vitest (suíte completa) | 631 testes, sem falha |
| `resources:sync`, `assets:minify`, `project:update-all conn2flow-v31-local` | código de saída 0; as duas tabelas criadas no banco da instalação |
| Navegador, `req251-browser.cjs` em `v3.1-conn2flow.local` | 18/18 |
| Regressão no mesmo ambiente | REQ-250 23/23, REQ-248 23/23, REQ-249 11/11, REQ-247 (administrador) 22/22, configurações do widget 16/16 |

O roteiro cria uma lousa, grava por cima, lista e restaura a versão, abre, duplica, exclui, mede o pop-up em 390 px e repete as sete ações com um usuário sem a operação (todas recusadas, lousa intacta). No fim exclui o que criou e devolve o layout do administrador.

Para a recusa foi criado, só no banco da instalação local da 3.1, o usuário `usuario-de-roteiro` (perfil `cloud-nano`, sem senha utilizável).

## Critérios de aceite

- [x] Criar lousa com nome a partir do layout atual. *Que ela aparece para outro administrador foi conferido pelo registro no banco e pela listagem; a instalação só tem um administrador.*
- [x] Abrir traz modo, widgets, objetos e posições para o próprio layout.
- [x] Gravar por cima cria versão; restaurar devolve o conteúdo e guarda a que estava.
- [x] Duplicar gera lousa independente.
- [x] Excluir tira da lista.
- [x] Só quem tem `widgets-administrar` lista, cria, altera ou exclui; o servidor recusa os demais.
- [x] Conteúdo normalizado no servidor.
- [x] PHPUnit e Vitest verdes; roteiro de navegador na 3.1; textos nos dois idiomas.

## Pendências

- Homologação humana (roteiro em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`).
- Na linha longa de ações, "Excluir" quebra para a linha de baixo quando o nome da lousa é comprido. Não atrapalha o uso.
- A raiz de `sdd/implementation/` passou de 10 relatórios. O arquivamento (`ai:archive-sdd`) ficou para ser feito na `main`, para não divergir da `3.1` nos mesmos arquivos.
