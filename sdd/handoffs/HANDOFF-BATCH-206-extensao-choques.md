# Handoff — BATCH-206: choques das entregas na extensão do VS Code (req-199)

* **Data**: 2026-09-30
* **De**: agente da req-198/199 (core, worktree `conn2flow-bl028`)
* **Para**: agente executor
* **Projeto**: `conn2flow-ai-workspace` (extensão em `vscode-extension/`)
* **Caminho raiz**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-ai-workspace`
* **Requisição de origem**: core [req-199](../human-requests/archive/req-199.md), lote `BATCH-206`. No `conn2flow-ai-workspace`, a requisição própria já está aberta: **`req-061`** (lote `BATCH-063`), apontando para este handoff; e seguir o SDD daquele repositório (`CLAUDE.md` de lá, `CURRENT.md`, `BATCH-INDEX.md`, memórias).

## Objetivo

A extensão lista os choques das entregas de um projeto, abre o diff lado a lado no editor nativo, deixa escolher a decisão (sobrescrever, manter, mesclar) e envia — **tudo pelo CLI do core** (`c2f`), sem falar com a API direto.

## Contrato do CLI (já no `main` do core, commit `0e2e2e3e`)

| Comando | Saída `--json` (uma linha; erro: `{ok:false, erro}` e código 1) |
|---|---|
| `c2f update:conflicts <projeto> --json [--todos]` | `{ok, projeto, choques:[{id, data_criacao, origem, camada, versao, caminho, tipo, motivo, camada_dona, copia, resolucao, resolvido_em, resolvido_por, acoes:[...]}]}` |
| `c2f update:conflicts <projeto> <id> --json` | `{ok, projeto, choque:{...}, binario, pasta, arquivos:{"no-ar": path, "nova": path, "mesclado": path}}` (baixa para `temp/conflicts/<projeto>/<id>/`; `mesclado` começa como a versão no ar e não é apagado por novo download) |
| `c2f update:resolve <projeto> <id> --acao=sobrescrever\|manter\|mesclar [--arquivo=PATH] [--local] --json` | `{ok, id, acao, resolvidos, local}` (`mesclar` usa o `mesclado` baixado por padrão; `--local` grava a mescla no repositório do projeto) |

- `acoes` já vem calculado por motivo (`editado` → sobrescrever/manter/mesclar; `sobreposto` → manter/mesclar; `retirado-editado` → sobrescrever/manter). A extensão só oferece o que vem em `acoes`.
- Os projetos vêm do `dev-environment/data/environment.json` (`devProjects`), que a extensão já lê (`projectEnvironmentPolicy.ts`). O CLI é chamado como os outros comandos (`ShellHelper.formatC2fCommand`), mas aqui é preciso **capturar a saída** (child_process), não só abrir terminal.

## Escopo sugerido

1. Nó "Choques" na árvore, por projeto (ou comando que pergunta o projeto): lista `caminho — motivo` com ícone por motivo; atualizar sob demanda.
2. Ao abrir um choque: baixa (`update:conflicts <p> <id> --json`), abre `vscode.diff(no-ar, nova)` com título claro e, se `mesclar` for possível, abre o `mesclado` para edição.
3. "Resolver": QuickPick com as `acoes`; para `mesclar`, confirmar que o `mesclado` está salvo e perguntar sobre `--local`; roda `update:resolve ... --json` e mostra o resultado; atualiza a lista.
4. i18n en/pt-br (`package.nls*.json`, `localizationCatalog.ts`), respeitar Workspace Trust (execução só com confiança, como os demais comandos).
5. Testes `node --test` para a parte pura (interpretar o JSON do CLI, montar a lista, escolher ações); `npm run compile` sem erro. **Não** publicar nem gerar `.vsix` sem o Humano pedir.

## Ambiente de teste

- Projeto `project-test` (`local: true`) → tenant isolado `c2f-teste.local` no SSH Lab, já configurado no `environment.json` (URL `https://c2f-teste.local:8443/`, `api_resolve_ip`, token). Hoje não há choques pendentes; para criar, peça ao agente da req-198/199 ou ao Humano uma atualização de teste (é esse agente que roda atualização no tenant). O `c2f update:conflicts project-test --todos --json` já lista os resolvidos para testar a leitura.
- O CLI roda a partir do diretório principal do core (`C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`); ele ainda não tem o `main` mais recente (outro agente tem trabalho não commitado lá). Para testar agora, use a worktree `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028` como raiz do core (tem o `c2f` com `--json`), **sem editar arquivos nela**.

## Regras

- `git add` só com caminhos explícitos; nada de `-A`/`-a`; não mexer em arquivos de outros agentes.
- Commit e push numa branch `feat/req-061` (ou o número aberto) do `conn2flow-ai-workspace`, **não** no `main`; avise o Humano. O agente da req-198/199 revisa.
- Qualquer mudança necessária no CLI do core: **não** edite o core; descreva no relatório e o agente da req-198/199 faz.
