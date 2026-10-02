# BATCH-222: menus do painel com o módulo atual à vista e apoio do core à execução no cliente (req-214)

Execução da [req-214](../human-requests/req-214.md). Relacionado à REQ-095 do `conn2flow-site`, onde está o relatório da execução dos módulos no cliente (`sdd/implementation/modulos-distribuidos/batch-089-execucao-no-cliente-catalogo-3d-e-ajustes.md`).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/gestor.php` | O item do módulo da página sai com `aria-current="page"`, igual nos dois frameworks |
| `gestor/assets/global/global.js` | `gestorMenuPosicionarAtual()`: marca o item atual e rola o menu até ele ficar no meio; a rolagem guardada da visita anterior só vale em página que não é de nenhum item |
| `gestor/bibliotecas/cookie-consent.php` (novo), `gestor/config.php` | `cookie_consent_estado()` e `cookie_consent_permitido($categoria)`: a decisão de cookies lida no servidor; filtro `cookie-consent` / `permitido` |
| `gestor/bibliotecas/modulo-distribuido-protocolo.php` | `modulo_distribuido_contexto()` e `modulo_distribuido_url_publica()`: o módulo pergunta se está rodando em nome de uma instalação e qual é o site dela |
| `gestor/controladores/api/api-module-distributed.php` | Depois de uma escrita vinda do Central, o cliente dispara o gancho `modulo-distribuido` / `db.escrita` (módulo, operação, tabelas) |

## Menus

O servidor marcava o item só quando o identificador do módulo da página era o do item, e nada posicionava o menu: a posição era a da última rolagem guardada. Agora:

- vale para o menu do layout Fomantic (computador e celular) e para o do layout Tailwind;
- quando o servidor não marca (página de módulo que não é o do item, menu de projeto sem a marca), o item é achado pelo endereço: o `href` que for o prefixo mais longo do caminho atual;
- o item fica no meio da área visível; no começo e no fim da lista o menu não tem como centralizar, e o item fica visível.

## Ganchos de consentimento

Um módulo de projeto condiciona um cookie gravado no servidor à permissão do visitante com `cookie_consent_permitido('<categoria>')`. Sem decisão, só `necessary` é permitido. No navegador o aviso já oferecia `window.c2fConsent.has()` e o evento `c2f:consent`. Primeiro uso: o cookie de indicação dos associados, no `conn2flow-site`.

## Defeitos achados na validação

- A biblioteca nova não estava no mapa de bibliotecas do `config.php`: `gestor_incluir_biblioteca('cookie-consent')` não carregava nada, e o teste de unidade, com a inclusão simulada, passava. Teste do registro acrescentado.
- O posicionamento do menu quebrava os testes que carregam o `global.js` num documento simulado sem `addEventListener`. O acréscimo passou a se proteger.
- O filtro de permissão derrubava a leitura quando o gerenciador de ganchos estava sem banco (aparecia só em ordem aleatória de testes). Falha de ouvinte não muda a resposta lida do cookie.

## Validação

- `CookieConsentServidorReq214Test`, `ModuloDistribuidoReq092Test`; suíte completa 1.435 testes, 1 falha anterior ao lote, igual em ordem aleatória; 461 testes JS.
- Navegador no Lab, roteiro em `conn2flow-site/sdd/validation/website/req214-menus-do-painel.cjs`: 61/61, em 14 páginas, duas alturas de janela, os dois layouts.

## Limites

- O cookie de decisão é lido sem conferir a versão do aviso: quem decidiu numa versão antiga continua com a decisão antiga no servidor até decidir de novo no navegador.
- O menu de celular do layout Fomantic não foi exercitado no roteiro.

## Pendências

- Revisão humana.
