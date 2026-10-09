# BATCH-122 — Degradação graciosa quando a migração ainda não rodou

Origem: observação do Chefe em 2026-08-18 sobre o BATCH-120 ([req-119.md](../../human-requests/archive/req-119.md))
Validação: [VALIDATION-CHECKLIST.md#batch-122](../../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation` (implementado em 2026-08-18)

---

## Correção de premissa

Eu havia registrado "migração Phinx pendente" como passo manual. **Está errado**: o pipeline roda as
migrações sozinho — `atualizacoes-banco-de-dados.php` chama `migracoes()`, que executa
`$manager->migrate($env)` com o Phinx embutido, e só pula com `--skip-migrate`. Não há passo manual
por instalação.

O Chefe apontou o problema **real** por trás disso: código e schema chegam por canais diferentes, e
não há garantia de que cheguem juntos em toda instalação.

## O risco, nomeado

O código do sistema é atualizado por ARQUIVOS e o schema por MIGRAÇÕES. Existem caminhos concretos
em que o código novo alcança um banco antigo:

1. a migração falha (permissão, lock, timeout) e o restante do deploy prossegue;
2. a atualização é só de arquivos (`Synchronize => Files`), sem tocar o banco;
3. o operador usa `--skip-migrate`;
4. **a janela entre os arquivos chegarem e a migração terminar** — toda requisição nesse intervalo
   executa código novo contra schema velho.

Sem tratamento, o desfecho é `Table 'usuarios_api_tokens' doesn't exist` na página de perfil: uma
tela que já funcionava passa a quebrar por causa de uma funcionalidade que o usuário nem pediu.

Já é lição registrada em outro projeto (Photon: schema drift quebrando produção com 500 "column does
not exist"). Aqui ela vira mecanismo.

## O mecanismo

Dois detectores no core (`bibliotecas/gestor.php`), **memoizados por requisição e silenciosos**:

- `gestor_schema_tabela_existe($tabela)` — executa `SHOW TABLES` **uma vez** por requisição e guarda
  o resultado inteiro. Verificar tabela a tabela custaria uma ida ao banco por checagem, em código
  que roda no caminho de render.
- `gestor_schema_campo_existe($campo, $tabela)` — confere a TABELA antes, porque `SHOW COLUMNS` sobre
  tabela inexistente é um erro de SQL, e o objetivo do gate é justamente não produzir nenhum.

Ambos **falham fechado**: banco indisponível, exceção ou detector ausente resultam em `false`. Sem
certeza de que o schema está pronto, a funcionalidade nova não é oferecida.

## Onde o gate entra

| Ponto | Sem o schema |
| --- | --- |
| Aba "Chaves de API" no perfil | Não é renderizada — mesmo caminho já usado para perfil não autorizado |
| `usuario_api_token_gerar/validar/revogar/listar` | Recusam antes de tocar o banco |
| Endpoint AJAX de geração | Responde com a mensagem de "não autorizado" |
| Validação de PAT na API | Devolve credencial inválida (401), não erro 500 |
| Ativação do 2FA | **Ativa normalmente**, apenas sem gerar códigos de recuperação |
| Resgate por código de recuperação | Não valida |
| Rótulo do recovery code no login | Não é exibido |

A assimetria do 2FA é deliberada: perder o segundo fator inteiro por causa de uma coluna ausente
seria muito pior que ficar sem os códigos de resgate.

A geração de códigos (`usuario_recovery_codes_gerar`) continua **pura e independente do gate** —
quem decide se ela é chamada é o módulo. Isso mantém a função testável e reaproveitável.

## Validação

- `php -l` OK; `composer test` → **508/508**, com o novo `SchemaDegradacaoTest` **14/14**, que simula
  os dois mundos (com e sem migração) pelo cache de schema em `$_GESTOR`, sem tocar o banco.
- `npx vitest run` → **328/328** sem regressão; compilador de recursos com 0 erros.

## Nota de método

O padrão vale para **toda funcionalidade nova que dependa de migração**, não só para esta. A regra:
se o recurso exige coluna ou tabela nova, o código precisa de um gate que o esconda enquanto o
schema não estiver pronto — e o gate falha fechado.
