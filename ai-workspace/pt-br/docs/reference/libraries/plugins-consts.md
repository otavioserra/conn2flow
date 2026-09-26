---
title: "Biblioteca plugins-consts.php"
label: "Constantes de plugins"
description: "Códigos de saída e estados de execução do instalador de plugins, e plg_exit_code_label()."
section: reference
order: 310
sources:
  - gestor/bibliotecas/plugins-consts.php
  - gestor/bibliotecas/plugins-installer.php
verified_at: a5ae8605
---

# Biblioteca `plugins-consts.php`

Constantes compartilhadas pelo instalador de plugins (`plugins-installer.php`). Cada uma só é definida se ainda não existir.

## Códigos de saída

| Constante | Valor | Rótulo (`plg_exit_code_label()`) |
|---|---|---|
| `PLG_EXIT_OK` | 0 | `OK` |
| `PLG_EXIT_PARAMS_OR_FILE` | 10 | `PARAMS_OR_FILE`: parâmetro inválido ou arquivo ausente |
| `PLG_EXIT_VALIDATE` | 11 | `VALIDATE`: manifesto do plugin inválido |
| `PLG_EXIT_MOVE` | 12 | `MOVE`: falha ao mover para a pasta final |
| `PLG_EXIT_DOWNLOAD` | 20 | `DOWNLOAD` |
| `PLG_EXIT_ZIP_INVALID` | 21 | `ZIP_INVALID` |
| `PLG_EXIT_CHECKSUM` | 22 | `CHECKSUM`: SHA-256 do pacote não confere |

Qualquer outro código devolve `UNKNOWN`.

## Estados de execução

`PLG_STATUS_IDLE` (`idle`), `PLG_STATUS_INSTALANDO` (`instalando`), `PLG_STATUS_ATUALIZANDO` (`atualizando`), `PLG_STATUS_ERRO` (`erro`) e `PLG_STATUS_OK` (`ok`), gravados na coluna `status_execucao` da tabela `plugins`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/plugins-consts.php` por `c2f docs:extract` — 1 funções. Não edite dentro deste bloco.

- `plg_exit_code_label(int $code): string` — [linha 68](../../../../../gestor/bibliotecas/plugins-consts.php#L68)

<!-- c2f:extract:end -->
