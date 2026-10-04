# BATCH-218: tela de atualização durante o deploy, prévia de widgets completa e retirada do módulo de apresentações (req-210)

Execução da [req-210](../human-requests/archive/req-210.md).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/manutencao.php` (novo) | Liga, desliga e lê `temp/maintenance.json`; isenções; idioma; tela, JSON e logo embutida |
| `gestor/gestor.php` | `manutencao_verificar()` antes da configuração, da sessão e do banco |
| `gestor/assets/global/global.js` | Aviso de atualização para `$.ajax` e `fetch`, com consulta até a manutenção terminar |
| `cli/src/Commands/ProjectUpdateAllCommand.php` | Liga a manutenção no destino antes das etapas e desliga no fim (`--no-maintenance` para não usar) |
| `gestor/controladores/api/api.php` | Deploy de projeto por API liga e desliga a manutenção |
| `ai-workspace/en/scripts/lib/project-transport.sh` | `rsync --chown` quando o destino usa `sudo` |
| `gestor/bibliotecas/html-editor.php` | `html_editor_widget_renderizar()` (CSS e variáveis, para as duas prévias) e `html_editor_widget_js_modulos()` |
| `gestor/assets/interface/html-editor-interface.js` | Marcadores de widget saem vazios no documento da prévia; lista de módulos vem do backend |
| Módulo de apresentações | Retirado: pasta do módulo, registro, permissão, documentação e testes |
| `gestor/db/migrations/20261002110001_create_cookie_consent_table.php` | Substitui a migração que criava as duas tabelas |

## Manutenção

- O estado é um arquivo com validade. Deploy que morre sem desligar não deixa o site fora do ar.
- A verificação roda antes de qualquer outra coisa do Gestor: durante o deploy, configuração, sessão e banco podem estar pela metade.
- A tela não usa arquivo nenhum do sistema: CSS e script em linha, logo como `data:` URI. A logo é `assets/manutencao/logo.(svg|png|webp)` do projeto ou, sem ela, a do Conn2Flow.
- A nova tentativa consulta o mesmo endereço e recarrega quando ele deixa de responder com a marca `X-C2F-Maintenance`.
- Os textos da tela ficam na biblioteca, e não nas variáveis do banco, que pode estar em migração.

## Causas dos erros 500 no deploy

Duas, medidas no Lab com uma sondagem por segundo durante o pipeline:

1. Requisição atendida com o sistema pela metade. A manutenção cobre.
2. `gestor.php: Permission denied`. Com `sudo rsync`, o arquivo nascia como `root` e só o `chown` do fim da etapa devolvia a posse; nesse intervalo o PHP-FPM não lia o arquivo e respondia 500 antes de qualquer código do sistema. `rsync --chown` grava já com o dono certo.

Depois das duas correções: 15 sondagens seguidas com 503 e a tela, nenhuma com 500.

## Defeitos achados na validação

- O arquivo de manutenção chegava ilegível ao destino quando o pipeline rodava no Windows: o `escapeshellarg` do PHP troca aspas e `%` por espaço. O conteúdo passou a ir em base64.
- Renomear a migração mantendo a versão deixou a cópia antiga duplicada no destino ("Duplicate migration"), porque o `rsync` não apaga. A migração renomeada recebeu versão nova.
- Módulo que sai do core deixa os `.min.js` antigos no destino, e o Gestor serve o minificado quando ele existe. No Lab foram removidos à mão.

## Validação

- `ManutencaoReq210Test` (9 testes) e `CookieConsentReq208Test`.
- Navegador no Lab, pelo roteiro do projeto: tela nos dois idiomas, logo, volta automática, JSON para AJAX, API isenta, aviso em página já aberta.
- Prévia do editor de páginas com widget de projeto: controlador carregado, sem imagem pedida com variável sem resolver.

## Limites

- A manutenção não entra na atualização do sistema feita pelo painel.
- O `SshRemoteTransport::buildRsyncCommand` do CLI (publicação de `dist/`) continua sem `--chown`.
- A parte da API de deploy foi coberta só por teste de unidade: o Lab estava em uso por outro lote e recebeu o `api.php` dele.
- Ambiente que já tinha a tabela `presentations` criada pela migração antiga continua com ela; nenhuma migração a remove.

## Pendências

- Revisão humana.
