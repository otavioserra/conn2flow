# BATCH-225: módulos distribuídos — confirmação de origem e chave de sessão (req-217)

Execução da [req-217](../../human-requests/archive/req-217.md), pedida pelo Engenheiro Chefe ao revisar a REQ-097 do `conn2flow-site`. No site, o relatório é `sdd/implementation/modulos-distribuidos/batch-092-marketplace-de-servicos.md` (o mesmo lote traz o marketplace).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/modulo-distribuido-protocolo.php` | `modulo_distribuido_sessao_saida()`, `_sessao_abrir()` (quem envia), `_sessao_conceder()`, `_confirmar_origem()`, `_receber()` (quem recebe), cifra AES-GCM e envelope assinado das ações de abertura; `peer` na configuração do painel |
| `gestor/bibliotecas/modulo-distribuido.php` | `modulo_distribuido_enviar` assina com a chave da sessão (`X-C2F-Session`) e reabre a sessão uma vez em caso de 401; `http_post` guarda o status; `canal_distribuido` leva `peer = central` |
| `gestor/controladores/api/api.php` | `abrir` e `confirmar` vão ao lado Central quando a instalação não tem `app-id` |
| `gestor/controladores/api/api-module-central.php`, `api-module-distributed.php` | Atendem `abrir` (retorno ao endereço cadastrado) e `confirmar`; o resto exige sessão (`distributed-session-required` / `distributed-session-invalid`) |
| `gestor/config.php` | `confirmacao-origem` (padrão ligada; `MODULO_DISTRIBUIDO_ORIGIN_CHECK=false` desliga) |

## Como funciona

1. Quem quer falar (A) cria um desafio de uso único (60 s), guardado só por ele, e pede `abrir`, assinando com o segredo da instalação.
2. Quem recebe (B) valida a assinatura, mas não confia: liga para o endereço que **ele** tem cadastrado de A e pede `confirmar` com o mesmo desafio.
3. A consome o desafio e responde "sim", assinado, se o desafio foi criado por ele e para aquele lado.
4. B cria a sessão: um id aleatório e uma chave de 32 bytes, guardados cifrados, válidos por 15 minutos e ligados a A. Ele devolve a chave cifrada com o segredo mais o desafio, de modo que só quem criou o desafio a lê.
5. As requisições seguintes (banco, rotina, estado, troca de login, ticket do iframe, permissão) vão assinadas com a chave da sessão. Sessão vencida ou esquecida do outro lado gera 401, que reabre a sessão e repete a requisição uma vez.

O segredo roubado, usado de qualquer lugar que não seja o endereço cadastrado, não abre sessão. O "sim" só sai do servidor verdadeiro, e o retorno nunca vai a um endereço informado na requisição. A chave de trabalho troca sozinha a cada 15 minutos.

## Validação

- `tests/Unit/PHP/ModuloDistribuidoOrigemReq217Test.php`: 8 testes. Simulam as duas direções, a reutilização da sessão, o atacante com o segredo nas duas direções, sessão de outra instalação, forjada, vencida, assinada com o segredo e com segredo trocado, o modo anterior, a reabertura depois de 401, o desafio de uso único e a ligação ao lado certo.
- Canal: 90 testes; suíte: 1.466, só a falha anterior `ProjectSshDeployReq034Test`.
- Lab: sessões nas duas direções gravadas nos dois bancos. Os roteiros req092, req094 a req098 passam com a confirmação ligada. O ataque com o segredo verdadeiro, fora do endereço cadastrado, teve 6/6 tentativas recusadas (`conn2flow-site/sdd/validation/modulos-distribuidos/req217-ataque-chave-roubada.php`).

## Limites

- Central e sites precisam ser publicados juntos; o desligamento por `.env` existe para a transição.
- `abrir` espera o `confirmar` do outro lado chegar ao próprio servidor: são necessários pelo menos dois processos PHP (PHP-FPM atende; o servidor embutido do PHP, com um processo só, trava).
- Próximo passo proposto: chaves assimétricas (Ed25519), para que o Central guarde só chaves públicas.
