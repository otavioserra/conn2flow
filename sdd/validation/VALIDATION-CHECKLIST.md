# Validation Checklist


> Lotes anteriores arquivados em [validation-176-239.md](archive/validation-176-239.md).

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

Evidências, comandos e limites: [BATCH-243](../implementation/BATCH-243.md). Banco principal e arquivos de outros lotes preservados; validação com fixtures temporárias, sem deploy de instalação.

## BATCH-245 / REQ-236 — integração homologada no Lab

- [x] Integrações Core REQ-222/223/224 sequenciais e preservação do stash/checkouts anteriores.
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
