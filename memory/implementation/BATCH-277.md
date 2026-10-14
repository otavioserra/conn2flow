---
id: "BATCH-277"
title: "REQ-268: página com sem_permissao = 0 passa a exigir login"
status: "implemented-pending-homologation"
date: "2026-10-14"
author: "executor"
target_repo: "conn2flow"
summary_short: "Roteador e telas de edição tratavam sem_permissao = 0 como página pública; agora só 1 abre a página."
summary_medium: "Causa raiz da apresentação de investidor aberta a visitantes: existe('0') é verdadeiro.\nFunção pura gestor_pagina_sem_permissao() no roteador, no admin-paginas e no publisher-pages, com teste."
---

# BATCH-277 — REQ-268: página com `sem_permissao = 0` passa a exigir login

- Projeto: `conn2flow`
- Raiz: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- Branch: `feat/req-268`, criada da `main` (`017fe4b8`)
- Requisição: [REQ-268](../human-requests/req-268.md); coordenada com o site, REQ-124 / BATCH-118
- Topologia e autonomia: `dupla`, `autonomo_monitorado`

## Live Todo

- [x] Reproduzir: as duas apresentações de investidor respondiam 200 a visitante sem login.
- [x] Achar a causa no motor de páginas.
- [x] Corrigir no roteador e nas telas que leem a coluna, com teste.
- [x] Suítes do core.
- [x] Publicação no Lab e conferência HTTP (ver BATCH-118 do site).
- [ ] Homologação humana.

## Causa

No Lab, as páginas `apresentacao-investidor-mdd` (pt-br e en) já estavam com `sem_permissao = 0`, e mesmo assim abriam para qualquer visitante. O roteador (`gestor.php`) decidia com `!existe($paginas[0]['sem_permissao'])`. O banco devolve a coluna como texto, e `existe('0')` é verdadeiro (string não vazia): `0` era tratado como página pública. Só `NULL` exigia login.

O sitemap já usava o critério certo (`empty()`, e `sem_permissao=1` na consulta). O `admin-paginas` e o `publisher-pages` tinham o mesmo erro na leitura (`isset($retorno_bd['sem_permissao'])`): abrir a página no editor mostrava "sem permissão" marcado, e salvar a reabriria ao público.

Impacto medido antes de mudar: nos bancos do Lab (`admin_conn2flow`, `meusit41_c2f`, `transf3_c2f`), só as duas páginas de investidor têm `sem_permissao = 0`. Todas as demais têm `1` (públicas) ou `NULL` (restritas), que não mudam de comportamento.

## Correção

| Arquivo | Mudança |
| --- | --- |
| `gestor/bibliotecas/gestor.php` | `gestor_pagina_sem_permissao($valor)`, função pura: só `1` (texto, inteiro ou `true`) abre a página. |
| `gestor/gestor.php` | Checagem de permissão da página usa a função. Visitante sem login segue o fluxo existente: `gestor_permissao()` grava `redirecionar-local` e manda para `/signin/`; robô recebe só o `<head>`. |
| `gestor/modulos/admin-paginas/admin-paginas.php`, `gestor/modulos/publisher-pages/publisher-pages.php` | Leitura da caixa "sem permissão" pela função (editar e clonar). A gravação já usava `1`/`NULL`. |
| `tests/Unit/PHP/GestorPaginaSemPermissaoTest.php` | 13 casos de valor, mais guardas de fonte no roteador e nas duas telas. |

Página sem módulo continua aceitando qualquer usuário logado. A exigência de perfil para apresentações restritas está no módulo `presentations` do site (BATCH-118).

## Validação

- Foco: `GestorPaginaSemPermissaoTest` + `GestorRoteadorErroTerminalTest`, 29 testes, 31 asserções, OK.
- PHPUnit completo (Windows, `OPENSSL_CONF` exportado): 1.776 testes, 20.753 asserções; 3 erros e 1 falha, todos anteriores a este lote e alheios a ele:
  - `StripePaymentIntentInstallmentsTest`, `StripeAssinaturaCupomTest`, `StripeAssinaturaItensAvulsosTest`: recortam o fonte por `"\n}\n"` e o `gestor/bibliotecas/stripe.php` deste checkout está com CRLF (armadilha já registrada na memória de execução).
  - `MemoryDualReq266Test::testSyncRequiresAll44SkillsAcrossTheFiveKits`: espera 44 skills; desde a REQ-072 o catálogo tem 45. Expectativa desatualizada na `main`.
- Vitest: 60 arquivos, 664 testes, OK.
- `php -l` em `gestor/gestor.php` e `gestor/bibliotecas/gestor.php`: sem erros.
- Lab (`https://conn2flow.local`): visitante recebe 302 para `/signin` nas duas apresentações de investidor; administrador, 200 com 14 slides; `/apresentacoes/conn2flow/` segue 200; no `admin-paginas`, a página de investidor aparece com "sem permissão" desmarcado. Hash dos quatro arquivos do core no Lab igual à origem. Detalhes no BATCH-118 do site (commit `13189941`, branch `feat/req-124`).

## Limites

- Os arquivos que a apresentação usa (`/presentation/investor-mdd/*.png`, `*.webp`, fontes) são entregues pelo controlador `arquivo-estatico` antes da sessão e continuam abertos a quem souber o endereço. O Engenheiro Chefe conferiu em 2026-10-14 que essas imagens não têm dado confidencial: os valores estão só no HTML do deck, protegido. Fica como observação técnica: página restrita não protege arquivos de `contents/`.
- Produção não foi tocada.
