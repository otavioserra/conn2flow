# BATCH-255 — Consolidação e documentação da Fase B

Requisição: [REQ-246](../human-requests/req-246.md). Data: 2026-10-06.
Status: `in-progress`. Autonomia: `autonomo_monitorado`.

- [x] Árvores inicialmente limpas; checkout main, pull e fetch nos dois repositórios.
- [x] Merge de origin/feat/req-243 e, conforme coordenação e orientação humana, origin/integ/widget-apresentacao (84deb54a); main consolidada 914c7b10.
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

Main Core `914c7b10`, Site `93fd51d1`, depois de pull das coordenações `991d5b28` / `ae677055`. Incorporadas as pontas atuais por nome remoto: Core 243, 245 e 244 via integração `84deb54a`; Site 108 e 109 via `a7192e75`. O caminho curto está expressamente autorizado nas requisições e pelo humano; inclui a correção posterior de precedência no widget.

As quatro suítes foram executadas neste lote. Core PHPUnit: 1.672 testes, 17.527 asserções, zero falhas/erros, quatro skips, quatro deprecações e três deprecações PHPUnit. Vitest: 49 arquivos, 573 testes. Site PHPUnit: 53 testes, 2.403 asserções; Vitest: dois arquivos, 13 testes. [Comandos e resultados](../validation/req246/tests-summary.json). PHP executado sob Linux na VM, com os arquivos do workspace montados; o comando do Core corresponde ao script `composer test`. Site não possui composer.json e usa o runner compartilhado com `req238-phpunit.xml`. Npm usa npm.cmd porque a política PowerShell bloqueia npm.ps1. Avisos ECONNREFUSED do happy-dom não causaram falha.

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

Checkout CRLF normalizado para LF antes da compilação; seleção do commit usa diff sem diferenças de fim de linha e caminhos explícitos. O roteiro de precedência ganhou C2F_OUTPUT para preservar as evidências antigas ao executar esta rodada. Pipeline e navegador em coleta; recibos finais abaixo serão preenchidos antes do push.
