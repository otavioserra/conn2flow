# Validation Checklist

## BATCH-295 — REQ-286

- [x] Artefatos de sistema e `config.php` do core sempre escritos; sobreposto de artefato com choque automático (testes).
- [x] Divergência nunca decidida vira choque; choque pendente não se repete; disco com a versão da camada de baixo é reescrito (testes).
- [x] CRLF não é edição; `db/.c2f-migrations-*` fora do manifesto (testes).
- [x] Superados saem sozinhos; lote passa pelo motor, pula o que não aceita e informa falhas (arnês SQLite).
- [x] Tela: pendentes por padrão, paginação, lote com confirmação, aviso de retirada; variáveis pt-br/en.
- [x] utf8mb4 na tabela de choques; INSERT que falha fica pendente e vai para o log.
- [x] PHPUnit 1.905 (2 falhas de ambiente), Vitest 700/700; simulação no Lab e na produção (só leitura).
- [x] Release 3.0.12 aplicada na produção (Engenheiro Chefe); formulário de contato ok.
- [x] Rodada 2: `.min.js` em dia com trava no `composer test`; aba de choques sem recarregar; `wait_timeout` no deploy pela API.
- [ ] Próxima release: aba de choques navegando sem recarregar e lote resolvendo os 115 pendentes da produção.
- [ ] Homologação humana.

Relatório: [BATCH-295](../implementation/BATCH-295.md).

---

## BATCH-294 — REQ-285

- [x] Causa do `Falha finalize` com dados reais: POST do finalize com HTTP 404 (página `admin-atualizacoes/` desativada pela retirada durante a etapa de banco).
- [x] Etapa de banco lê os dados entregues pelo core quando `db/data/` tem os do projeto; conferido contra a instalação real do Lab (274 páginas do core, sem o particionado).
- [x] Nada sai sem confirmação (DEC-136): registro e arquivo viram choque `retirado`; `--confirmar-retirada` remove direto. Testes novos verdes, incidente reproduzido em teste.
- [x] `finalize` com `atualizacoes_web_recursos()` e liberação da trava no fatal; tela mostra o HTTP das falhas.
- [x] Suítes do atualizador 234/4.783 verdes; PHPUnit completo só com as 2 falhas de ambiente do Windows (OpenSSL e CRLF do `api.php`); Vitest 700/700.
- [x] Lab reparado pelo dump do snapshot (1.069 status de volta, 3.568 linhas reinseridas, manifesto de recursos do core regravado); painel responde de novo.
- [x] Release 3.0.10 e atualização do Lab pelo painel concluindo o `finalize` (critério 2 da REQ-285), relatada pelo Engenheiro Chefe.
- [x] Rodada 2: deploy de projeto com HTTP 500 (fatal de memória no registro de 298 choques). Registro em fluxo e antes do manifesto, 1 GB na etapa de arquivos, zip enviado fora da entrega; 3 testes novos; manifesto do projeto no Lab restaurado.
- [x] Release 3.0.11 e `project:deploy conn2flow-site-local` sem erro (Engenheiro Chefe).
- [x] Rodada 3: falsos positivos do Lab saneados com aprovação (2 do core sobrescritos, 1.367 superados, 316 do projeto entregues no deploy); selo em 3.0.11, `api-crud` registrado, projeto 1.443/1.443 e core idênticos às entregas.
- [ ] Produção: release 3.0.11 e deploy do projeto (Engenheiro Chefe).
- [ ] Homologação humana.

Relatório: [BATCH-294](../implementation/BATCH-294.md).

---

## BATCH-292 — REQ-283 (Fase 1)

- [x] Motor responde às sete rotas para módulo com `api.crud`; sem `api.crud` ou inativo, 404 como antes.
- [x] PAT e OAuth pelo `api_authenticate`; escopo (read/write/mobile) e RBAC `gestor_acesso` em todas as rotas.
- [x] Envelope `status`, `message`, `data`, `timestamp`; códigos 200, 201, 400, 401, 403, 404, 405 e 409 exercitados.
- [x] `ApiCrudReq283Test` 13/13; PHPUnit completo 1.873 sem falhas (rodada intermitente registrada no lote).
- [x] Lab: `project:sync-core conn2flow-site-local --skip-css` saída 0, hashes conferidos; E2E HTTP 22/22; PATs revogados.
- [x] Revisão independente (tríade): [REV-001](../human-reviews/rev-001.md), **RECOMMEND-REVISION** em 2026-10-19. F1–F4 abertos: acesso base ao módulo, falhas de persistência, corrida de versão e mensagens sem i18n. Testes dirigidos 13/13; reproduções isoladas anexas à ficha.
- [x] Rodada 2 (Executor, 2026-10-19): F1–F4 corrigidos. `ApiCrudReq283Test` 20/20 (311 asserções, inclui RBAC com `gestor_acesso` real, falha de escrita, interleaving de versão e pt-br/en); PHPUnit completo 1.880, 0 falhas, 9 skips; [sondas r2](../human-reviews/evidence/req-283-probes-r2.jsonl) com saída 0.
- [ ] Rodada 2 no Lab: variáveis `api-crud-*` no banco e E2E HTTP repetido (não executado nesta rodada).
- [x] Nova revisão independente da rodada 2: [REV-002](../human-reviews/rev-002.md), **RECOMMEND-REVISION** por falta de validação integrada r2 (R2-V1). F1–F4 corrigidos no alcance isolado; Revisor executou CRUD 20/311 e módulos distribuídos 43/180 (testes/asserções), sondas r2 e getter real com 52 variáveis. DEC-135 aceita; G3/G4 preservados.
- [ ] Homologação humana.

