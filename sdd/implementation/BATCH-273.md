# BATCH-273 — Instalação funcional e página inicial padrão

- **Requisição:** [REQ-264](../human-requests/req-264.md)
- **Projeto:** `conn2flow`, `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`
- **Branch:** `feat/req-264`, criada da `main`.
- **Status:** `implemented-pending-homologation`
- **Data:** 2026-10-08
- **Coordenação:** Site REQ-121 / BATCH-115.

## Live Todo List

- [x] Auditar marcadores: preservar a primeira ocorrência de `modelo_var_troca`; consumidor Site passou a usar `modelo_var_troca_tudo`.
- [x] Investigar Nginx/PHP-FPM/bootstrap e reparar ingresso do tenant no Lab.
- [x] Acrescentar diagnóstico CLI ao instalador: timeout, resposta HTTP vazia/erro e aviso traduzido; sem autorrequisição bloqueante no PHP-FPM.
- [x] Página inicial pt-br/en com visual navy/cyan do Site, login, Dashboard e orientação de edição.
- [x] Adendo: usar `gestor/assets/images/logo-principal.png` no cabeçalho, preservando proporção; recurso versão 1.8.
- [x] Corrigir publicação de assets por SSH no Windows e sobreposição dos assets do projeto sobre o Core.
- [x] Compilar, testar, publicar no tenant do Lab e conferir desktop/mobile.
- [x] Revisar, registrar evidências e preparar commit com arquivos explícitos.

## Implementação e diagnóstico

O domínio pelo Caddy respondia HTTP 200 com zero bytes. O VirtualHost HestiaCP direto em 8443 retornava 6.840 bytes, sem fatal correspondente nos logs do Nginx/PHP-FPM. Apache não está instalado neste Lab. A causa era o ingresso Caddy, corrigido pelo script versionado no Site. A página padrão antiga também era mínima: a nova semente utiliza `layout-pagina-simples`, CSS próprio e variáveis localizadas. A homepage específica do Site continua sendo a dele.

`publicAccessReport()` detecta resposta vazia e falhas HTTP ao fim da instalação CLI; o resultado acompanha a resposta e gera aviso traduzido. O modo web não faz autorrequisição ao seu próprio pool. A sonda não tenta administrar um proxy externo.

`assets:publish` usava `C:/...` no cwRsync, interpretado como origem remota, além de descobrir somente os assets do Core. Agora reaproveita o transporte Bash oficial (cwRsync/OpenSSH pareados, namespace Cygwin, proprietário Hestia) e inclui assets do projeto com precedência. A lista de extensões permitidas e a exclusão de PHP/arquivos internos permanecem protegidas por teste com publicação real em staging isolado.

## Validação

- `c2f resources:sync`: saída 0; 4.341 recursos. A publicação sem DocumentRoot foi avisada e concluída explicitamente com o projeto do tenant.
- `composer test`: 1.762 testes, 20.600 asserções; zero falhas/erros. Quatro deprecações, quatro avisos PHPUnit e cinco skips herdados registrados. Windows com `OPENSSL_CONF` do Git e fontes LF nos testes que comparam trechos literais. A primeira rodada Windows falhou por OpenSSL/CRLF; a suíte Linux anterior passou.
- `npm.cmd run test -- --maxWorkers=2`: 60 arquivos, 664 testes aprovados. A primeira rodada teve um teste de temporização CSRF instável; passou isoladamente e na reexecução integral.
- `project:sync-core conn2flow-meusite-local`, `project:sync-db conn2flow-meusite-local --core-resources`, `css:rebuild --project=conn2flow-meusite-local --confirmar-remoto`, `assets:publish --project=conn2flow-meusite-local --confirmar-remoto`: saídas 0, executados sequencialmente.
- Navegador em `https://meusite.local/`, 1280 e 390 px: [20/20 verificações](../validation/req264/browser-results.json), logo carregado, sem overflow/erro JS, login e Dashboard respondendo. [Desktop](../validation/req264/homepage-1280.png), [mobile](../validation/req264/homepage-390.png), [roteiro](../validation/req264/req264-browser.cjs).
- [Resumo de testes](../validation/req264/tests-summary.json). Revisão final sem finding crítico remanescente.

## Limites e governança

Nenhuma cobrança real nem envio de e-mail real foi criado nesta validação; o dispatcher do Site é exercitado com transportes isolados. A instalação existente foi atualizada no Lab, sem reinstalação destrutiva. Certificados `.local` e pacote Hestia são configurados pelos scripts versionados do Site. Não houve publicação em produção, merge na main ou alteração normativa em SPEC.

O compilador normalizou hashes/versões e seus derivados; alterações geradas estão incluídas com a autoria. O arquivador oficial moveu REQ-254/BATCH-263 e reparou cinco links. Memória de engenharia não foi podada. A homologação humana global permanece no fluxo SDD; a aprovação da página inicial e o pedido de troca do logo foram recebidos neste chat.
