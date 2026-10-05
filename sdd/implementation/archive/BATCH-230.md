# BATCH-230 — Família publisher em Tailwind (req-221)

**Status:** `implemented-awaiting-review` (2026-10-04).
**Autonomia:** `autonomo_monitorado`.
**Projeto:** conn2flow, worktree `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-req221`, branch `feat/req-221`.

## Escopo aprovado

Migrar publisher, publisher-pages, publisher-highlights, publisher-index e pages-index, incluindo as listagens (BATCH-228 integrado), e as três variantes de controles/simulações publisher do editor. Preservar contratos de formulários, hooks, seletores e dados das prévias. Integrar o Workspace Social do site por variante para páginas Tailwind.

## Lista de execução

- [x] Leitura da req-221, CURRENT, workflow e referências; worktree isolada.
- [x] Páginas e componentes dos cinco módulos, nos dois idiomas, incluindo listagens.
- [x] Variantes globais publisher do editor e seleção restrita ao escopo.
- [x] Hooks preservados e variante do Workspace Social no site.
- [x] Contratos PHP, Vitest e suíte completa.
- [x] Pipeline sequencial com trava do Lab e roteiro Playwright desktop/390 px.
- [x] Evidências e roteiro humano no PENDENCIAS-HUMANAS.md do site.
- [ ] Revisão independente, teste humano e consolidação pela req-219.

## Coordenação

Os índices gerais permanecem sob responsabilidade do agente da req-219. BATCH-230 registra suas próprias evidências. As frentes trabalham em arquivos distintos na worktree; somente o executor principal executa pipeline, grava relatórios e consolida commits.

## Validação

| Verificação executada | Resultado |
| --- | --- |
| PHPUnit completo | 1.515 testes / 14.094 asserções; zero falhas. 4 depreciações, 3 depreciações PHPUnit e 4 skips existentes. |
| Contratos core REQ-221 após filtragem de artefatos | 10 testes / 1.543 asserções. |
| Vitest completo | 38 arquivos / 495 testes aprovados. |
| Site Vitest | 9 testes aprovados. |
| Site PHPUnit isolado | 3 testes / 92 asserções. |
| Pipeline oficial project:update-all conn2flow-site-local | Concluído; banco sincronizado, CSS final reconstruído, manutenção desligada. |
| Playwright no Lab | 439 verificações / 32 navegações autenticadas; zero falhas. |
| Fixture Highlights | Criada pela UI; editar/clonar/curadoria/salvar testados; remoção confirmada pela UI e ausência conferida. |
| git diff --check | Aprovado nos dois repositórios. |

Roteiro e evidências no site: `sdd/validation/core/req221-publisher-e2e.cjs`, `evidence-req221/report.json`, `cleanup-report.json` e screenshots selecionadas. O roteiro usa `C2F_CORE_ROOT` para apontar à worktree; por padrão usa o core irmão. `C2F_REQ221_CLEANUP=1` executa somente a limpeza da fixture de nome exato no Lab. Runtime validado em pt-br; en compilado e conferido por contratos.

No navegador foram conferidos listar/adicionar/editar/clonar, abas, selects por cliques reais, switches, campos/vínculos do Publisher, curadoria manual/automática, salvar e recarregar valores, modal social carregado/reaberto em desktop e 390 px. Telas migradas sem CSS/JS Fomantic no documento principal, modais legados soltos, overflow horizontal, diálogos nativos, erros de console ou 403. HTML de exemplos destinados ao iframe foi preservado.

As checagens locais completas foram `php vendor/bin/phpunit` e `node node_modules/vitest/vitest.mjs run`. No Windows, `OPENSSL_CONF=C:/Program Files/Git/usr/ssl/openssl.cnf` e normalização local LF de stripe.php/CssRebuildCommand.php resolveram dependências anteriores da suíte, sem diff semântico. No site: Vitest com `tests/vitest.req221.config.mjs` e PHPUnit com `--no-configuration tests/Unit/PHP/SocialWorkspaceTailwindReq221Test.php`.

## Comportamento entregue

Os cinco módulos usam layout/dependências Tailwind, mantendo nomes, IDs, marcadores, AJAX e contratos PHP. Os switches são labels nativos e respondem ao clique no trilho/texto. O Publisher tem autocomplete sem Fomantic, navegação por teclado e descarte dos listeners de linhas removidas. Schemas antigos sem template_map válido continuam renderizando e serializando os campos.

O Workspace Social reutiliza endpoints, CSRF e integração existentes. O adaptador fornece modal, carregamento, cards, geração, mídia, cópia e envio ao editor com templates do recurso; páginas legadas preservam a integração original. Os testes usam global.js real para CSRF e controles.js real para o modal. Publicação em redes sociais e geração paga não foram executadas; seus contratos foram testados por unidade.

## Coordenação e limites verificados

Core base `657344f1`; site base `f5e473bc`. Site: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site-req221`, branch `feat/req-221`. A req-101 do site recebeu nota para reutilizar a variante social. Os índices gerais SDD permanecem com o responsável da req-219.

O Lab foi protegido pela trava `.c2f-lab-lock`, com pipeline/navegação somente pelo executor principal. `conn2flow-site-local.local=true` foi conferido. Comando oficial: `bash ./c2f project:update-all conn2flow-site-local`, sequencial. Foi usado Git Bash no PATH; MSYS_NO_PATHCONV global quebra a resolução do jq. O launcher c2f precisou de LF na VM: uma tentativa inicial de CSS rebuild falhou por CRLF; duas rodadas posteriores concluíram a reconstrução. Nenhum deploy de produção ou integração em main.

O verificador encontrou seis JS compartilhados mais recentes no Lab, da req-219: controles.js/min, html-editor.js/min e interface-listar-tailwind.js/min. Foi confirmado que correspondiam ao trabalho em andamento no core original e foram preservados. Há dez assets antigos de 3d-catalog no destino. A req-219 deve manter suas correções ao consolidar.

O controle legado admin-templates continua com ponte Tailwind desativada. Seu overflow a 390 px (aproximadamente 290 px) pertence à frente BATCH-229. A simulação original de Highlights não está registrada no components.json da base, embora o arquivo exista, e não aparece nesse controle legado; a variante Tailwind nova está registrada e sua presença foi testada nas telas migradas. A req-219 deve avaliar o cadastro original ao tratar admin-templates.

O pipeline recompilou metadados fora da frente por CRLF. Antes dos commits, esses artefatos foram invertidos para os bytes/entradas HEAD, preservando somente 40 páginas, 8 componentes locais, 6 variantes globais, 4 variáveis novas e cinco JS/minificados do core; no site, componente/variáveis sociais. Os checksums preservados são os gerados pelo pipeline. Scripts de auditoria locais: `%TEMP%/req221-clean-generated.py` e `%TEMP%/req221-site-clean-generated.py`.

Saves dos registros existentes no Lab preservaram dados na recarga e acrescentaram versões de auditoria. A fixture desta frente foi removida; registros de outras frentes foram preservados.
