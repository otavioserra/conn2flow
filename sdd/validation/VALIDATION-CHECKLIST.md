# Validation Checklist

## BATCH-266 — REQ-257 (linha 3.0)

- [x] Aba Modelos do editor lista os modelos nas telas de adicionar dos módulos sem campo de framework (7 telas).
- [x] Modelo escolhido pela aba aplica o HTML e registra o framework (formulários).
- [x] Miniaturas em 4:3, a proporção do cartão; os cinco casos apontados inteiros na largura.
- [x] PHPUnit 1.691 e Vitest 614 na `3.0`; navegador 24/24.
- [ ] Aba Modelos na tela de clonar e nos módulos do site.
- [ ] Homologação humana.

Detalhes: [BATCH-266](../implementation/BATCH-266.md).

---

## BATCH-265 — REQ-256 (linha 3.0)

- [x] 184 miniaturas WebP (580 × 394, até 27 KB) para os modelos que não tinham, nos dois idiomas.
- [x] `thumbnail` declarado em cada modelo; teste de guarda falha para modelo novo sem miniatura.
- [x] Conferência visual das famílias em pt-br, em três rodadas.
- [x] PHPUnit 1.688 e Vitest 614 na `3.0`.
- [ ] Folhas em inglês conferidas uma a uma.
- [ ] Homologação humana.

Detalhes: [BATCH-265](../implementation/BATCH-265.md).

---

## BATCH-255 — REQ-246 / Site REQ-110

- [x] Main atualizada e branches integradas, incluindo a correção posterior da apresentação (84deb54a).
- [x] PHPUnit completo: 1.672 testes, 17.532 asserções, zero falhas/erros; quatro skips e deprecações registrados.
- [x] Vitest completo: 49 arquivos, 573 testes aprovados no recorte até 84deb54a.
- [x] Histórico de 36 requisições levantado; extração das bibliotecas e documentação bilíngue atualizadas a partir do código.
- [x] Auditoria final, build idempotente e links HTTP da documentação.
- [x] Pipeline completo do Lab e comparação SQL antes/depois.
- [x] Navegador: regressão REQ-243 (158), REQ-245 (19), apresentação (24) e documentação desktop/mobile.
- [x] Arquivamento mantém dez lotes ativos na raiz; links órfãos reparados nos dois repositórios.
- [x] Revisão final e push das duas main.

Detalhes: [BATCH-255](../implementation/BATCH-255.md), [suítes](req246/tests-summary.json) e [inventário](req246/history-inventory.json).


> Lotes anteriores arquivados em [validation-176-239.md](archive/validation-176-239.md).

## BATCH-258 — REQ-249

- [x] Link com `target` próprio, formulário e botão de carrinho da loja levam a página de fora; âncora interna fica dentro.
- [x] Isolamento do widget sem `allow-same-origin`.
- [x] Aba de widgets em primeiro: menu, persistência, layout salvo e layout por perfil.
- [x] PHPUnit 1.685 e Vitest 614 sem falhas; site 56 e 13; pipeline do Lab com saída 0; navegador 11/11.
- [ ] Homologação humana; decisão sobre a ordem do plano da lousa.

Detalhes: [BATCH-258](../implementation/BATCH-258.md).

## BATCH-257 — REQ-248

- [x] Grade preservada como padrão; widgets por linha (1, 2, 3, 4, 6).
- [x] Lousa: colunas pela largura, posição livre com vão, colisão empurra para baixo, 20 px entre vizinhos.
- [x] Troca de modo mantém a proporção; modo e posições persistem e acompanham layout salvo e publicado.
- [x] Janela cheia cobre o navegador e sai com `Esc`; opções em botão flutuante com menu que fica aberto.
- [x] Lousa estreita e 390 px sem sobreposição nem rolagem lateral.
- [x] Tailwind 4.3.3 fixado; PHPUnit 1.684 e Vitest 606 sem falhas; pipeline do Lab com saída 0; navegador 23/23.
- [ ] `npm ci` na árvore principal e homologação humana.

Detalhes: [BATCH-257](../implementation/BATCH-257.md).

## BATCH-256 — REQ-247

