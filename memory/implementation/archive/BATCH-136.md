# BATCH-136 — Mirrors de projeto em `auth:cookie` e `env:*`

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-134.md`
- **Data de abertura**: 2026-08-26
- **Classificação**: implementação incremental / bugfix de CLI

## Objetivo

Corrigir os comandos `c2f auth:cookie`, `c2f env:status` e `c2f env:set` para que `--project=ID`
opere sobre o mirror local configurado em `devProjects`, com resolução determinística de
`path_tests`, `target` e `path`. Quando o container `conn2flow-app` estiver ativo, a geração do token
deve ocorrer dentro dele para que hosts internos como `DB_HOST=mysql` sejam resolvidos.

## Slice aprovado

1. Introduzir um resolvedor compartilhado para `dev-environment/data/environment.json`.
2. Resolver a raiz do Gestor, o `.env`, a URL/host e o mount Docker do projeto.
3. Aplicar o resolvedor a `env:status`, `env:set` e `auth:cookie`.
4. Isolar a geração do cookie em script CLI, executável localmente ou no container.
5. Cobrir paths, mutação isolada do `.env`, despacho Docker e fallback local em PHPUnit.

## Fora do escopo

- Alterar `environment.json` ou credenciais de projetos.
- Subir, reiniciar ou reconfigurar containers.
- Mudar o contrato de autenticação, JWT, sessão ou banco de dados.
- Fazer deploy, commit ou push.

## Contrato de validação

- `php -l` limpo nos PHP modificados/adicionados.
- Testes focados de `CliEnvCommandsTest` e `CliProjectEnvironmentTest` aprovados.
- Suíte PHPUnit completa aprovada.
- `c2f env:status --project=snapphoton-local` reporta o `.env` do mirror Photon.
- `c2f auth:cookie --project=snapphoton-local` usa `conn2flow-app` quando disponível e grava o jar
  solicitado no host.

## Evidências

- `php -l`: **7/7** arquivos modificados/adicionados sem erros.
- Testes focados: **14 testes, 36 asserções**, cobrindo `path_tests`, `target`, `path`, fallback do
  `.env` na raiz, mount Docker,
  isolamento do `.env`, despacho no container e fallback local.
- Suíte PHPUnit completa: **765 testes, 3.285 asserções**, 4 skips de ambiente e 1 depreciação
  preexistente do PHPUnit.
- `git diff --check`: limpo.
- Runtime `env:status --project=snapphoton-local`: resolveu
  `dev-environment/data/sites/localhost/photon/autenticacoes/localhost/.env`.
- Runtime `env:set`: ciclo `false → true → false` confirmado no `.env` do mirror sem alterar o
  `.env` da raiz. O arquivo voltou a aparecer como `true` depois, com timestamp posterior ao ciclo;
  a busca nos arquivos carregados pelo gerador não encontrou escrita no `.env`, indicando mutação
  concorrente/externa ao comando validado. O lote não sobrescreveu esse estado novamente.
- Runtime `auth:cookie --project=snapphoton-local`: detectou `conn2flow-app`, executou o gerador em
  `/var/www/sites/localhost/photon/` e gravou `temp/agent-cookies.txt` com sucesso para o usuário 1.
- Nenhum resíduo `.c2f-auth-cookie-*` permaneceu no mirror após a execução.
- Review findings-first: nenhum finding funcional, de regressão, spec drift, batch drift ou
  validação ausente.
- Memória de execução podada de **9.125 bytes / 122 linhas** para **3.608 bytes / 45 linhas**.
- Nível 1 respeitado: nenhum commit, push ou deploy executado.
