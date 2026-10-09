# BATCH-246 — req-237: consolidação, capas e Dashboard V3.2

- Status: `in-review`
- Autonomia: `autonomo_monitorado`
- Data: 2026-10-05
- Core: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- Site: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site`
- Execução isolada: worktrees `conn2flow-req237-exec` e `conn2flow-site-req237-exec`, branches `feat/req-237-exec`.

## Live Todo List

- [x] Preservar autoria do Site, incluindo arquivos não rastreados.
- [x] Fast-forward Site `main` para `2deacc38`.
- [x] Fast-forward Core `main` para `a7a7bf33` (inclui `f59fc499`).
- [x] Validar base pelo pipeline oficial no Lab.
- [x] Gerar e registrar 19 capas Core (12 iniciais e 7 solicitadas no chat) e 14 capas Site, 1024×1024, abaixo de 100 KB.
- [x] Dashboard pt-br/en: alturas M/G, enquadramento, links e isolamento do arrasto.
- [x] Recuperar autoria REQ-100 e concluir migração no Site (BATCH-094).
- [x] Testes JS/PHP, navegador desktop/390 px e sincronização final.
- [x] Consolidar evidências e commits locais.

## Preservação e coordenação

Stash Site `6a6519a63e69fb17d9b58c05b35d57abe164591f`, mensagem `wip-req100-autoria-previa`, criado com `--include-untracked`. A inspeção de `git clean -nd` encontrou CSS de autoria e recursos de assinaturas junto dos derivados: foram preservados no stash. Não houve limpeza destrutiva. As 23 capas prévias do Site e o roteiro req100 também estão no terceiro pai do stash.

O humano informou que outro agente faria commit/push da autoria anterior. Stage de ambos os repositórios estava vazio na conferência. Em seguida confirmou responsabilidade desta missão por ambos os repositórios. Implementação isolada evita incluir a autoria anterior do Core.

## Validação da base

Destino `conn2flow-site-local` confirmado com `local=true`. Trava do Lab adquirida em `GIT/.c2f-lab-lock`.

Primeira tentativa interrompida na etapa 2: o `bash.exe` padrão era o launcher WSL e não reconhecia caminho Windows. Segunda tentativa falhou na leitura do projeto por `jq.exe` após desabilitar conversão MSYS globalmente. Execução corrigida com Git Bash no PATH, scripts `.sh` em LF e conversão MSYS normal; a biblioteca oficial controla as invocações rsync. Pipeline em andamento.

Etapa 1 reportou falha de minificação de `admin-categorias.js`; `node --check` passou. Investigar antes de encerrar o lote.

Base validada: pipeline completo com saída 0; CSS remoto analisou 7 recursos, regenerou 6, sem erros; manutenção desligada. A falha anterior de CSS vinha do wrapper `c2f` sem extensão em CRLF: corrigido em LF e protegido por `.gitattributes`. Os scripts `.sh` também passam a ser LF.

Partições do Site falharam por conversão CRLF no checkout: a normalização LF coincidiu exatamente com os dois hashes e tamanhos do manifesto original. Regra `gestor/db/data/*.part-*.json -text` impede a conversão no Core e Site. Nenhum checksum foi alterado manualmente.

Avisos da base: `admin-categorias.js` é arquivo vazio (0 bytes), portanto Terser gera saída vazia que o minificador rejeita; não há comportamento JavaScript a perder. Dez JS extras do catálogo 3D foram encontrados no destino pela conferência de hashes. Derivados da compilação ampla precisam ser separados do commit funcional.

Referências visuais conferidas: `admin-arquivos`, `admin-cron`, `admin-componentes`. Nova série preserva fundo azul profundo, base quadrada arredondada, cerâmica/vidro fosco e iluminação ciano/violeta. Prompts em `sdd/validation/req237-image-prompts.json`.

## Resultado técnico e ajustes autorizados no chat

33 novas capas (19 Core / 14 Site), WebP 1024×1024 abaixo de 100.000 bytes. As 23 capas anteriores do Site foram recuperadas do stash sem mudar seus bytes. Referências e os sete prompts extras estão em `sdd/validation/req237-extra-image-prompts.json`.

Dashboard: cabeçalhos M 13,2rem e G 17,5rem; imagem/ícone são links para o mesmo destino de Acessar, com alça fora do link. O humano substituiu o alinhamento inferior do briefing por topo centralizado com deslocamento de -20px. Implementado com `object-fit: cover`, `object-position: center -20px` e altura `calc(100% + 20px)`, sem faixa inferior vazia. Removida a escala duplicada da imagem; o link aplica a animação. Corrigida a substituição repetida do título/aria-label.

REQ-100 concluída no Site, relatório BATCH-094. A responsabilidade por ambos os repositórios foi autorizada no chat: as lacunas do Core foram resolvidas neste BATCH-246 (variante Tailwind da configuração, botão de histórico e parâmetro canônico `id` nas ações da listagem, inclusive PK numérica). Formulários e chaves CSRF preservados; nenhum pagamento real.

## Evidências finais

- Pipeline oficial sequencial no Lab: saída 0, manutenção desligada.
- Core: 532 testes JS; 1.594 testes PHP / 15.799 asserções, sem falhas ou erros. O runner retorna 1 por 4 depreciações, 3 depreciações PHPUnit e 4 skips já existentes.
- Site: 13 testes JS; 7 testes PHP / 1.022 asserções, aprovados.
- Dashboard: 17/17, cliques na imagem e SVG, drag com persistência, densidades, 390px; preferências originais restauradas.
- Site: 98/98, 30 telas, abas e eventos Stripe; create/edit com recarga de quatro registros, assinatura temporária e cinco limpezas verificadas.
- Inspeção visual dos screenshots de Dashboard, configuração e assinatura em desktop/390px. Nenhum erro AJAX/JS ou diálogo nativo.
- Integridade de todas as 70 capas e preservação dos arquivos públicos/portal verificados. Derivados de compilação foram produzidos pelo pipeline; não houve edição funcional de páginas públicas/portal.
- `git diff --check`: saída 0 nos dois repositórios; avisos de conversão LF/CRLF não são falhas de whitespace.

Inventário verificável: [req237-validation.json](../../validation/req237-validation.json); screenshots e checks em [Dashboard](../../validation/evidence-req237/dashboard.json) e no Site `sdd/validation/painel-tailwind/evidence-req100/req100-e2e.json`.

Revisão `review-current-batch`: bugs de dispatch AJAX, chave de rota numérica, histórico Fomantic e toggle após SVG corrigidos e revalidados. Sem findings bloqueantes restantes. Avisos pré-existentes de minificação vazia, recursos públicos sem bundle e JS extras registrados no inventário. Homologação humana permanece pendente.

## Consolidação

Commits de implementação: Core `3f77e621391606396b9f7a94a9b026f540ea5c70`, Site `ff695bcc0a6b45481c94067f0744fed53d5d8a51`, ambos em `feat/req-237-exec`. Stage listou 141 caminhos no Core e 298 no Site, incluindo derivados consistentes do pipeline e evidências; nenhum arquivo de ambiente/cookie. As árvores estão limpas após atualização do cache de arquivos sem diferença semântica de LF/CRLF.

Preservados os commits do outro agente `a26dfce8` e `8e7cb1c9`: a implementação Core foi reaplicada sobre essa base, com mudanças apenas de documentação entre as bases. Stash do Site mantido. Hashes e bases em [req237-commits.json](../../validation/req237-commits.json). Apenas o Lab recebeu atualização; revisão humana anterior à integração final permanece pendente.