- [x] Operações `widgets-administrar` e `widgets-visualizar` declaradas nos dois idiomas; `administradores` recebe a de administrar.
- [x] Servidor recusa gravar, publicar, listar perfis, abrir catálogo e renderizar conforme a operação (11 testes PHP).
- [x] Usuário sem operação: aba ausente. Usuário só com visualizar: layout do perfil, sem controles (navegador, fases `sem` e `ver`).
- [x] Administrador: publicar por perfil e para todos, alternar próprio e padrão, copiar o padrão, layouts salvos.
- [x] Duplicar, atalho de edição, larguras de 2 a 12, todos os cabeçalhos, tela cheia.
- [x] `resources:sync`, PHPUnit 1.683 e Vitest 588 sem falhas; pipeline do Lab com saída 0; navegador 43/43; 390 px sem rolagem lateral.
- [x] Integrado na `main` com a mesclagem revertida pela Fase B reaplicada; suítes e cinco roteiros de navegador repetidos sobre a integração.
- [ ] Concessão da operação pela tela de perfis, por um humano.
- [ ] Decidir a versão oficial do Tailwind (4.3.0 do `package-lock` ou 4.3.3) e atualizar a documentação do Dashboard.
- [ ] Revisão da chefia e homologação humana.

Detalhes: [BATCH-256](../implementation/BATCH-256.md).

## BATCH-254 — REQ-245

- [x] Decisão do ciclo, versões, vencimento por período, configuração e salvaguardas: 15 testes de unidade (`AtualizacaoAutomaticaReq245Test`).
- [x] Opções proibidas não chegam ao atualizador, nem com o arquivo de configuração adulterado.
- [x] Tarefa `admin-atualizacoes-automatica` declarada pelo módulo; aparece no `admin-cron` e acompanha o interruptor.
- [x] Abas Manual e Automático; modo manual funcionando; textos nos dois idiomas; 390 px sem rolagem lateral.
- [x] `resources:sync`, PHPUnit 1.670 e Vitest 569 sem falhas; pipeline do Lab com saída 0; navegador 19/19 e regressão da REQ-243 140/140.
- [x] Rotina pela engine no Lab: desligada, fora da hora e na hora (consulta e decide "já atualizado").
- [ ] Atualização real disparada pela rotina, com sucesso e com volta automática, no tenant isolado.
- [ ] Revisão da chefia e homologação humana.

Detalhes: [BATCH-254](../implementation/BATCH-254.md) e [resultado do navegador](req245/evidencias/resultado.json).

## BATCH-252 — REQ-243

- [x] Select: `mousedown`, `mouseup` e `click` da opção não chegam ao elemento de trás; o controle acompanha `select.value` escrito por script e selects inseridos depois da carga.
- [x] Editor: palco do editor visual com `calc(100vh - 220px)`, cartões de modelos de até 240 px, alerta com `<b>` interpretado e marcação fora da lista como texto.
- [x] Histórico sem rolagem horizontal; `admin-arquivos` com dicas e botões na mesma altura; `adicionar-filho` com classes oficiais.
- [x] `cookie-consent` e `menus` com mensagens em linha e rádios na horizontal; seletor de arquivos com bandeja e "Concluir seleção"; alça da galeria sólida.
- [x] Família publisher: mapeamento em 3 colunas com cabeçalho, copiar compacto, selects do painel; `publisher-pages` no padrão do `admin-paginas`.
- [x] `publisher-pages/editar`: HTML e valores de campo escapados; as cinco abas do editor (inclusive SEO) mostram só o próprio painel, também depois de recarregar.
- [x] `variables` sem tag crua e sem contorno nos ícones; `admin-cron` e `admin-environment` com selects, botões e dicas.
- [x] `resources:sync` (511 recursos), Vitest 569 e PHPUnit 1.655 sem falhas; `RefinamentosReq243Test` e `req243-refinamentos.test.js` novos.
- [x] Pipeline do Lab com saída 0 (6 rodadas), conferência por hash sem diferenças e navegador 140/140 em 1366 e 390 px.
- [x] Revisão da REQ-244 / REQ-109 sem achado bloqueante; mesclagem simulada com um conflito, em arquivo derivado.
- [ ] Homologação humana.

Detalhes: [BATCH-252](../implementation/BATCH-252.md) e [resultado do navegador](req243/evidencias/resultado.json).
## BATCH-253 — REQ-244 / Site REQ-109

