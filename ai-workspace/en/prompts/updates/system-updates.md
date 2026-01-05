````markdown
# Prompt Interactive Programming - System Updates (Controller: atualizacoes-sistema.php)

> Updated to SIMPLIFIED MODEL: Total overwrite + ZIP package SHA256 verification. Old diff / conflict sections have been deprecated and will be replaced below.

## 🤖 AI Agent - Responsibilities
- **Development**: Create, evolve, and maintain the system update controller script (`atualizacoes-sistema.php`).
- **Continuous Refinement**: Adjust this prompt as feedback / new needs arise.
- **GIT**: Use exclusively internal scripts for versioning. Use for release: `bash ./ai-workspace/git/scripts/release.sh ${input:tipo} \"${input:tagMsg}\" \"${input:commitMsg}\"`; use only for commit: `bash ./ai-workspace/git/scripts/commit.sh \"${input:commitMsg}\"`.
- **Docker**: Execute and validate routines inside the application container. Use to send updated data to the test environment: `bash docker/utils/sincroniza-gestor.sh checksum`. Then use `docker exec conn2flow-app bash -c \"php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-sistema.php\"` to test.
- **Quality**: Ensure clear logs, idempotency when possible, and security (do not inadvertently remove / overwrite critical files).

## 🎯 Current Context (Simplified Model)
Objective: Update Conn2Flow in a direct, secure, and reproducible way, prioritizing simplicity over granularity.

Main flow:
1. Discover latest tag (or use `--tag`).
2. Download `gestor.zip`.
3. Download `gestor.zip.sha256` and validate SHA256 (unless `--no-verify`).
4. Extract complete content into temporary staging.
5. (Optional `--backup`) Create FULL snapshot (excludes ignored dirs) before any changes.
6. TOTAL overwrite of files (removal of orphans) preserving `contents/`, `logs/`, `backups/`, `temp/`.
7. Additive merge of `.env` (only adds new keys, logs deprecated ones).
8. Execute database update (except modes that disable it).
9. Export JSON plan with aggregated statistics + checksum + env merge.
10. Optional staging cleanup (`--clean-temp`).

The previous model based on diff and conflicts was REMOVED.

This file (PROMPT) is the LIVING SPECIFICATION. The agent must keep it consistent with the actual implementation.

## 🧪 Testing Environment
- Docker Config: `docker/dados/docker-compose.yml`
- Mounted installation (host): `docker/dados/sites/localhost/conn2flow-gestor`
- Installation in container: `/var/www/sites/localhost/conn2flow-gestor/`
- Web installer for reference: `http://localhost/instalador/`
- Tools:
    - Manager code synchronization: `docker/utils/sincroniza-gestor.sh checksum`
    - Useful commands: `docker/utils/comandos-docker.md`
- Example execution inside container:
    - `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-sistema.php --debug"`
- Update logs should go to: `gestor/logs/atualizacoes/` (prefix: `atualizacoes-sistema-YYYYmmdd.log`).

## 🗃️ GIT Repository
- Commit / tag always via: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`
- Never run manual git (push/pull) directly.
- Update module commit messages must start with prefix: `update-system:`

## ⚙️ Implementation Settings
Standard variables / paths (derived at runtime):

| Concept | Value / Rule |
|---------|--------------|
| Manager base | `realpath(dirname(__FILE__) . '/../../') . DIRECTORY_SEPARATOR` |
| Temporary staging folder | `<base>/temp/atualizacoes/<timestamp>/` |
| Destination zip file | `gestor.zip` inside staging folder |
| Main log | `<base>/logs/atualizacoes/atualizacoes-sistema.log` (with rotation by date) |
| Backups folder (files) | `<base>/backups/atualizacoes/arquivos/<timestamp>/` |
| Backups folder (future db) | `<base>/backups/atualizacoes/db/` |
| New .env template | `<staging>/autenticacoes.exemplo/dominio/.env` |
| Current productive .env | `<base>/autenticacoes/<env-dir>/.env` |

`.env` Rules (merge):
1. DO NOT overwrite existing user values.
2. Add new variables to the end keeping comments (lines starting with `#` associated immediately above the key).
3. Preserve original order of current `.env`.
4. If a key exists in template but absent in current → add with template value + comment `# added-by-update YYYY-mm-dd`.
5. Detect keys removed in template only by LOGGING (do not remove from user file).

