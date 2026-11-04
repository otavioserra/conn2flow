---
id: "BATCH-306"
title: "REQ-298: equipe com perfis no padrão de usuarios-perfis, fim do vazamento de atalhos, rota inicial do perfil e mobile"
status: "implemented-pending-review"
date: "2026-11-03"
author: "executor"
target_repo: "conn2flow"
summary_short: "Recusa leva à primeira página do perfil (sem laço), atalhos fixos de layout por módulo, Dashboard só com permissão e perfis de equipe em cartões."
summary_medium: "Core e lumix publicados juntos no snapphoton.local; E2E 16/16 em 1280 e 390 px.\nPHPUnit focado 81/81; a suíte completa tem 22 falhas de ambiente (CRLF e openssl do Windows), nenhuma nos arquivos do lote."
request: "REQ-298"
topology: "solo"
autonomy: "autonomo_monitorado"
---

# BATCH-306 — Equipe, permissões de sub-usuário, rota inicial e mobile (REQ-298)

Branch `main`, sem commit (aguarda revisão do Arquiteto-Chefe). Coordenado com o BATCH-267 do `lumix` (REQ-119).

## Diagnóstico

| Sintoma relatado | Causa encontrada |
| --- | --- |
| Membro com Agenda + Anamnese via Busca Clínica, Minha Assinatura, Configurações etc. | O menu montado pelo perfil (`gestor_pagina_menu`) já estava certo: só agenda, anamnese e perfil-usuario. Os itens vinham de **atalhos fixos do layout `photon-admin`** do `lumix` (logo → busca-clinica, engrenagem → user-settings, dropdown com assinatura e preferências, barra inferior). O acesso direto já era recusado no servidor. |
| Erro de permissão no primeiro login e "continua acusando erro" | Toda recusa redirecionava para `dashboard/`; para perfil sem o painel, o painel respondia 403 "Acesso ao painel recusado". |
| Laço em `/dashboard/` com perfil só Dashboard | Hook `dashboard_start_hook` do `lumix` mandava todo não-admin para `busca-clinica/`, que recusava e voltava ao painel (8 redirecionamentos medidos). |
| `/equipe/` desktop no celular | Tabelas sem contenção de rolagem e cabeçalho/painel sem ajuste; o menu do layout do core já colapsa (confirmado em 390 px). |

## Entregue no core

- `gestor_permissao_rota_inicial_escolher()` (pura, `bibliotecas/gestor.php`) e `gestor_permissao_rota_inicial()` (`gestor.php`): primeira página raiz permitida, na ordem do menu, respeitando `gestor_perfil_permissao_sql()` (membro ∩ titular). `perfil-usuario` só como último recurso; a página cujo caminho é o próprio módulo vence as demais (`anamnese/` e não `anamnese-paciente/`).
- `gestor_permissao()`: recusa do painel vai para a rota inicial (sem alerta); recusa de módulo vai para a rota inicial; 403 só sem rota ou quando o destino seria a própria rota recusada.
- `gestor_pagina_blocos_por_modulo()`: blocos `<!-- c2f-modulo:ID < -->…<!-- c2f-modulo:ID > -->` no layout saem quando o usuário não abre o módulo. Resolvido em `gestor_pagina_variaveis()`, uma consulta por módulo.
- Menu: o Dashboard aparece sempre para conta própria (como antes) e, para sub-usuário, só com a permissão.
- `/equipe/` (pt-br e en): aba Perfis em cartões por grupo de módulo (ordem do menu), chave `c2fc-chave` por módulo, operações dentro do cartão, "Marcar todos/Desmarcar todos" por grupo; operação desabilitada com o módulo desligado. Selects com `data-c2f-select`, abas com ícones, avisos e estados vazios em cartão, botão "Adicionar Membro" fora da borda das abas (painel `c2fc-painel-aba`), tabelas com rolagem contida.
- Variáveis novas: `team-select-all`, `team-unselect-all`, `team-group-other` (pt-br/en). `equipe.json` 1.0.1, páginas 1.2, `equipe.min.js` regenerado.

## Validação

- **PHPUnit focado**: `EquipePermissoesReq298Test` (novo, 12 casos), `GestorPainelSemLacoReq131Test` (ajustado ao destino variável) e `UsuarioHierarquiaReq297Test`: 81 testes, 94 asserções, sem falhas.
- **PHPUnit completo**: 1.984 testes, 21 falhas e 1 erro, todos de ambiente: testes que procuram `"\n"` em `dashboard.php`/`api.php` com checkout CRLF (Dashboard Req247/251/258, Manutencao Req210) e RSA do `openssl.cnf` (CoreHelpersTest). Nenhum toca arquivo deste lote.
- **Publicação**: `project:update-all snapphoton-local` saída 0 (core e projeto mudaram juntos) e `css:rebuild --project=snapphoton-local --confirmar-remoto` sem erros.
- **HTTP com sessões reais** (`c2f auth:cookie --user`): membro 45 (Agenda + Anamnese): `/dashboard/`, `/busca-clinica/`, `/user-subscription/`, `/user-settings/`, `/calculadora/` → 302 para `/agenda/`, um salto; agenda e anamnese 200. Membro 44 (só Dashboard): `/dashboard/` 200 sem redirecionar. Titular: dashboard, equipe, pacientes e meus-equipamentos 200.
- **Navegador** (`lumix/tests/e2e/req119-homologation-lab-browser.cjs`): 16/16 em 1280 e 390 px, sem erro de JavaScript, incluindo perfis em cartões, espaçamento, selects 3.0, menu colapsado e menu do membro sem atalhos vazados.

## Limites e pendências

- A raiz `/` do snapphoton é a página pública do site; não foi alterada. O redirecionamento automático vale para o painel (`/dashboard/` e qualquer recusa), que é o destino do login.
- O perfil `administradores` nunca teve `user-subscription`: o atalho "Minha Assinatura" sumiu também para ele (antes levava a uma recusa). Assinantes (`academico`) continuam vendo.
- Em 390 px a tabela de membros rola dentro da própria caixa (rolagem contida, sem rolagem da página); não virou cartões.
- Os testes da suíte que falham por CRLF continuam falhando neste checkout (não é regressão).

## Handoff

Projeto `conn2flow`, raiz `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`, REQ-298, BATCH-306, topologia solo, autonomia `autonomo_monitorado`, sem commit. Arquivos: `gestor/gestor.php`, `gestor/bibliotecas/gestor.php`, `gestor/modulos/equipe/{equipe.php,equipe.js,equipe.min.js,equipe.json}`, `gestor/modulos/equipe/resources/{pt-br,en}/pages/equipe/equipe.{html,precompiled.css}`, `tests/Unit/PHP/EquipePermissoesReq298Test.php`, `tests/Unit/PHP/GestorPainelSemLacoReq131Test.php`, mais os derivados do pipeline (`gestor/db/data/{ModulosData,PaginasData,VariaveisData,schema-metadata}.json`, `gestor/assets/{asset-versions,minify-manifest}.json`, `gestor/resources/.tailwind-build-manifest.json`). Próxima ação: revisão do Arquiteto-Chefe e homologação humana no `snapphoton.local`.
