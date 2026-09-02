# BATCH-120 — Personal Access Tokens e códigos de recuperação de 2FA

Origem: [req-119.md](../../human-requests/archive/req-119.md)
Validação: [VALIDATION-CHECKLIST.md#batch-120](../../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation` (implementado em 2026-08-18)

Continuação direta do BATCH-119: a aba nova nasce no painel Tailwind já entregue.

---

## O que governou o desenho

**Dois segredos existem em texto puro uma única vez cada** — o PAT, na resposta da criação, e os 10
códigos de recuperação, na resposta da ativação do 2FA. O banco guarda apenas hashes e não há
endpoint de recuperação. Isso decidiu duas coisas concretas na interface:

- criar um token **não recarrega a página** (recarregar apagaria o valor da tela);
- ativar o 2FA **deixou de recarregar** quando vêm códigos — o fluxo antigo fazia `location.reload()`
  e teria destruído os dez códigos antes de o usuário anotá-los.

## M1 — Banco

Migração `20260818100000`: tabela `usuarios_api_tokens` (com índice **único** em `token_hash` — a
validação busca a linha PELO hash, e colisão faria duas contas compartilharem credencial) e coluna
`two_factor_recovery_codes` em `usuarios` (JSON de **hashes**, nunca códigos em claro).

## M2 — Biblioteca core (`bibliotecas/usuario.php`)

Puras (testáveis sem banco, e são as que decidem se uma credencial vale):
`usuario_api_token_formato()`, `usuario_api_token_hash()`, `usuario_api_token_prefixo()`,
`usuario_api_token_situacao()`, `usuario_recovery_codes_gerar()`,
`usuario_recovery_code_normalizar()`, `usuario_recovery_code_hash()` e
`usuario_recovery_code_consumir()`.

Com banco: `usuario_api_token_gerar()`, `usuario_api_token_validar()`, `usuario_api_token_revogar()`
e `usuario_api_tokens_listar()`.

Decisões registradas no código:

- **SHA-256 sem sal** para o PAT: diferente de senha, o token é 64 hexadecimais de CSPRNG — não há
  dicionário a proteger, e a busca precisa ser feita pelo hash (um sal por linha obrigaria a varrer a
  tabela a cada requisição de API).
- **Status desconhecido falha fechado** (`revogado`); **data de expiração ilegível falha aberto**
  (`ativo`) — o defeito é do dado, e derrubar credencial de produção por formato inesperado seria
  pior. `expirado` é distinto de `revogado` porque o usuário resolve os dois de formas diferentes.
- **Alfabeto dos códigos exclui `0`, `O`, `1`, `I` e `L`**: o código é copiado à mão de um papel, e
  cada erro de digitação custaria uma das dez chances.
- **Comparação por `hash_equals`** e normalização antes do hash (hífen, espaço e caixa variam quando
  se digita lendo de um papel).

## M3 — Integração com a API

`api_token_validar_memoizado()` passou a desempatar **pelo formato** (`c2f_pat_`) antes de qualquer
validação: sem isso, todo PAT passaria pelo validador de JWT, falharia na decodificação e o usuário
receberia "token inválido" sem pista do motivo. Os dois validadores devolvem o **mesmo contrato**,
então nenhum endpoint precisou aprender um segundo formato — e o rate limit por usuário
(`api_rate_limit_subject`) passou a funcionar para PAT de graça.

`usuario_api_token_validar()` também derruba o token quando o usuário está inativo: a credencial
pertence à conta, não a si mesma.

## M4 — Aba "Chaves de API" e resgate no login

- Aba 4 no painel, com formulário (nome, expiração, escopos), caixa de exibição única do token com
  botão de cópia (Clipboard API, com seleção de texto como alternativa em contexto não seguro) e
  tabela com nome, prefixo, criação, último uso, situação e revogação.
- **A aba só existe para perfis autorizados pela política da API** (`AUTH_API_ALLOWED_PROFILES`) — a
  mesma política, não uma permissão nova: token emitido por quem não pode usar a API seria credencial
  inútil, ou um caminho lateral para contorná-la. Quando escondida, a aba é removida do HTML por
  `modelo_tag_del`, não apenas ocultada.
- Revogar **mantém a linha** na tabela: apagar destruiria "criada em" e "último uso", que são a
  auditoria da própria credencial.
- No login 2FA, o código de recuperação só é tentado **depois** que o segundo fator normal falhou —
  tentar antes gastaria um código a cada digitação errada do TOTP.

## Validação

- `php -l` OK (usuario.php, perfil-usuario.php, api.php, migração e os 2 testes novos);
  `node --check` OK.
- Compilador de recursos: **0 erros**, apenas os 4 avisos pré-existentes.
- `composer test` → **387/387** (novos `UsuarioApiTokensTest` 20/20 e `UsuarioRecoveryCodesTest`
  14/14).
- `npx vitest run` → **328/328** (novo `perfil-usuario.api-tokens.test.js` 19/19).
- **Pendente**: deploy `Update => Core` + homologação com o operador. A migração roda sozinha no
  pipeline (`atualizacoes-banco-de-dados.php` → `migracoes()`); ver BATCH-122 para o que acontece
  quando ela NÃO roda.

## Limites de escopo (declarados)

- Os escopos (`read`/`write`/`deploy`) são gravados e devolvidos no contrato (`scope`), mas **nenhum
  endpoint os aplica ainda** — a API atual autoriza por perfil. Fazer os endpoints respeitarem escopo
  é mudança de contrato da API e merece intake próprio.
- A tela de login 2FA continua em Fomantic (é o req-120): o campo de código já aceita o código de
  recuperação, mas o rótulo dedicado entra junto com a migração daquela tela.