- [x] Backend: colunas opcionais, template/autoria/head completos, tema e assets oficiais.
- [x] Frontend: base/tema → folhas parciais do widget → compilador completo, origem opaca e geometria sem padding/overflow.
- [x] Apresentações: template e altura embedded; consentimento compatível com o sandbox.
- [x] Compilação oficial: Core 511 / Site 1176 recursos; pipeline final e css:rebuild com saída 0.
- [x] Vitest: Core 553 / Site 13; PHPUnit: Core 1629 / Site 31, sem falhas/erros (skips/deprecações registrados).
- [x] Navegador 29/29: oito slides, desktop/mobile, setas/pontos/tela cheia, resize, catálogo e restauração das preferências.
- [x] Consolidação em main pela Fase B (BATCH-255).
- [ ] Homologação humana.

Detalhes: [BATCH-253](../implementation/BATCH-253.md), [resultado](req244/evidencias/resultado.json) e [métricas/limites](req244/evidencias/checks.json).

## BATCH-251 — REQ-242

- [x] `forms-search` e `forms`: abas sem fundo, copiar numa linha, select de tipo flutuante sem rolagem no container, dicas.
- [x] `forms-submissions`: dicas nas abas, JSON em CodeMirror somente leitura e select de status padronizado.
- [x] Dashboard: abas simétricas, capa ou ícone em 66 cards, menu de opções, tela cheia do widget, malha de 20 px, altura de 120 a 960 px.
- [x] TopBar e sidebar: favoritos à direita e tipografia igual em 18 módulos medidos.
- [x] `cursor: pointer` em rótulos de checkbox e rádio.
- [x] `galleries` e seletor: ajuda em linha, grade de controles, título de seleção múltipla e bandeja de miniaturas com remoção.
- [x] `resources:sync` (511 recursos), Vitest 551 e PHPUnit 1.629 sem falhas; `RefinamentosReq242Test` e `req242-refinamentos.test.js` novos.
- [x] Pipeline do Lab com saída 0 (três rodadas depois de uma com código 23, causa registrada) e navegador 112/112 em 1366 e 390 px.
- [x] Memória de execução podada: 296 linhas / 37 KB para 157 linhas / 22 KB, seções antigas em `archive/`.
- [ ] Homologação humana.

Detalhes: [BATCH-251](../implementation/BATCH-251.md) e [resultado do navegador](req242/evidencias/resultado.json).

## BATCH-249 — REQ-240

- [x] Pilares 1 a 6: ícones, abas, cartões, selects e alertas, tabelas e 390 px.
- [x] Adendo 1: `admin-atualizacoes` redesenhado, com as opções da req-201 na tela.
- [x] Core PHP 1.613 e JS 541; Site PHP 13 e JS 13, sem falhas.
- [x] Pipeline do Lab com saída 0 (oito rodadas) e 257 telas varridas em 1366 e 390 px.
- [x] Guardas novas: `IconesLucideReq240Test` (core) e `HarmonizacaoReq106Test` (site), com prova negativa.
- [x] Adendo 2: `admin-arquivos` como único módulo de arquivos; isolamento do usuário restrito provado no Lab; modo iframe corrigido.
- [ ] API de arquivos no `admin-arquivos`.
- [ ] Homologação humana.

Detalhes: [BATCH-249](../implementation/BATCH-249.md) e [resultado da varredura](archive/req240-browser-results.json).

## BATCH-247 — REQ-238

- [x] CA-A/B: perfil, atalhos e sidebar uniformes nos 22 módulos.
- [x] CA-C1–C7: badges, resize, blueprint, seletor, isolamento, switch e docs privadas.
- [x] CA-D1–D3: cards, títulos, abas funcionais e 38 telas desktop/390px.
- [x] Core JS 537/PHP 1.597; Site JS 13/PHP 7 sem falhas; 261 checks de navegador.
- [x] Pipeline Lab saída 0, manutenção desligada; 775 arquivos com conteúdo idêntico.
- [x] Provas negativas, revisão, versões e evidências registradas.
- [ ] Homologação humana.

Detalhes: [BATCH-247](../implementation/BATCH-247.md) e [inventário](req238-validation.json).

## BATCH-246 — req-237

- [x] Bases Core/Site preservadas e compilação oficial sequencial no Lab.
- [x] 19 novas capas Core e 14 Site, 1024×1024, menos de 100.000 bytes; 70 assets conferidos.
- [x] Dashboard pt-br/en: alturas M/G, topo -20px, links de capa/SVG, arrasto isolado e preferências restauradas.
- [x] Site REQ-100 concluída, 98/98 checks; cinco fixtures removidas.
- [x] Core JS 532 e PHP 1.594 sem falhas; Site JS 13 e PHP 7 aprovados.
- [x] Pipeline final saída 0 e manutenção desligada; screenshots desktop/390px e revisão sem bloqueantes.
- [ ] Homologação humana.

