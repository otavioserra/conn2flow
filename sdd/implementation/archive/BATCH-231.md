# BATCH-231 — Menus, galerias, formulários e cookies em Tailwind

- Requisição: [req-222](../../human-requests/archive/req-222.md), fatia 6 da req-219.
- Status: `in-review`; implementação entregue, com ressalva da prévia compartilhada.
- Autonomia: `autonomo_monitorado`.
- Projeto: `conn2flow`, worktree `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-req222`, branch `feat/req-222`.
- Escopo: menus, galleries, forms, forms-search, forms-submissions e cookie-consent; listagens incluídas após a integração do BATCH-228.
- Índices gerais mantidos pelo dono da req-219.

## Findings da revisão

1. **Prévia da galeria no modo Variáveis:** o editor compartilhado monta uma imagem com o endereço cru `@[[item#img-src]]@`; o iframe pede `/galleries/.../[[item` e recebe 404. As três verificações de console (adicionar/editar/clonar) seguem reprovadas, sem filtrar o erro do relatório. `gestor/assets/interface/html-editor.js` e o template público `galleries-grid` não têm alteração em relação à base `cb4bb169^`. O pedido de correção está registrado na req-219: editar esse código compartilhado está fora desta frente. Não se afirma rodada integralmente verde.
2. **Quatro arquivos de outra frente mais novos no destino:** `formulario.php`, `hooks.php`, `atualizacoes-hooks.php` e `admin-paginas.php`. O rsync preservou suas versões. Nenhum arquivo dos seis alvos apareceu na divergência.

## Implementação

As páginas administrativas pt-br/en usam `layout-administrativo-tailwind`, bundles e dependências declaradas. As páginas públicas mantêm seus layouts e fontes. Forms/forms-search têm componentes de informações com variantes Tailwind e `<details>`. A visualização compartilhada usa a variante fornecida pelo dono da req-219 em `aff98f83`.

Linhas de menus, galerias, campos e categorias de cookies usam templates DOM, propriedades e texto seguro. Controles dinâmicos recebem as classes da biblioteca; estilos específicos ficam nos recursos CSS, sem `tailwind_sources` apontando para PHP/JS. Os ganchos de seleção, abas, serialização e drag foram preservados. O checkbox oculto fica posicionado dentro da chave, evitando transbordamento da tabela no mobile. As abas de visualizar inicializam antes do retorno do JS de edição.

Forms-submissions usa DOM e fetch com o contrato AJAX existente; desabilita o botão durante a requisição e restaura-o no finally. Mensagens novas vêm das variáveis pt-br/en. A resposta usa `c2fControles.dialogo`; cancelar não envia requisição. Os seis scripts não têm alert/confirm/prompt nativo.

As duas simulações globais ganharam variantes nos dois idiomas, com JSON e ganchos idênticos aos originais. Em `html-editor.php`, só as duas expressões de escolha dessas simulações mudam. Versões e artefatos compilados dos alvos foram atualizados pelo pipeline.

## Live Todo List

- [x] Intake, skills, isolamento e coordenação da trava do Lab.
- [x] Páginas, metadados e componentes dos seis módulos.
- [x] Construtores, controles e diálogos.
- [x] Simulações e escolha das variantes.
- [x] Contratos PHP e Vitest.
- [x] Pipeline sequencial e CSS no Lab, com duas rodadas após a última mudança de JS.
- [x] Navegador: salvar/recarregar, AJAX, selects, chaves, abas, upload, modais e drag de menus; desktop e 390 px.
- [x] Suítes completas, revisão e remoção dos efeitos gerados alheios na worktree isolada.
- [x] Evidências, roteiro humano e branches para revisão.
- [ ] Correção da prévia compartilhada: encaminhada ao dono da req-219.
- [ ] Revisão humana conjunta; nenhum deploy de produção.

## Evidências

- PHPUnit completo: **1.526 testes, 14.957 asserções, zero falhas/erros**, quatro skips e sete avisos de depreciação. A primeira execução encontrou CRLF no transporte shell, CLI CSS e extração de funções Stripe; normalização local sem alteração de conteúdo fez a suíte passar.
- Contrato próprio: **4 testes / 565 asserções**. O teste administrativo falhou deliberadamente com o manifesto de menus anterior (`cb4bb169^`), no layout Fomantic, provando que detecta o estado antigo.
- Vitest completo após a última mudança: **39 arquivos / 509 testes aprovados**, incluindo os 11 cenários desta frente.
- Playwright real: **369/372 verificações aprovadas**, 30 navegações, capturas a 1366 e 390 px. Os três erros são o finding de prévia acima. Sem assets Fomantic no documento do painel (ícones legados são independentes), 403, diálogo nativo ou modal solto. O controle Fomantic `admin-arquivos` conserva o dropdown original e não ativa a ponte.
- Registros locais `E2E REQ222` foram criados, editados e recarregados com o mesmo schema. Forms/search testam opções multiline e required; cookies testam categoria e movimento; menus testam inclusão, edição inline e drag; galeria testa upload real, seleção e modal de legenda; submissions mantém o status existente e testa resposta vazia/cancelada, sem enviar e-mail.
- Fixtures descartáveis ficam no Lab para repetição e revisão humana. Uploads têm prefixo `e2e-req222-`; o relatório enumera a imagem da rodada final. Nenhum dado de produção foi usado.
- Pipeline oficial: `bash ./c2f project:update-all conn2flow-site-local`, Git Bash no PATH, sem `MSYS_NO_PATHCONV` global; launcher em LF. Rodadas finais 6 e 7 concluídas, CSS regenerado na VM e manutenção desligada. Só as entradas desta frente foram mantidas nos artefatos compartilhados.
- [Métricas verificáveis](../../validation/evidence-req222.json). No site: `sdd/validation/core/req222-modules-e2e.cjs` e `sdd/validation/core/evidence-req222/` (relatório e capturas dos seis módulos, inspecionadas visualmente).
- Roteiro humano no item único da req-219 em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`.

## Limites da rodada

Clonar foi navegado e exercitado, sem gravar cópias adicionais. Não foi disparado envio real de e-mail. A simulação do editor foi conferida pelo contrato das massas JSON. Conteúdo público e integrações externas não foram alterados. A prévia de variáveis e a conferência transversal seguem com os donos da req-219/req-225.
