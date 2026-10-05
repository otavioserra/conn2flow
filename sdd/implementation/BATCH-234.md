# BATCH-234 — Conferência da UI Tailwind (req-225)

**Estado:** `in-review`. **Data:** 2026-10-04. **Autonomia:** `autonomo_monitorado`.
**Intake:** [req-225](../human-requests/req-225.md).
**Core:** `conn2flow`, `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-req225`, branch `feat/req-225`.
**Site:** `conn2flow-site`, `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site-req225`, branch `feat/req-225`.

## Implementação integrada

O humano autorizou assumir a continuação de REQ-100/101 do site, consolidar as frentes anteriores e fazer commit/push em 2026-10-04. As alterações da req-223 foram consolidadas em `15c93b10` e as correções/revisão da req-224 em `e5dc99d3`, integradas nesta branch; as memórias das outras worktrees foram preservadas.

- Campos, botões, chaves e dicas padronizados em pt-br/en no cron, perfil e telas administrativas do core/site. Diálogos assíncronos preservam Tab/Escape e restauração de foco. Campos dinâmicos de produtos seguem a mesma biblioteca.
- Ícones legados preservam seus hooks e recebem SVG Lucide, inclusive após alterações dinâmicas; a fonte Fomantic deixou de ser carregada nos editores e módulos migrados.
- Formulários de configurações, inclusão/edição especiais e estado vazio de arquivos têm variantes Tailwind. Documentação de parâmetros do catálogo 3D também tem variante administrativa.
- O compilador resolve dependências primeiro no projeto e depois no core, somente em modo projeto e dentro das raízes permitidas. Bundles incluem dependências do layout, como barra superior, menu e seletor de imagens.
- O runtime disponibiliza o identificador do layout antes de executar o módulo. Isso permite ativar o bundle da página e evita estilos individuais sobrescrevendo as utilities responsivas.
- As chaves posicionam o input oculto dentro do próprio rótulo, evitando overflow do documento quando estão em tabelas com scroll interno.
- O manifesto de products declara a biblioteca html-editor para o despacho AJAX do editor de página. Gateways preservam os marcadores `<span>#select-...#</span>` exigidos pelo contrato de interface; quatro selects são conferidos no navegador, com os segredos mascarados. Botões dinâmicos de apresentações usam dicas e aria-label da biblioteca.
- Listagens AJAX de subscriptions-service-types/stages/status seguem o envelope de `interface`; controllers sem arquivo JS deixam de pedir um asset inexistente. A listagem de social-connections passou ao layout novo.
- Tailwind e CLI fixados em `4.3.3`, com lockfiles e derivados regenerados. Node instalado pelo humano: `24.21.0`; no PowerShell os testes usam Node diretamente ou npm.cmd.
- Transporte rsync do Windows usa caminho Cygwin para a origem e o SSH compatível do cwRsync. Launcher `c2f` preserva LF por atributo Git. Pipelines anteriores concluíram publicação dos assets e comparação de 763 arquivos de código; pipeline final concluído, manutenção desligada.
- Script de download das docs aceita links SVG e falha de resolução em srcdoc sem interromper os demais scripts. Templates pt-br/en corrigidos e derivados regenerados pelo `docs:build` oficial.

## Checklist vivo

- [x] Integração das frentes do core e REQ-100/101 do site em worktrees isoladas.
- [x] Correções, metadados/versionamento, compilação oficial e testes direcionados.
- [x] PHPUnit completo: 1.569 testes / 16.453 asserções; 4 skips e depreciações registradas.
- [x] Vitest completo: 526 testes; controles após última mudança: 17 testes.
- [x] Treze mocks independentes do site; Workspace Social: 9 JS e 3 PHP / 92 asserções.
- [x] Pipeline final após as correções da matriz e regeneração das docs.
- [x] Matriz final de todas as páginas pt-br/en, 1366/390 px, assets e console.
- [x] REQ-100/101, interações da req-219 e foco/modais da req-225 no navegador.
- [x] Inventário final, screenshots inspecionados, roteiro humano e revisão.
- [x] Limpeza das fixtures e teardown; seleção explícita dos caminhos para consolidação.

## Evidências reproduzíveis