Detalhes: [BATCH-246](../implementation/BATCH-246.md) e [inventário](req237-validation.json).


## BATCH-243 — Tabelas JSON particionadas (req-234)

- [x] Biblioteca `db-data.php` com as quatro funções requeridas e carregamento pelo bootstrap/entradas CLI.
- [x] Round-trip estrito com registros completos, Unicode, floats e limite padrão real de 80 MiB.
- [x] Ausência, corrupção de um byte/hash e manifesto inválido abortam com `RuntimeException`; manifesto inválido não recorre a monolítico antigo.
- [x] Crescimento, redução de partes e retorno a monolítico/array vazio limpam arquivos obsoletos.
- [x] Falha ao gravar a segunda parte temporária preserva a tabela publicada e limpa os temporários já gravados.
- [x] Comparação SQL recebe todas as partes; nenhuma retirada falsa; parte ausente aborta antes de mudanças SQL. SQLite em memória com adaptação apenas de descoberta de colunas MySQL.
- [x] Compilador escreve partes, relê versão/checksum e publica `partitioned`/`total_parts`; sincronizador de plugins também compara o conjunto completo.
- [x] Exportação reversa → ZIP → extração → recuperação de HTML/metadados preserva todos os registros.
- [x] Checksums detectam mudanças além da primeira parte e ignoram alterações do timestamp de geração.
- [x] PHPUnit focado aprovado; `php -l` e `git diff --check` limpos.
- [x] Suíte geral comparada com checkout limpo de `3e2ad2e9`: mesmos 3 erros de Stripe e 2 falhas de CRLF.

Evidências, comandos e limites: [BATCH-243](../implementation/archive/BATCH-243.md). Banco principal e arquivos de outros lotes preservados; validação com fixtures temporárias, sem deploy de instalação.

## BATCH-245 / REQ-236 — integração homologada no Lab

- [x] Integrações Core REQ-223/233/224 sequenciais e preservação do stash/checkouts anteriores.
- [x] Dashboard V3.1: título/abas/Opções, tooltip, capa única M/G, edição, resize e seleção tipo/registro persistida.
- [x] Core Vitest 529/529; PHPUnit 1.594 testes / 15.795 assertions sem falhas/erros, quatro skipped e deprecações registradas.
- [x] Site Vitest 9/9; PHPUnit cinco testes / 462 assertions.
- [x] Navegador Site 88/88 e Dashboard 17/17; 390px; seis fixtures removidas e preferências restauradas.
- [x] Pipeline oficial completo sequencial, manutenção desligada; configuração local temporária restaurada.
- [x] Inventário 386 páginas/idiomas sem violações; SQL 316 páginas administrativas sem resíduos visuais. Resíduos globais de componentes/templates e stale documentados.
- [x] Revisão findings-first, controles negativos de instâncias/ícones e diff sem erros de whitespace.
- [ ] Revisão humana e consolidação final.

Relatório: [BATCH-245](../implementation/BATCH-245.md). Runtime pt-br; contratos bilíngues; OAuth e operações externas reais não exercitados.

## BATCH-248 / REQ-239 — refinamentos da auditoria humana

- [x] Dashboard P/G, editor de páginas, família Publisher e módulos administrativos refinados.
- [x] Interface: máscara monetária e imagem URL/admin-arquivos; cache Tailwind normaliza HTML/CSS/JS para LF, incluindo saída e entrada central.
- [x] Core Vitest 541; PHPUnit 1.599 testes / 15.872 asserções, sem falhas (5 skips, 4 deprecações PHP e 3 PHPUnit).
- [x] Site Vitest 13; PHPUnit 10 testes / 1.386 asserções; controles negativos cache/promoção reproduzidos.
- [x] Lab desktop/390px, submissões reais products/variações e herança Stripe autoritativa; dois produtos próprios removidos.
- [x] Pipeline oficial sequencial concluído, manutenção desligada; 778 hashes normalizados sem diferenças/sobras.
- [x] Cookie tabs com c2fc-abas-lista c2fc-anexa / c2fc-painel-aba em pt-br/en; recado do agente REQ-240/106 incorporado e trabalho paralelo preservado.
- [x] Revisão técnica, PHP lint e diff-check aprovados; evidências e limites no [BATCH-248](../implementation/BATCH-248.md).
- [ ] Homologação humana e consolidação Git.
