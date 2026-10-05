# BATCH-245 — Integração Tailwind e Dashboard V3.1

- Requisição: [req-236](../human-requests/req-236.md).
- Status: in-review.
- Repositório: `conn2flow`, `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`.
- Branch de implementação: `feat/req-236`; integrações sequenciais realizadas em `main`.
- Site associado: `conn2flow-site`, worktree `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site-req101`, branch `feat/req-101`.

## Live Todo List

- [x] Preservar alterações locais rastreadas em stash antes das integrações.
- [x] Mesclar `feat/req-222`, `feat/req-223` e `feat/req-224`, preservando registros dos lotes.
- [x] Título acima das abas e ações agrupadas em Opções.
- [x] Tooltip bottom left, sem clipping e z-index 100030.
- [x] Capa única, alturas M 11rem e G 14.5rem.
- [x] Modo de edição, quatro cantos de resize e seleção tipo → registro com instâncias independentes.
- [x] Testes JavaScript e PHPUnit.
- [x] Migração dos módulos privados no worktree do Site.
- [x] Pipeline completo no Lab.
- [x] Auditoria SQL/CSS e homologação em desktop e 390px.
- [x] Revisão final e evidências consolidadas.

## Implementação e verificação

Integrações em main: req-222 `da56f3c9`, req-223 `db5f3b77`, req-224 mesclada em seguida. Os conflitos em manifests e registros SDD preservaram os módulos e lotes das branches. O stash anterior permanece guardado e não foi reaplicado.

Dashboard mantém geometria 4/6/8/12 colunas e alturas 1/2. Instâncias persistem `id`, `registro_id`, `params`, `instance_id`, `width` e `height`; configurar ou remover uma instância não afeta outra do mesmo tipo. O backend valida o cadastro ativo e o registro no idioma atual antes de montar a assinatura canônica do renderer. Erros de renderização aparecem como erro, sem simular sucesso.

Corrigidos metadados herdados com motivos de fontes/dependências ausentes e referências erradas aos modais Tailwind. A compilação de projetos resolve componentes globais ausentes no projeto diretamente na autoria do Core, sem cópia manual; dependências locais continuam locais.

- Vitest: **529 testes em 44 arquivos**, todos aprovados. Quatro testes de instâncias e os testes de conversão dinâmica de ícones falham contra o código anterior, confirmando controles negativos.
- PHPUnit Core, Linux/PHP 8.5.10: **1.594 testes, 15.795 assertions**, sem falhas ou erros; quatro skipped, quatro deprecações e três deprecações do PHPUnit.
- PHPUnit Site: **5 testes, 462 assertions**, todos aprovados; Vitest Site **9/9**.
- Inventário estático: **386 páginas/idiomas Tailwind, zero linhas com violações**, em `sdd/validation/req236-source-inventory.json`. Este inventário não substitui a inspeção do SQL e do navegador.
- A primeira execução PHPUnit consultou o checkout original do Site, que foi preservado: repetição usa `CONN2FLOW_SITE_ROOT` apontando ao worktree. O transporte Bash foi normalizado localmente para LF; conteúdo Git inalterado.

## Homologação e revisão

Pipeline oficial completo concluído no Lab, sequencialmente, com CSS reconstruído e manutenção desligada. A origem temporária do Site no `environment.json` ignorado foi restaurada após os comandos do Lab; o checkout original do Site e o stash do Core foram preservados.

- Dashboard: **17/17**, incluindo capas reais 176/232px, tooltip, edição, resize por mouse, duas instâncias persistidas, documento isolado do widget e 390px. [Resultados](../validation/evidence-req236/dashboard.json).
- Site: **88/88**, 29 telas em desktop/390px, sem assets Fomantic ou erros de script. Cinco registros temporários e sua página associada foram criados por formulários com CSRF e removidos pelos controles oficiais; preferências do Dashboard restauradas.
- SQL: **316 páginas administrativas, zero resíduos visuais**; páginas/layouts sem resíduos. [Auditoria completa agregada](../validation/req236-css-report.json) registra também **160 resíduos em componentes e dois em templates**, além de proveniência stale e classes sem regra do acervo. A auditoria global não está integralmente limpa.

Revisão findings-first corrigiu isolamento CSS/JS dos widgets com iframe, seletores do editor, fonte de ícones Fomantic, CSV de campos com espaços que omitia rótulos, lista legada do histórico e marcadores/rótulos não resolvidos em Apps Sociais. O roteiro verifica também texto visível não resolvido e seleciona explicitamente a aba antes de medir capas. Os 14 módulos de autoria REQ-100 presentes no checkout original foram preservados no worktree para evitar regressão do Lab; sua homologação funcional completa não faz parte deste lote.

Limites: runtime homologado em pt-br; en validado por contratos/compilação. OAuth, publicação real, pagamentos e atualizações de tenants não foram acionados. A verificação de distribuição registra arquivos de destino mais recentes/diferentes e dez sobras 3D, sem removê-los. O minificador avisa que `admin-categorias.js` está vazio. Registros legados de widgets sem alvo precisam ser configurados pela seleção em dois níveis.

Revisão humana e consolidação final permanecem pendentes. Integração req-224 em main: `12bc742c`.
