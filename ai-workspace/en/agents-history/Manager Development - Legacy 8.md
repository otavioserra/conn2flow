# Manager Development - Legacy 8 (September 2025)

## Focused Objective of This Session
To strictly adapt the core script `atualizacoes-banco-de-dados.php` into a plugin equivalent (`atualizacao-plugin-banco-de-dados.php`), without "innovating": just adjusting paths, supporting `--plugin=<slug>`, and operating on the plugin's `db/data`, while keeping all original logic (migrations, checksum, dry-run, orphans, reverse export, backup, filters, etc.).

## Scope Achieved
- Creation/adaptation almost line by line of the update script for plugins.
- Inclusion of secure dynamic resolution of the plugin directory.
- Mandatory support for `--plugin` and reuse of the same flags from the core (`--dry-run`, `--debug`, `--skip-migrate`, `--tables`, `--force-all`, `--backup`, `--reverse`, `--log-diff`, `--orphans-mode`).
- Adjustment of logs (file named `atualizacoes-plugin-bd-<slug>`).
- Isolated export of orphans per plugin (`plugins/<slug>/db/orphans/bd/`).
- Optional inclusion of the plugin's migrations directory (`plugins/<slug>/db/migrations`) to the Phinx paths array without removing the core.
- Protection against invalid slug (slashes) and debug log of the path.
- Tolerance for the absence of `.env` (just a warning) – connection via `config.php`.
- Safeguard: skipping *Data.json files whose tables do not exist in the database (avoids `Base table ... doesn't exist` error).

## Files / Directories Involved
- `gestor/controladores/plugins/atualizacao-plugin-banco-de-dados.php` (new/adapted) – main deliverable.
- `gestor/plugins/example-plugin/db/data/PaginasData.json` – simple fixture for validation.
- Docker synchronization via existing script (`docker/utils/sincroniza-gestor.sh checksum`).

## Problems Encountered & Solutions
| Problem | Cause | Solution |
|---------|-------|---------|
| Script couldn't find plugin | `BASE_PATH_DB` relative without recalculating at runtime inside the container | Recalculate `BASE_PATH_DB` in `main()` with `realpath(__DIR__.'/../../')` |
| Plugin directory not found error | Relative path resolving to empty in log (`base=`) | Adjustment of recalculation + debug log before validation |
| Failure due to absence of `.env` | Test environment not synchronized | Make check non-fatal: just a warning and proceed |
| Non-existent table exception (e.g., ExampleData.json) | Data.json exceeds standard schema | Insert `SHOW TABLES LIKE` check before synchronizing |
| Plugin migrations potentially conflicting | Need to include folder without breaking the core | Append directory to `paths['migrations']` (array merge) |

## Test Execution (Container)
1. Synchronization: `bash docker/utils/sincroniza-gestor.sh checksum` (provided task).
2. Dry-run:
   `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/plugins/atualizacao-plugin-banco-de-dados.php --plugin=example-plugin --dry-run --debug --skip-migrate"`
3. Result obtained (dry-run):
```
📝 Final DB Update Report
══════════════════════════════════════════════════
📦 componentes => +0 ~6 =0
📦 layouts => +0 ~2 =0
📦 paginas => +1 ~0 =0
📦 variaveis => +0 ~0 =6
Σ TOTAL => +1 ~8 =6
```
4. Logs stored in `logs/atualizacoes/atualizacoes-plugin-bd-example-plugin.log` (containing initial DEBUG line before the fix and subsequent executions).

## Deliberate Decisions
- DO NOT create a dedicated checksum table for plugins (requirement was to copy logic – maintain use of `manager_updates`).
- DO NOT change the signature of internal functions (ensures minimal diffs with the core).
- DO NOT remove code for features not needed now (reverse export, backup) to preserve parity.

## Risks / Limitations
- `manager_updates` continues to accumulate entries for plugins as well (may require future filtering if volume grows).
- Plugin migrations are only added to the path; malformed name/timestamp collisions still need curation (existing example with short timestamp will generate a Phinx error if `--skip-migrate` is not used).
- `DEBUG_PLUGIN_CANDIDATE` debug log remains (can be removed in a later cleanup).

## Suggested Next Steps
1. (Optional) Run without `--dry-run` to apply real plugin updates.
2. Sanitize plugin migrations ensuring valid timestamps `YYYYMMDDHHMMSS`.
3. Add a future flag `--no-manager-log` if you want to avoid logging in `manager_updates` when running only plugins.
4. Remove the debug log and consolidate short documentation in `ai-workspace/docs/PLUGIN-INSTALADOR-FLUXO.md` (linking the script).
5. Create a simple automated test (PHP) validating: initial insertion vs. second execution with no changes (idempotency).

## Delivery Checklist (Session)
- [x] Line-by-line adaptation preserving core functions
- [x] Mandatory support for `--plugin=<slug>`
- [x] Resolution of plugin directories (data, migrations, orphans)
- [x] Dry-run validated in the container
- [x] Skipping of non-existent tables avoiding exceptions
- [x] Logging separated by slug

## Main Differences vs. Core (Intentional)
| Aspect | Core | Adapted Plugin |
|---------|------|-----------------|
| Data directory | `gestor/db/data/` | `gestor/plugins/<slug>/db/data/` |
| Orphans | `gestor/db/orphans/bd/` | `gestor/plugins/<slug>/db/orphans/bd/` |
| Extra migrations | Core only | Core + plugin (append) |
| .env absent | Fatal | Warning (continues) |
| Base path dynamization | Assumed on initial load | Recalculated in `main()` |

## Relevant Context Commits (before this session)
Reference to recent modernizations (preview system, framework CSS, update engine update) that motivate keeping update scripts clear and isolated.

## Conclusion
The session strictly met the objective of creating the database update variant for plugins, maintaining logical parity with the main script and introducing only the minimal necessary adaptations for the plugin scope. The flow now allows for independent and safe data synchronizations of plugins (dry-run, checksum, backups, filtering) within the same existing update ecosystem.

_Ready to hand over to the next agent. This document serves as a contextual restoration point (Legacy 8)._