Minimum set of critical keys to monitor (for alert if absent): `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `OPENSSL_PASSWORD`, `USUARIO_HASH_PASSWORD`, `URL_RAIZ`.

Execution Modes:
- FULL (default) → files + env merge + database.
- --only-files → only file update + env merge.
- --only-db → only database.
- --no-db → same as FULL but without database (deprecates `--only-files`).

Supported CLI Flags / Options (simplified version):
```
--tag=GESTOR_TAG        # Uses specific release (ex: gestor-v1.2.3)
--version=GESTOR_TAG    # Alias of --tag
--domain=DOMAIN         # Domain / environment name (folder autenticacoes/<domain>)
--env-dir=DOMAIN        # Alias
--download-only         # Downloads (and verifies) + extracts without applying overwrite
--skip-download         # Uses existing zip in staging (debug)
--dry-run               # Simulates (does not copy/remove). Stats not yet simulated.
--backup                # FULL snapshot before overwrite
--only-files            # Only files + env merge
--only-db               # Only database
--no-db                 # Same as full without database step
--no-verify             # Disables gestor.zip SHA256 verification
--force-all             # Forwards to database script
--tables=list           # Forwards to database script
--log-diff              # Forwards to database script
--debug                 # Increases verbosity
--clean-temp            # Removes staging at the end (even dry-run)
--logs-retention-days=N # Keeps only N days of logs/JSON plans (default 14, 0 disables)
--help                  # Displays help
```

Fatal errors interrupt execution with code != 0. (JSON Checkpoint still FUTURE.)

### Ignored Directories in File Update
In TOTAL overwrite these directories are preserved (without removal or overwriting):
```
logs/
backups/
temp/
contents/   # uploads and files sent by user (dedicated folder)
```
Justification: `contents/` contains uploads; `logs/` and `backups/` keep history; `temp/` may contain ongoing processes.

### PHP Extension Dependencies (minimal)
Environment must have extensions enabled before running the controller:
```
curl      # release download via GitHub API / assets
zip       # gestor.zip manipulation (ZipArchive)
json      # (already enabled) for plan / structured logs
mbstring  # (future) advanced internationalization
openssl   # (already used in other parts of the system)
```
Without `curl` or `zip` the script will abort in initial steps. Base Docker should already contain.

## 📖 Current Libraries / Helpers
- `logAtualizacao()` / `logErroCtx()`
- `parseArgs()` / `help()` / `validarOpts()`
- `descobrirUltimaTagGestor()` / `downloadRelease()`
- `downloadZipChecksum()` / `verifyZipSha256()`
- `extrairZipGestor()`
- `coletarArquivos()` / `aplicarOverwriteTotal()` / `backupTotal()`
- `mergeEnv()` (additive)
- `executarAtualizacaoBanco()`
- `exportarPlanoJson()` / `renderRelatorioFinal()`
- Hooks: `hookBeforeFiles()`, `hookAfterDb()`, `hookAfterAll()`

## 📝 Guidelines for the Agent
1. Keep this file synchronized with parameter and flow changes.
2. Add new CLI options only after updating FLAGS section.
3. Ensure DocBlock comments in ALL public/internal functions describing:
    - Objective
    - Parameters
    - Return
    - Exceptions
4. All disk writing must validate permissions and report significant errors.
5. Never delete sensitive directories: `autenticacoes/`, `logs/`, `backups/`, `vendor/`.
6. File update must handle conflicts:
    - If file modified locally (heuristic: different checksum + presence of marker `// LOCAL-EDIT`), save `.bak` copy and register conflict.
7. `dry-run` must produce detailed plan (JSON) without applying.
8. Multilingual logging: keep future keys in JSON files (planned) – for now use `__t()` if available; if not, fallback to pt-br text and mark `TODO:i18n`.
9. Prepare extension points (empty functions `hookBeforeFiles()`, `hookAfterDb()`).
10. Return consistent exit code (CLI): 0=success, 1=generic error, 2=download error, 3=extraction error, 4=env merge error, 5=db error.

