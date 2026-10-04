# BATCH-228 — Listagem do interface em Tailwind

- Requisição: [req-220](../human-requests/req-220.md).
- Status: `in-review` (implementação validada; revisão da req-219 pendente).
- Projeto: `conn2flow`, raiz `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`.
- Execução isolada: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-req220`, branch `feat/req-220`.
- Base: `ab0ce14a1`. Autonomia: `autonomo_monitorado`.

## Live Todo List

- [x] Ler intake, CURRENT, governança e contratos existentes.
- [x] Componente pt-br/en e listagem sem DataTables/jQuery.
- [x] Busca, ordenação, paginação e ações com CSRF; confirmação pelos controles.
- [x] Piloto modulos-grupos, todas as páginas Tailwind.
- [x] Testes PHP/Vitest e roteiro de navegador no Lab, desktop/390px.
- [x] Evidências e entrega para revisão da req-219.
- [ ] Revisão independente/consolidação pelo agente da req-219 e humano.

## Coordenação

Outro agente trabalha na req-219. Não editar controles, admin-paginas ou editor HTML. Separar implementação PHP em arquivo próprio; em interface.php alterar apenas a entrada da listagem e o contrato AJAX necessário. Índices gerais mantidos pelo agente da req-219, conforme intake. Pipeline somente a partir desta worktree e com destino de testes ocioso.

## Evidências

## Implementação e revisão técnica

O componente global e o JS próprio reutilizam o endpoint `interface_listar_ajax()`. O servidor continua aceitando somente as colunas da sessão; a variante Tailwind limita paginação a 100 linhas e início não negativo. Respostas vazias agora têm o envelope completo. A seleção por `interface_componente_variante()` mantém Fomantic/híbrido no caminho existente. Dados livres usam `textContent`; formatadores legados têm seu texto extraído em template inerte.

Editar/clonar são links; status e exclusão levam CSRF. Exclusão usa o diálogo de perigo de `c2fControles`, lendo novamente o token após confirmar. Requisições antigas são abortadas/descartadas. Todos os rótulos vêm de variáveis pt-br/en.

As oito páginas do piloto declaram layout, bundles e dependências Tailwind. Formulários preservam os campos do CRUD; `host` usa checkbox nativo. Para cumprir CA-1, o layout administrativo Tailwind e seu menu trocaram os ícones estruturais pela biblioteca Lucide já disponível e removeram a folha de ícones Fomantic. Metadados, CSS pré-compilado, minificado e seeds correspondentes foram gerados pelo compilador oficial. Derivados de outros módulos foram excluídos do diff.

Revisão findings-first: nenhum finding bloqueante no slice. Conferidos allowlist SQL, idioma, CSRF, confirmação, escape de textos, URLs da mesma origem, concorrência de respostas e seleção da variante. Não houve alteração nos controles, admin-paginas ou editor HTML. O agente da req-219 deve integrar os pequenos hunks em `interface.php` e entradas globais de recursos com seu trabalho atual; não substituir esses arquivos inteiros.

## Evidências executadas em 2026-10-04

- PHPUnit completo via PHP 8.5.10 no Linux do Lab: **1.475 testes, 11.720 asserções, zero falhas/erros**, 4 pulados, 4 depreciações e 2 depreciações PHPUnit. O checkout isolado foi temporariamente normalizado para LF: a primeira execução revelou 5 problemas de CRLF nos testes/scripts existentes e 1 expectativa de asset corrigida neste lote. A normalização de arquivos alheios foi restaurada e não integra o commit.
- PHPUnit focado final no Windows: **44 testes, 591 asserções**, zero falhas/erros, 2 depreciações PHPUnit. Inclui limites da paginação, sessão, idioma, allowlist, resposta vazia, layout e dependências nos dois idiomas.
- Vitest completo: **36 arquivos, 483 testes aprovados**; listagem nova: 10 testes para contrato, XSS, busca/ordenação/paginação, CSRF, confirmações, erros e respostas fora de ordem.
- `php -l`, `node --check` e `git diff --check`: aprovados.
- `php cli/c2f.php project:update-all project-test`: pipeline sequencial oficial concluído, banco/seeds sincronizados, rebuild CSS executado e **458 arquivos de código conferidos por hash**. Ambiente exclusivo local de testes, `local:true`; nenhum deploy de produção.
- [Roteiro Playwright](../validation/req220-browser.cjs) e [resultado](../validation/req220-browser-results.json): **21 verificações, zero erros de console**, desktop 1280 px e mobile 390 px, zero overflow da página. Exercitados cadastro, edição, clonagem, busca vazia/com resultado, ordenação, paginação real de 10 linhas, ativar/desativar, cancelar/confirmar exclusão e DataTables em admin-paginas/admin-layouts. Antes/depois: 7 grupos ativos; registros criados pelo roteiro removidos. Capturas versionadas em [assets/req220](../validation/assets/req220/).
- CA-1/CA-2/CA-3/CA-4 aprovados tecnicamente. A listagem pode rolar dentro da tabela em telas estreitas; a página não rola horizontalmente.

## Limites e reprodução

A publicação direta de `dist/` no Lab em Windows falhou no rsync (`source and destination cannot both be remote`, caminho `C:/...`). O pipeline conclui com aviso e o fallback `arquivo-estatico` serve os assets; o navegador confirmou funcionamento. Corrigir o transporte de publicação está fora desta requisição.

Inspeção de runtime executada em pt-br; os recursos en foram compilados e conferidos nos testes de dependências/idioma. Formatação HTML personalizada de outros módulos Tailwind não foi exercitada; a variante exibe seu conteúdo textual seguro.

Para repetir o navegador nesta worktree: configurar `project-test` local apontando para `gestor`, executar o pipeline oficial, gerar cookie com `php cli/c2f.php auth:cookie --project=project-test --out=temp/req220-cookies.txt` com saída privada e executar `node sdd/validation/req220-browser.cjs`. Cookies/logs privados ficam em `temp/`, ignorado pelo Git. O destino é `https://c2f-teste.local:8443/`, HestiaCP no Lab. Não iniciar pipeline concorrente do outro agente sobre esse tenant.
