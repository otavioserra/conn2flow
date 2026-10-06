# BATCH-255 — Consolidação e documentação da Fase B

Requisição: [REQ-246](../human-requests/req-246.md). Data: 2026-10-06.
Status: `in-progress`. Autonomia: `autonomo_monitorado`.

- [x] Árvores inicialmente limpas; checkout main, pull e fetch nos dois repositórios.
- [x] Integração até 84deb54a, conforme resposta humana posterior: configurações de 515c764c permanecem para REQ-247.
- [x] PHPUnit e Vitest completos: 1.672 / 573 testes, zero falhas.
- [x] Auditoria inicial: 245 itens com avisos, 277 avisos, zero erros; legado zero.
- [x] Documentação bilíngue, extração de bibliotecas e compilação do site.
- [ ] Pipeline local e evidência HTTP/SQL.
- [x] Arquivamento SDD e integridade de links.
- [ ] Revisão final.
- [ ] Commit e push main.

## Coordenação

Site: conn2flow-site em C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site, REQ-110 / BATCH-104. Integração pronta inclui as cinco frentes e a correção posterior do widget. MCP Hub indisponível nesta sessão; execução pela CLI oficial.

## Evidências e limites

A publicação usa `ai-workspace/pt-br/docs/` e `ai-workspace/en/docs/` como fontes canônicas. Não há uma segunda árvore de autoria em `docs/`: o site recebe páginas, publicações, menus e llms.txt por `docs:build` e pelo pipeline SQL. Nenhuma aprovação de teste herdada foi contabilizada como execução deste lote.

### Integração e suítes

Main Core inicial 914c7b10, Site 93fd51d1. Core incorpora 243/245/244 e a correção de precedência até 84deb54a; Site incorpora 108/109 e a7192e75. Origin/main recebeu depois f53e0a67 e 27c185dc, incluindo a seção 5.6 e reserva da REQ-247. Antes de chegar a resposta humana, 515c764c foi incorporado localmente; o humano confirmou expressamente manter a Fase B até 84deb54a, e a incorporação adicional será revertida sem apagar a branch de origem. A decisão humana prevalece sobre a seção 5.6.

As quatro suítes foram executadas neste lote. Core PHPUnit: 1.672 testes, 17.527 asserções, zero falhas/erros, quatro skips, quatro deprecações e três deprecações PHPUnit. Vitest do recorte confirmado: 49 arquivos, 573 testes. A rodada transitória com 515c764c também passou (578), mas não representa o estado final. Site PHPUnit: 53 testes, 2.403 asserções; Vitest: dois arquivos, 13 testes. [Comandos e resultados](../validation/req246/tests-summary.json). PHP executado sob Linux na VM, com os arquivos do workspace montados; Core executou literalmente o script `composer test`, invocando o composer.phar disponível. Site não possui composer.json e usa o runner compartilhado com `req238-phpunit.xml`. Npm usa npm.cmd porque a política PowerShell bloqueia npm.ps1. Avisos ECONNREFUSED/AbortError do happy-dom não causaram falha.

### Cobertura da documentação

Inventário: [36 requisições recentes](../validation/req246/history-inventory.json), Core 219–245 e nove arquivos existentes do Site 100–109. REQ-104 do Site não existe com esse número no acervo pesquisado. Contratos documentados seguem o código integrado, inclusive onde diferem dos planos iniciais.

| Frente histórica | Conteúdo atualizado |
| --- | --- |
| Core 219–225, 236 e Site 100–101 | Interface administrativa: programa dos 51 módulos, componentes c2fc, campos, selects dinâmicos, máscaras, abas, tabelas e arquitetura Tailwind v4. |
| Core 226–233, 235, 237–240 e 242–244 | Dashboard V3.4, duas abas, P/M/G, estado por usuário, grid de 12 colunas, altura 120–960 em passos de 20, iframe opaco e compilador por último na camada utilities. |
| Core 234 | Referência nova db-data, bloco extraído e comportamento da carga de seeds. |
| Core 241 e 245 | Modo Manual/Automático, configuração privada, cron horário, períodos 1/7/30 dias, checagem manual separada, release estável, bloqueios, recusadas, verificação e rollback obrigatórios. REQ-241 absorvida: não promete janela por dia da semana ou notificação inexistente. |
| Core 239–243 e Site 102–108 | Editor, palco visual 100vh−220px, escape de publisher-pages, família publisher/pages-index, arquivos, galerias, formulários, menus, cookies, variáveis, categorias e TopBar. |
| Site 100–109 | Guia novo de administração do site: produtos/Stripe, catálogo, tipos/reviews, cupons, afiliados, fretes, pedidos/relatórios, assinaturas, gateways-pagamentos, apresentações, Host Manager e documentação. |

