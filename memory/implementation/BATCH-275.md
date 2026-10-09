# BATCH-275 — REQ-266: compatibilidade memory/ e sdd/

- Projeto: `conn2flow`
- Raiz: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- Data: 2026-10-09
- Status: `implemented-pending-homologation`
- Branch: `feat/req-266`
- Requisição: [REQ-266](../human-requests/req-266.md)
- Modo: `autonomo_monitorado`

## Live Todo

- [x] Ler briefing, CURRENT e governança.
- [x] Resolver caminhos dual nos testes e CLI, preservando aliases históricos.
- [x] Corrigir três contratos na fonte canônica e propagar aos cinco kits do Core.
- [x] Atualizar referências de memory/README.md.
- [x] Executar PHPUnit, Vitest, ai:sync, arquivamento dry-run e validação da memória.
- [x] Revisar diff e registrar evidências no checklist.

## Escopo e preservação

Preferência por memory/ e fallback sdd/. SddSource conserva chaves públicas sdd/ e seção sdd para preservar o contrato de publicação existente. Nenhuma alteração normativa ou de recursos de produto.

Estado inicial: branch feat/req-265, sem alterações rastreadas; output/ não rastreado e preservado. Branch feat/req-266 criada a partir desse estado.

## Implementação

Os testes REQ-225 e REQ-256 preferem os artefatos em memory/. SddSource lê memory/ quando a pasta existe e mantém o fallback legado; a coleta real e o filtro de conteúdo sensível continuam exigidos pelo teste REQ-184.

O arquivador seleciona a árvore a partir do repositório resolvido por --repo, usa suas duas pastas governadas e conserva o reparo de links. Novos aliases: ai:archive-memory, memory:archive e memory:prune. O validador de memória prefere o arquivo existente em memory/, mesmo quando ambas as árvores existem, sem executar poda.

AiSyncCommand agora exige as 44 skills canônicas, incluindo as sete que não constavam no catálogo antigo de 37. Os totais dos textos vêm da lista exigida; o status aceito é ✔ Complete. O teste novo verifica a tabela dos cinco kits.

Fonte canônica corrigida: conn2flow-ai-workspace, C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-ai-workspace, .gemini/skills/{c2f-ai-features,c2f-modelo-templates,c2f-module-visual-assets}/SKILL.md. O título do gatilho de modelo-templates tinha mojibake; os outros dois blocos estavam ausentes. Correção restrita ao título corrompido e aos blocos novos; o restante da skill de modelos foi preservado.

Propagação oficial: node ../conn2flow-ai-workspace/scripts/skills/sync-skills.cjs --apply c2f-ai-features c2f-modelo-templates c2f-module-visual-assets --target C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow --report temp/req266-skills.json. Resultado: 5 alvos, 220 skills iguais, 15 arquivos escritos, zero divergências/skills locais alteradas. O propagador também escreve rules: a alteração indevida na regra MDD do Core foi revertida por edição inversa e os nove arquivos novos não rastreados foram removidos individualmente. .claude/skills é ignorado pelo Git, mas foi propagado e validado localmente.

## Defeitos encontrados e limites

- Além dos caminhos rígidos, o catálogo de ai:sync ainda exigia só 37 skills; corrigido para 44.
- O comando de arquivamento no Core foi exercitado somente em dry-run. Movimentação real e reparo de links foram exercitados em fixtures temporárias para memory/, sdd/ e ambas juntas; fixtures removidas no tearDown.
- Checagem adicional no conn2flow-site com --dry-run selecionou memory/, mas retornou 1 por 55 links relativos quebrados, incluindo referências ao antigo conn2flow/sdd/. Nenhum arquivo do Site foi alterado. Fallback sdd/ validado com exit 0 no boilerplate en/sdd-boilerplate de conn2flow-app e nas fixtures.
- ai:prune-memories/memory:prune: exit 0, arquivo encontrado, 38.451 bytes e 252 linhas. Alerta preventivo por linhas; nenhuma poda executada.
- Vitest: exit 0, 60 arquivos e 664 testes aprovados. A execução emitiu mensagens de Happy DOM sobre iframes desabilitados e conexões recusadas/abortadas; aprovação dos testes não implica ausência dessas mensagens.
- Sem alterações de HTML/CSS/JS de produto, banco ou deploy. Sem inspeção visual necessária.
- README: apenas referências sdd/ foram trocadas para memory/; texto histórico restante mantido, não conferido.

## Evidências de validação

| Verificação | Resultado |
| --- | --- |
| wsl -d Conn2Flow-Lab --exec php vendor/bin/phpunit --log-junit temp/req266-phpunit.xml | Exit 0; suíte completa com 1.768 testes, 20.735 asserções, 4 skips, 4 depreciações PHP e 4 depreciações PHPUnit; 4m49s. Sem erro fatal/falhas. |
| php vendor/bin/phpunit --filter Req266 (estado final) | Exit 0; 5 testes, 58 asserções. Inclui o teste adicionado após o início da suíte completa: catálogo completo e rejeição de skill obrigatória ausente. |
| Testes focados originais REQ-225, REQ-256 e REQ-184 no Windows | Exit 0; 11 testes, 4.013 asserções. Novo teste de preferência memory/ de REQ-184 também incluído na suíte Linux completa. |
| node node_modules/vitest/vitest.mjs run | Exit 0; 60 arquivos, 664 testes aprovados. |
| php cli/c2f.php ai:sync | Exit 0; cinco kits com 44/44 exigidas, 44 contratos e ✔ Complete. |
| ai:archive-sdd, ai:archive-memory, memory:archive --dry-run no Core | Exit 0; selecionam memory/, planejam 2 arquivos (1 requisição e 1 lote); nenhuma movimentação. |
| sdd:archive --repo=.../conn2flow-app/en/sdd-boilerplate --dry-run | Exit 0; seleciona sdd/, zero links órfãos. |
| ai:prune-memories e memory:prune | Exit 0; memória encontrada e alerta preventivo, sem poda. |
| git diff --check | Exit 0, sem erros de whitespace. |

Logs locais: temp/req266-phpunit.log, temp/req266-phpunit.xml, temp/req266-ai-sync.log, temp/req266-archive-core.log e temp/req266-skills.json. Revisão própria conforme review-current-batch: nenhum finding funcional pendente no escopo; limites acima preservados. Homologação humana permanece separada da validação técnica.

A fonte canônica na matriz permanece com três alterações locais a revisar; o commit deste lote no Core contém os quatro kits rastreados, CLI, testes, README e evidências. O quinto kit (.claude) permanece local, conforme a regra de ignore existente.