## 🧭 Flow (Pseudo-code Total Overwrite)
```
parseArgs(argv)
if help -> print + exit 0
validarOpts()
log start
if !only-db:
    staging = prepararStaging()
    tag = opts.tag || descobrirUltimaTagGestor()
    zip = skip-download ? staging/gestor.zip : downloadRelease(tag, staging)
    if verification active: checksumFile = downloadZipChecksum(tag); verifyZipSha256(zip, checksumFile)
    extrairZipGestor(zip, staging)
    if backup && !dry: backupTotal(base, backupDir, excludes)
    stats = aplicarOverwriteTotal(staging, base, excludes, dry)
    mergeEnv(.envAtual, .envTemplate, context, dry)
    exportarPlanoJson({stats}, context)
if !only-files && !no-db: executarAtualizacaoBanco()
hookAfterAll()
renderRelatorioFinal()
if clean-temp: removerStaging()
exit 0
catch Download -> EXIT_DOWNLOAD
catch Extraction -> EXIT_EXTRACTION
catch EnvMerge -> EXIT_ENV_MERGE
catch Database -> EXIT_DB_ERROR
catch Integrity -> EXIT_INTEGRITY
catch Generic -> EXIT_GENERIC
```

### `atualizacoes-sistema.php` File Structure
Suggested order:
1. `declare(strict_types=1);`
2. Header comment (purpose, CLI usage, examples)
3. Basic constants / paths
4. Require minimal libs
5. Utility functions (log, io, checksum, etc.)
6. Domain functions (download, extraction, overwrite, env merge, backup, db)
7. Empty hooks
8. `main()` function and CLI dispatch

### Plan / Statistics
Current JSON plan includes: `stats.removed`, `stats.copied`, `stats.total_new`, `env_merge.added`, `env_merge.deprecated`, `checksum`.

### .env Merge (Current Pseudo)
```
curLines = file(.envAtual)
tplLines = file(.envTemplate)
curMap = parseEnvLines(curLines)  # KEY => {value,index}
tplMap = parseEnvLines(tplLines)
added = keys(tplMap) - keys(curMap)
deprecated = keys(curMap) - keys(tplMap)
if !dry-run and added:
    append "\n# added-by-update YYYY-mm-dd" + each KEY=value from template
register in context env_merge.added / deprecated
log summary
```

### Database Execution
Reuse `atualizacoes-banco-de-dados.php` defining:
```
$GLOBALS['CLI_OPTS'] = [
  'env-dir' => <env>,
  'debug' => opts.debug,
  'force-all' => opts.forceAll,
  'tables' => opts.tables,
  'log-diff' => opts.logDiff
];
require 'atualizacoes-banco-de-dados.php';
```

### Internationalization
Include (future) file `controladores/atualizacoes/lang/pt-br.json` and `en.json` with keys:
```
{
  "update_start": "Starting system update",
  "download_release": "Downloading release {tag}",
  "merge_env_added": "Variable added to .env: {key}",
  "merge_env_summary": ".env merge completed. New: {added} | Absent in template: {deprecated}",
  "db_update_start": "Updating database",
  "db_update_done": "Database updated",
  "files_plan": "Files plan - add:{add} update:{upd} conflicts:{conflicts}",
  "update_finished": "Update completed successfully"
}
```

## 🤔 Current Decisions
| Theme | Decision | Status |
|-------|----------|--------|
| Integrity | SHA256 of ZIP mandatory (file gestor.zip.sha256) | Implemented |
| Files Model | Total overwrite + orphan removal | Implemented |
| Conflicts | Not handled (delegated to plugin architecture) | N/A |
| Backup | Optional full snapshot (--backup) | Implemented |
| Rollback | Restore snapshot (future) | Planned |
| JSON Plan | Aggregated statistics + checksum + env merge | Implemented |
| Dry-run Stats | Simulate counts without operations | Pending |
| Logs Retention | Automatic pruning of old logs/plans (--logs-retention-days) | Implemented |
| Old Temp Cleanup | Automatic removal of temp/atualizacoes/ >24h | Implemented |
| gestor/db removal post update | Removes db folder after applying data | Implemented |
| Web Execution / Jobs | Administrative panel / queue | Future |

