---
title: "plugins-consts.php library"
description: "Exit codes and execution states of the plugin installer, and plg_exit_code_label()."
section: reference
order: 310
sources:
  - gestor/bibliotecas/plugins-consts.php
  - gestor/bibliotecas/plugins-installer.php
verified_at: a5ae8605
---

# `plugins-consts.php` library

Constants shared by the plugin installer (`plugins-installer.php`). Each one is only defined if it does not exist yet.

## Exit codes

| Constant | Value | Label (`plg_exit_code_label()`) |
|---|---|---|
| `PLG_EXIT_OK` | 0 | `OK` |
| `PLG_EXIT_PARAMS_OR_FILE` | 10 | `PARAMS_OR_FILE`: invalid parameter or missing file |
| `PLG_EXIT_VALIDATE` | 11 | `VALIDATE`: invalid plugin manifest |
| `PLG_EXIT_MOVE` | 12 | `MOVE`: failed to move to the final folder |
| `PLG_EXIT_DOWNLOAD` | 20 | `DOWNLOAD` |
| `PLG_EXIT_ZIP_INVALID` | 21 | `ZIP_INVALID` |
| `PLG_EXIT_CHECKSUM` | 22 | `CHECKSUM`: the package SHA-256 does not match |

Any other code returns `UNKNOWN`.

## Execution states

`PLG_STATUS_IDLE` (`idle`), `PLG_STATUS_INSTALANDO` (`instalando`), `PLG_STATUS_ATUALIZANDO` (`atualizando`), `PLG_STATUS_ERRO` (`erro`) and `PLG_STATUS_OK` (`ok`), stored in the `status_execucao` column of the `plugins` table.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/plugins-consts.php` by `c2f docs:extract` — 1 functions. Do not edit inside this block.

- `plg_exit_code_label(int $code): string` — [line 68](../../../../../gestor/bibliotecas/plugins-consts.php#L68)

<!-- c2f:extract:end -->
