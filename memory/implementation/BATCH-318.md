---
id: "BATCH-318"
title: "REQ-309 — Consolidação da REQ-308 e release Gestor 3.0.20"
status: "published-pending-homologation"
date: "2026-11-26"
author: "executor"
target_repo: "conn2flow"
request: "REQ-309"
topology: "dupla"
autonomy: "autonomo_monitorado"
summary_short: "REQ-308 integrada na main e Gestor 3.0.20 publicado com a UI 3.0 de abas, diálogos e avisos; CI success e SHA-256 conferido."
summary_medium: "Merge --no-ff da branch da REQ-308, correção de EOL no teste, suítes completas verdes, release gestor-v3.0.20 pelo GitHub Actions e ZIP validado."
---

# BATCH-318 — REQ-309 — Gestor 3.0.20

- **Repositório** `conn2flow` em `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`.
- **Topologia e autonomia** `dupla` / `autonomo_monitorado`. Push para `origin/main` e da tag autorizado na [REQ-309](../human-requests/req-309.md).
- **Base** `main` = `origin/main` em `88d0b08e1b67a5d54268be649a2e2bec63ceab26` (sem divergência).
- **Branch integrada** `feat/req-308-ui3-abas-mobile-e-dialog-backdrop` em `446a1350374dfb3547c3dc968bc2edf5513f61ba` (`ee8454a9` + `446a1350`), merge `--no-ff` `185550f5`. Nenhuma outra branch foi integrada. A branch foi preservada.
- **Correção de teste** `b5f7d107`: ver abaixo.
- **Commit publicado** `96cf63869508a5b5b7064ce8058a8def23e3b3c4` (`release(core): v3.0.20 …`). Objeto da tag anotada `gestor-v3.0.20`: `3314950e56ae79afe4457bb2edca03dc68f7bb54`.

## Rodada 1 — validação local antes da tag

| Verificação | Resultado |
| --- | --- |
| Vitest completo (`npm test`) | 1ª execução: 742/743. Falha em `req308-controles.test.js` (CSS): o teste fatiava `controles.css` num marcador com `"\n"`; no checkout Windows (`autocrlf`, índice LF / cópia CRLF) o `indexOf` dava -1. Corrigido normalizando CRLF → LF na leitura (`b5f7d107`, só o teste). 2ª execução: **743/743 em 66 arquivos**. |
| PHPUnit completo no checkout Windows | 2.102 testes, 21 falhas + 1 erro: o mesmo conjunto da linha de base dos BATCH-306/312/313 (`"\n"` procurado em `dashboard.php`/`api.php` com cópia CRLF; RSA sem `openssl.cnf`). Nenhum arquivo dessas falhas foi tocado pelo merge. |
| PHPUnit completo sobre export LF do `HEAD` (`git -c core.autocrlf=false archive`, `vendor` por junction, removida depois) | 2.102 testes, 22.833 asserções, **0 falhas**, 1 erro (RSA). O RSA passou isolado com `OPENSSL_CONF=…\extras\ssl\openssl.cnf` (6 asserções). Suíte 100% verde no equivalente ao CI. |
| `php cli/c2f.php assets:minify --verificar` | 75 arquivos de autoria, **0 derivados desatualizados**. |
| `php -l` | `controles.php`, `interface.php` (alterados pelo merge) e `gestor/config.php` sem erros. |
| `bash -n` | 70 scripts `*.sh` versionados sem erros. |

## Release

- Metadados no padrão do `a0f1adb4` (3.0.19): `gestor/config.php` 3.0.20, entradas `[3.0.20]` em `CHANGELOG.md` e `CHANGELOG-PT-BR.md` (abas responsivas Solução B, fechamento de `<dialog>` pelo fundo, aviso unificado, estilos escopados), selos dos READMEs e a seção Status/Situação, que ainda dizia v3.0.2, passou a v3.0.20 com um item novo sobre a UI 3.0 (itens existentes mantidos).
- Push: `main` `88d0b08e → 96cf6386` e tag `gestor-v3.0.20`.
- [GitHub Actions — Release Gestor, execução 45892164924](https://github.com/otavioserra/conn2flow/actions/runs/45892164924): **success**. Documentação sincronizada, PHPUnit, Vitest, Playwright, ZIP e criação da release em `success`; a retirada de releases ficou `skipped` (só roda em disparo manual com confirmação).
- [GitHub Releases — gestor-v3.0.20](https://github.com/otavioserra/conn2flow/releases/tag/gestor-v3.0.20): estável, marcada como Latest, publicada em 2026-11-26T20:37:32Z.

## Conferência do pacote

- `gestor.zip` 19.080.107 bytes e `gestor.zip.sha256` 65 bytes. SHA-256 idêntico no cálculo local, no arquivo de checksum e no digest da API: `2f2f944460b5a7d3f7f3e58233ef8eb517ec649bde9d6977768ebc601669176b`.
- 2.183 entradas. `config.php` com `versao = '3.0.20'`.
- `assets/interface/controles.js`, `controles.min.js`, `controles.css`, `assets/asset-versions.json` e `assets/minify-manifest.json` presentes e com conteúdo idêntico aos da tag (comparação após normalizar EOL). O minificado contém `c2fc-abas-seletor` e o CSS tem os blocos `@media (max-width: 639.98px)`.
- **DEC-136**: `gestor-v3.0.19` e `gestor-v3.0.18` continuam listadas e acessíveis. Nada foi removido.

## Handoff

Projeto `conn2flow`, raiz `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`, REQ-309, BATCH-318, topologia dupla, autonomia `autonomo_monitorado`. O release está pronto para a atualização de `dev.snapphoton.com` pelo painel, que destrava a `lumix` REQ-142. Pendências: revisão do Macro-Arquiteto e homologação humana da atualização no Dev. Não houve deploy em nenhum ambiente.

## Relatório de dificuldades e aprendizados (AI Workspace)

1. **Contexto**: `conn2flow`, REQ-309 / BATCH-318, consolidação e release.
2. **Atritos**: o Vitest dado como 743/743 no BATCH-317 falhou após o checkout da `main`, porque o Git regravou `controles.css` em CRLF. O checkout para a `main` também foi barrado pelo `CURRENT.md` alterado pelo operador; resolvido com stash só desse arquivo.
3. **Lacunas nas skills**: `project-validation` e a rotina de release não dizem como provar "PHPUnit 100% verde" no Windows, onde 21 testes sempre falham por CRLF. Os lotes anteriores aceitaram a linha de base sem prova.
4. **Solução validada**: export LF do `HEAD` com `git -c core.autocrlf=false archive` + `OPENSSL_CONF` dá a suíte completa verde localmente (2.102 testes, 0 falhas) antes de criar a tag; o CI confirmou.
5. **Sugestões**: (a) testes JS/PHP que procuram texto com `"\n"` em fonte devem normalizar `\r\n` (corrigir os 21 casos PHP de `DashboardLousa*`, `DashboardWidgetsPermissoes*` e `ManutencaoReq210Test` num lote próprio); (b) acrescentar à rotina de release o passo "suíte completa sobre export LF" ou um `.gitattributes` com `eol=lf` para fontes.