Relatório: [BATCH-292](../implementation/BATCH-292.md).

---

## BATCH-285 — REQ-276

- [x] Falha do teste existente reproduzida antes da mudança.
- [x] Dois módulos usam confirmação padrão assíncrona; mensagem traduzida e salvamento normal preservados.
- [x] Cancelar não altera campo nem envia; confirmar envia uma vez, com token, também no fallback.
- [x] Vitest dirigido 23/23; completo 700/700 em 63 arquivos.
- [x] Versões dos módulos incrementadas; dois minificados e manifesto gerados oficialmente; sintaxe aprovada.
- [x] Auto-revisão e handoff com escopo e limites em [BATCH-285](../implementation/BATCH-285.md).
- [ ] Homologação humana.

Publicação pelo próximo agente. Aviso global de minificação em admin-categorias, fora do diff, registrado no lote.

---

## BATCH-282 — REQ-273

- [x] Tipo domain nos seis editores, variáveis pt-br/en e placeholder traduzido.
- [x] Widget renderiza text, autocapitalize=none, spellcheck=false e autocomplete=off.
- [x] Sanitização no input/change e submit; FQDN e limites de 63/253 caracteres.
- [x] PHP normaliza $_POST antes da validação/persistência e rejeita valores não escalares.
- [x] Prompts e mensagens de erro traduzidos; teste do campo restaurado no DOM.
- [x] PHPUnit completo via test:run: 1.795 testes, zero falhas, 5 skips e depreciações registradas.
- [x] Vitest completo: 686 aprovados; lint e diff sem erros.
- [x] Compilação oficial dos oito recursos e minificados dos dois scripts alterados.
- [x] Publicação no destino correto: project:update-all conn2flow-site-local saída 0; SQL, CSS e dist atualizados.
- [x] URL editar/form-pro-acceleration, adicionar e clonar com HTTP 200 e opção Domínio; widget preview com os atributos corretos; desktop/mobile sem overflow e zero pageerror.
- [ ] Persistência via UI e submissão AJAX real no ambiente publicado.
- [ ] Homologação humana.

Evidências e limitações: [BATCH-282](../implementation/BATCH-282.md).

---

## BATCH-280 — REQ-271

- [x] `pagamentos_com_trava()` em `gestor/bibliotecas/stripe.php`; `gestor/bibliotecas/pagamentos.php` removido com `git rm`.
- [x] Fixture REQ-265 carrega `gestor_incluir_biblioteca('stripe')`; `PaymentsReq265Test` OK.
- [x] PHPUnit completo no `lab`: 1.788 testes, 1 falha anterior e alheia (`MemoryDualReq266Test`, 41/45 skills).
- [x] Lab: `project:update-all conn2flow-site-local --skip-css` saída 0; `stripe.php` publicado define a função; sem novo fatal no `php-error.log`.
- [x] Sobra remota `bibliotecas/pagamentos.php` removida e `public_html/dist` em `755 admin:www-data` (via `ssh lab`).
- [x] Homologação assinada em 14/10/2026 pelo agente, por instrução expressa do Humano.

---

## BATCH-278 — REQ-269

- [x] `project:update-all <id> --skip-css`: saída 0 em 36 s no Lab, etapas 2, 4 e 7 puladas com aviso.
- [x] Sem a flag: pipeline completo (Tailwind e `css:rebuild`) em 40 s, saída 0.
- [x] `manager:update-all --skip-css` aceita a flag; falha nas etapas 3 e 4 por falta de Docker no host, igual sem a flag.
- [x] `PipelineSkipCssReq269Test` 6/6; `composer test` 1.788 testes saída 0; Vitest 664; `git diff --check` limpo.
- [ ] Homologação humana.

---

## BATCH-277 — REQ-268