## ✅ Implementation Progress (Consolidated)
- [x] Initial skeleton / parser / logging
- [x] Tag discovery + ZIP download
- [x] Automatic SHA256 verification
- [x] Staging extraction
- [x] Total overwrite + orphan removal
- [x] Optional full backup
- [x] Additive .env merge
- [x] Database script integration
- [x] JSON Plan (stats + checksum + env)
- [x] Basic dry-run (without simulated stats)
- [x] Exit codes / exception handling
- [x] Optional staging cleanup
- [x] Release workflow update (generates gestor.zip.sha256)
- [x] Update of this specification
- [ ] Manual end-to-end tests new release
- [ ] README / external docs
- [ ] Dry-run with simulated count
- [ ] Rollback planning

### Manual Tests Subprogress (container)
- [x] Download + checksum verified
- [x] Total overwrite with full backup
- [x] .env merge adding key
- [ ] Full execution (with database)
- [ ] Execution with --no-verify (skip integrity)
- [ ] Dry-run execution (validate no modification)

## ☑️ Post-Implementation Process
- [ ] Dry-run (check plan + no real changes)
- [ ] Full execution with new real release tag
- [ ] .env merge (simulated key) validated
- [ ] Database update (flags) validated
- [ ] Execution with --no-verify (warning)
- [ ] Commit via script

## ♻️ Recent Changes
1. Removal of diff/conflicts.
2. Introduction of SHA256 checksum.
3. Total overwrite + orphan removal.
4. Full snapshot backup.
5. Simplified JSON plan.
6. Updated documentation.
7. Automatic cleanup of old temporary directories (>24h).
8. Automatic removal of gestor/db after database update completed.
9. New flag --logs-retention-days for pruning old logs/plans.
10. Normalization of language column (language vs linguagem_codigo) in database script.
11. Removal of gestor.zip/gestor-local.zip artifacts before moving to production.

## ✅ Changes and Corrections Implementation Progress
- [x] Items 1–6 applied
- [ ] Dry-run simulated stats
- [ ] Automatic rollback

## ☑️ Post Changes and Corrections Process
- [ ] Execute again with real scenarios
- [ ] Commit with structured message

---
**Date:** 08/25/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.14.0
**Module:** Update System

## 🆕 News v1.14.0

### Added
- Flag `--logs-retention-days=N` (default 14) for automatic pruning of:
    - `logs/atualizacoes/atualizacoes-sistema-YYYYMMDD.log`
    - `logs/atualizacoes/atualizacoes-bd-YYYYMMDD.log` and variants (`atualizacoes-banco-`).
    - Files `plan-YYYYmmdd-HHMMSS.json`.
- Automatic pruning of old directories in `temp/atualizacoes/` (>24h) preserving current staging.
- Dynamic normalization of language column in database script (`language` or `linguagem_codigo`).
- Prevention of multilingual record duplication via fallback indices.

### Changed
- Deploy removes `gestor.zip` / `gestor-local.zip` artifacts from staging before moving.
- Folder `gestor/db/` removed after data application to reduce divergences and surface.
- CLI help updated with new log retention flag.

### Fixed
- Errors "Unknown column 'language'" in tables where only `linguagem_codigo` existed (detection + unified normalization).
- Indefinite growth of logs/plans without retention policy.
- Zip artifact potentially remaining in root after local update.

### Removed
- Dependency on keeping `gestor/db/` persistent between releases (now transient).

### Notes
1. Retention adjustment: `--logs-retention-days=30` extends history; `0` disables pruning.
2. For prolonged audit of original JSON scripts, copy `gestor/db/` before or implement future preservation flag.
3. Normalization mechanism does not alter schema, only maps at runtime.

### Next Steps (Planned)
- Stats simulation in `--dry-run`.
- Rollback using full snapshot.
- Optional flag to preserve `db` folder after update.

> This prompt is synchronized with the current simplified model. Any evolution (rollback, web UI, queue) must update here first.
````