- [Inventário](../validation/req225-inventory.md), [JSON](../validation/req225-inventory.json) e [gerador](../validation/req225-inventory.php): 384 páginas/idiomas, sem divergências estáticas. Runtime final: 376 aprovadas e oito excluídas por autorização humana, sem pendências.
- [Matriz de navegador](../validation/req225-matrix-browser.cjs) e [resultados](../validation/req225-matrix-evidence/results.json): 384 entradas consolidadas: 376 `passed`, oito `excluded`, nenhuma falha. A rodada completa encontrou quatro dicas nativas em páginas de apresentações; após a correção, apresentações e gateways foram novamente percorridos, preservando no relatório os demais resultados aprovados.
- [Foco e modais](../validation/req225-browser.cjs), com [resultados/capturas](../validation/req225-evidence/results.json): sete rotas em desktop/mobile e quatro modais, todas as verificações aprovadas; capturas inspecionadas pelo executor.
- Preparar dados somente no tenant isolado: `python sdd/validation/req225-lab-fixtures.py prepare`; ao terminar, `python sdd/validation/req225-lab-fixtures.py cleanup`. O script PHP recusa outro docroot/banco e não imprime credenciais. A matriz recebe somente identificadores no arquivo ignorado `temp/req225-fixtures.json`.
- Site: relatórios BATCH-094/095 e roteiros `sdd/validation/painel-tailwind/req100-e2e.cjs`, `req101-e2e.cjs`, `sdd/validation/core/req219-controles-e2e.cjs`.

## Validação final e revisão

- PHPUnit: 1.569 testes / 16.453 asserções, sem falhas; quatro skips, quatro depreciações PHP e três PHPUnit. Comando: `CONN2FLOW_SITE_ROOT=<worktree-site> php vendor/phpunit/phpunit/phpunit`.
- Detector de deriva: seis testes / 881 asserções, usando a worktree integrada do site em `CONN2FLOW_SITE_ROOT`. Sem essa variável, a worktree irmã compartilhada ainda contém migrações não consolidadas e não representa esta entrega.
- Vitest: 526 testes em 42 arquivos; controles: 17. `node node_modules/vitest/vitest.mjs run`.
- Site: REQ-100 123/123; REQ-101 101/101; REQ-219 40/40 (backup real e salvamento somente em página descartável). PHP apresentações 42/42 e mock de parcelas dos gateways aprovados após as últimas correções. Regressão de links SVG das docs aprovada em pt-br/en.
- Capturas do cron, modal mobile, Host Manager, catálogo 3D e gateways inspecionadas. Sem overflow do documento a 390 px; tabelas extensas usam scroll interno.
- [Revisão do executor](../reviews/REVIEW-2026-10-04-BATCH-234.md), sem findings abertos no escopo homologado. O roteiro solicitado em CA-4 foi registrado no site; aprovação humana não é declarada.

## Limites autorizados

Encerramento do Lab: 39 registros descartáveis removidos, incluindo a página E2E da req-219 e seus backups/histórico. O comando `env:set` resolve um `.env` local e não encontrou esse arquivo nesta worktree SSH; a configuração do tenant foi conferida diretamente, sem expor credenciais: `DEVELOPMENT_ENV=false`, mantido durante os testes. A manutenção foi desligada pelo pipeline. A entrega é pelas branches `feat/req-225` do core/site, preservando o trabalho concorrente no main compartilhado.

Consolidação verificada em 2026-10-05: core `5834b45c` e site `6cabb928`, publicados em `origin/feat/req-225` dos respectivos repositórios. O GitHub recusou a primeira versão do commit do site por `PaginasData.json` com 110.654.660 bytes; somente esse seed foi convertido para Git LFS, preservando seu SHA-256 e os bytes homologados (`git lfs fsck --objects` aprovado). A branch do site passou no push e seu SHA remoto foi conferido. Detalhes de materialização e arquivos ZIP constam no BATCH-094 do site. Fracionamento nativo dos seeds foi solicitado para análise pelo humano e não foi implementado nesta entrega.

`modulos-grupos-distribuido` é um piloto antigo. O humano confirmou: "É um piloto antigo; pode ficar fora desta homologação." As oito entradas ficam como `excluded`, com motivo explícito; seu canal distribuído não foi homologado.

O módulo `arquivos` do site foi separado pelo coordenador anterior na REQ-103, por calendário, progresso e contrato do seletor em iframe. Continua nessa frente e não é declarado migrado pelo BATCH-095. Host Manager é validado em leitura; não foram executadas ações de instalação/atualização de hosts nem pagamentos reais. Nenhum deploy de produção, alteração normativa de SPEC ou poda das memórias SDD.
