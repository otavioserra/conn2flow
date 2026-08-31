# 01 — Conteúdo como Superfície de API

## O problema de "só pelo painel administrativo"

Um CMS tradicional trata o painel administrativo web como a única porta de
entrada para o seu conteúdo. Qualquer coisa que um agente precise ler ou
alterar tem que passar por raspagem de tela, acesso direto ao banco de
dados ou uma integração sob medida — nenhuma delas carrega a validação, as
permissões ou a trilha de auditoria do próprio CMS.

## Personal Access Tokens como identidade do agente

A camada `_api/` do Conn2Flow é alcançada por Personal Access Tokens (PAT)
que:

- são escopados a perfis explícitos (`AUTH_API_ALLOWED_PROFILES`), nunca
  uma credencial de administrador genérica;
- têm limite de taxa (rate limit) por token, para que um agente
  descontrolado não esgote o sistema;
- podem ser revogados sem tocar na senha ou na sessão do usuário;
- são validados pelo mesmo pipeline de permissão que um humano logado
  atravessa.

Um agente se autentica como um usuário escopado, não como um script de
fundo segurando um segredo compartilhado.

## O CLI como contrato de automação

O binário `c2f` (mais de 30 comandos, cobrindo recursos, banco de dados,
releases, Docker e CI) é a mesma superfície que um humano roda manualmente
no terminal e que um agente despacha remotamente através do **MCP Hub** do
Conn2Flow AI Workspace:

- `c2f_run_command` — executa um comando nativo do CLI (`resources:sync`,
  `db:test`, `docker:status` e o restante do catálogo);
- `dispatch_task` — enfileira trabalho para um agente rodando em modo
  supervisionado pelo IDE ou headless;
- `report_completion` — registra e correlaciona a evidência de um lote
  concluído com a requisição que o originou.

Um único contrato, dois operadores: independentemente de quem estiver
dirigindo, os comandos, suas travas de segurança e sua saída são idênticos.

## O IDE como console

A extensão VS Code **Conn2Flow Dev Tools** expõe as mesmas operações
(Docker, Manager, Projetos, Releases, Hub de IA) como ações de um clique em
uma barra lateral, permitindo que um humano dirija exatamente a mesma
superfície de automação que um agente usa — sem nunca sair do editor.