`docs:extract --all` atualizou 24 referências existentes. Criadas e preenchidas cinco bibliotecas em pt-br/en: atualizacoes-automatica (17 funções), controles (9), db-data (15), admin-topbar (7) e interface-listar-tailwind (2). HTML Editor explica retorno HTML/CSS, módulos JS e widgets; gestor/interface/arquivo recebem os contratos correlatos. Blocos automáticos continuam sob responsabilidade do extrator.

Nos treze módulos admin-arquivos, galleries, forms, forms-search, forms-submissions, menus, cookie-consent, variables, admin-categorias, publisher, publisher-index, publisher-highlights e pages-index, a seção nova de interface foi conferida no código; o texto técnico herdado foi **mantido, não conferido integralmente**. Seu verified_at anterior foi preservado. Não houve atualização indiscriminada de hashes para ocultar drift.

### Auditoria e limites

Auditoria inicial, antes da extração: 245 itens com desvios, 277 avisos, zero erros, 44 documentos limpos; legado zero. O [snapshot intermediário após a criação de esqueletos](../validation/req246/docs-audit-after-extract.json) contém quatro erros transitórios de descrição, corrigidos durante a redação. [Auditoria final estrita](../validation/req246/docs-audit-final.json): 220 avisos em 220 itens, 76 limpos, zero erros e legado zero. São 216 avisos de fontes alteradas desde verified_at e quatro lacunas de bibliotecas: cookie-consent, manutencao, modulo-distribuido-protocolo e paginas-layouts-perfis. Permanecem visíveis para a próxima passagem; o conteúdo relacionado aos contratos desta Fase B está atualizado.

Atualização real pela tarefa automática com sucesso e rollback continua uma limitação herdada do BATCH-254: o Lab está na última versão. Esta consolidação não declara ter exercitado esse cenário. PHP skips/deprecações e falha pré-existente de minificação admin-categorias.js também permanecem registrados.

### Higiene e revisão

`ai:archive-sdd --keep=10 --repair-links` no Core e com `--repo` no Site, pela CLI do Core. Core: oito arquivos arquivados e 14 links reparados. Site: dois lotes arquivados, 160 links antigos reparados, mais três links de índice. Sete referências históricas apontavam para nomes de documentos planejados inexistentes; mantidos seus rótulos como texto, explicitando a ausência. Gate posterior sem links órfãos; dez lotes na raiz de cada repositório. Nenhuma especificação normativa alterada.

Checkout CRLF normalizado para LF antes da compilação; seleção do commit usa diff sem diferenças de fim de linha e caminhos explícitos. O roteiro de precedência ganhou C2F_OUTPUT para preservar evidências antigas. O merge adicional gerou oito conflitos exclusivamente em Data.json, CSS pré-compilado e manifesto; fontes integradas preservadas e derivados recompilados pela CLI (quatro recursos novos, 507 em cache).

As configurações por widget de 515c764c foram retiradas da documentação desta Fase B, em obediência ao recorte humano; a branch de origem e o trabalho separado da REQ-247 permanecem preservados.

Primeiro pipeline encerrou com saída 0 e 778 arquivos conferidos por hash. Uma publicação externa posterior de 515c764c com acervo antigo desativou as páginas novas. O navegador detectou a manutenção e, depois, conteúdo desatualizado (2/36); [evidência da concorrência](../validation/req246/docs-browser/competing-publication-failure.json). A rodada final republica código integrado e acervo atualizado. Três arquivos gerados estavam mapeados por outro processo no Windows, impedindo truncamento; substituição atômica local liberou sua regeneração. Pipeline e navegador finais em coleta.
