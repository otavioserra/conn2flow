---
id: "BATCH-287"
title: "Atualizador do core sempre entregue, mesmo com manifesto desatualizado"
status: "implemented-pending-homologation"
date: "2026-10-15"
author: "executor"
target_repo: "conn2flow"
summary_short: "Arquivos do atualizador (camada core) são sempre escritos quando mudam; manifesto desatualizado não segura mais o hotfix do atualizador."
summary_medium: "A 3.0.5 no temp preservou 28 arquivos como editados, inclusive o atualizador, porque o manifesto do core ficou na 3.0.3 depois de um deploy SSH da 3.0.4.\nPHPUnit 1.849 (4 novos), sem falhas."
---

Projeto: conn2flow. Raiz: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-req277 (worktree).
REQ-278; BATCH-287; topologia dupla; autonomo_monitorado; branch `feat/req-278` (da tag `gestor-v3.0.5`). Vai na 3.0.6. Sem deploy pelo executor (pedido do Humano): release pelo outro agente.

## Live Todo

- [x] `INSTALACAO_CORE_SEMPRE_ESCREVER` e `instalacao_core_sempre_escrever()` em `bibliotecas/instalacao-manifesto.php`; `instalacao_planejar()` escreve esses arquivos antes das regras de preservação e lista em `forcados`.
- [x] Log da entrega com os forçados (`atualizacoes-sistema.php`).
- [x] Testes no `InstalacaoManifestoTest`.
- [x] Relatório, commit e push.
- [ ] Release 3.0.6 e atualização do `temp` pelo painel.

## Evidências

| Verificação | Resultado |
| --- | --- |
| `InstalacaoManifestoTest` + `AtualizacoesManifestoIntegracaoTest` + `AtualizadorHotfixReq277Test` | 39 testes OK (4 novos: manifesto desatualizado; reentrega com disco diferente; sobreposição do projeto; regra só no core) |
| PHPUnit completo (`ssh lab`, worktree com LF) | 1.849 testes, 0 falhas |

## Estado do `temp` (para o release)

- Trava órfã das 16:27 removida (PID morto); manifesto do core retirado para a próxima entrega valer como primeira; cópias em `/root/req128-backup-20261015-1145/`.
- Arquivos no disco: mistura de 3.0.4 (28 preservados) com 1 arquivo da 3.0.5; `config.php` ainda diz 3.0.4. A atualização pelo painel para a 3.0.6 (ou repetir a 3.0.5) escreve tudo.
- Depois: conferir `assets/modulos/covers/manifest.json` contra a cópia `covers-manifest-antes-3.0.5.json` (é o único arquivo do core que o site sobrepõe com conteúdo próprio).