- [x] Causa reproduzida: `existe('0')` verdadeiro abria páginas com `sem_permissao = 0`.
- [x] `gestor_pagina_sem_permissao()` no roteador e na leitura do `admin-paginas` e do `publisher-pages`; só as duas páginas de investidor tinham `0` no Lab.
- [x] `GestorPaginaSemPermissaoTest` + `GestorRoteadorErroTerminalTest`: 29 testes OK; Vitest 664 OK.
- [x] PHPUnit completo: 1.776 testes; 4 falhas anteriores e alheias registradas no [BATCH-277](../implementation/BATCH-277.md).
- [x] Lab: visitante 302 para login, administrador 200; editor de páginas com a caixa certa (evidências no BATCH-118 do site).
- [x] Arquivos de `contents/` de página restrita seguem abertos; no caso das apresentações de investidor, sem dado confidencial (conferido pelo Engenheiro Chefe).
- [ ] Homologação humana.

---

## BATCH-275 — REQ-266

- [x] Testes REQ-225/256 e SddSource priorizam memory/ com fallback sdd/; corpus real continua filtrado.
- [x] CLI seleciona a árvore do --repo e conserva aliases antigos; ai:archive-memory, memory:archive e memory:prune executados.
- [x] Arquivamento real com reparo de links em fixtures memory/, sdd/ e ambas; dry-run Core e boilerplate legado com exit 0.
- [x] Três contratos corrigidos na fonte canônica e propagados oficialmente aos cinco kits; ai:sync exige 44/44 e mostra ✔ Complete.
- [x] PHPUnit completo: 1.768 testes/20.735 asserções, exit 0; 4 skips e depreciações registrados. Teste adicional do catálogo: 5 testes/58 asserções no foco final.
- [x] Vitest: 60 arquivos/664 testes, exit 0; mensagens do Happy DOM registradas no lote.
- [x] Memória encontrada (38.451 bytes/252 linhas), exit 0 com alerta preventivo; README atualizado e git diff --check limpo.
- [x] Revisão própria e limites no [BATCH-275](../implementation/BATCH-275.md), incluindo a checagem adicional de links do Site e a fonte canônica local na matriz.
- [ ] Homologação humana global.

---

## BATCH-274 — REQ-265

- [x] Trava/transação e aliases financeiros; marca e logo resolvidos; falhas de webhook recebem 401/503.
- [x] Recursos sincronizados, 1.763 testes PHP e 664 JS aprovados; pipeline completo publicado no Lab.
- [x] Logo com CID e arquivo absoluto existente; telas com duas cobranças e histórico recuperado.
- [x] Nova arquitetura de eventos e unificação registradas em backlog, sem nova tabela nesta entrega.
- [x] Evidências e limites no [BATCH-274](../implementation/archive/original/BATCH-274.md); revisão e arquivos explícitos.
- [ ] Homologação humana global.

---

## BATCH-273 — REQ-264

- [x] Requisitos implementados e revisados, inclusive adendos humanos.
- [x] Compilação oficial, suítes PHP/JavaScript completas e publicação no Lab.
- [x] Navegador desktop/mobile, sem overflow/erros JS nos alvos.
- [x] Evidências, avisos herdados e limites registrados; arquivos específicos no commit.
- [ ] Homologação humana global.

Detalhes: [BATCH-273](../implementation/archive/original/BATCH-273.md).

---

## BATCH-272 — REQ-263

- [x] A busca da página pelo caminho usa `idx_paginas_language_caminho` e examina 1 linha (antes, 1.061).
- [x] Zero leitura de disco no banco por visita (antes, 4.356).
- [x] Primeiro byte logo após publicar: de 414 a 548 ms para 59 a 62 ms, nos dois ambientes de teste.
- [x] PHPUnit 1.757.
- [ ] Medição em produção depois de publicar; conferir a memória do banco lá.
- [ ] Homologação humana.

Detalhes: [BATCH-272](../implementation/archive/original/BATCH-272.md).

---

## BATCH-276 / REQ-267 — capas isométricas

- [x] WebP 1024x1024, 60.000–100.000 bytes; SHA-256 e bytes conferidos nos dois manifestos.
- [x] Compilação resources:sync e pipelines oficiais: saída 0.
- [x] HTTP/hash dos oito assets e seis cards autenticados sem fallback; 19/19 por ambiente; desktop/mobile sem overflow ou erro de console.
- [x] Distinção registrada: arquivos-api/interface não têm cards neste perfil, mas suas capas são servidas corretamente.
- [ ] Homologação humana.

Relatório: [BATCH-276](../implementation/BATCH-276.md). Evidências coordenadas no Core: memory/validation/req267-delivery.json.

[Archived history](<archive/compacted/VALIDATION-CHECKLIST-106a0e880f1d.md>)
