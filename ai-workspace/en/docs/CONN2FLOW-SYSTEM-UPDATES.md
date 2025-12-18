# Conn2Flow - System Updates (Phase 1)

## Objective
Provide an automated mechanism to update the `gestor/` core with:
- Download (or local use) of `gestor.zip` artifact
- Optional integrity check (SHA256)
- Selective wipe (keeping protected directories)
- Simplified deploy (move/rename + fallback copy)
- Additive merge of `.env` with detection of new and deprecated variables
- Execution of unified database update script (`atualizacoes-banco-de-dados.php`)
- Export of JSON plan with statistics
- Persistence of executions in `atualizacoes_execucoes` table
- CLI or Web execution (incremental via AJAX)
- Retention/housekeeping of logs and temporary files

## Directories and Files Involved
```
/gestor/controladores/atualizacoes/
  atualizacoes-sistema.php         # Main orchestrator (CLI + Web)
  atualizacoes-banco-de-dados.php  # Unified database script (called inline or via process)
/logs/atualizacoes/                # Daily logs and JSON plans
/temp/atualizacoes/                # Staging + web sessions
/temp/atualizacoes/sessions/       # <sid>.json + <sid>.log (incremental web)
/backups/atualizacoes/             # (optional) full backups (--backup)
```

## CLI Flow (Summary)
1. Bootstrap (download/extract + possible re-execution if script updated)
2. Deploy (wipe + move + merge .env)
3. Database (optional according to flags)
4. Cleanup + final report + retention

## Incremental Web Flow
Actions (param `action`):
- `start`: prepares staging + downloads/extracts ZIP + validates + creates session
- `deploy`: applies wipe+deploy + merge .env + plan
- `db`: executes database script (skips if `only-files` or `no-db`)
- `finalize`: staging cleanup + finalizes persistence
- `status`: returns state + aggregated log
- `cancel`: marks execution as canceled

State: `temp/atualizacoes/sessions/<sid>.json`
```
{
  "sid": "...",
  "exec_id": 15,
  "step": "deploy_files_done",
  "opts": { ...flags... },
  "release_tag": "gestor-v1.14.0",
  "checksum": { expected, got },
  "staging_dir": "/var/www/.../temp/atualizacoes/20250827-.../",
  "staging_root": "...",
  "progress": { bootstrap, deploy_files, database, finalize },
  "stats": { removed, copied },
  "finished": false
}
```

## Protected Directories (not removed in wipe)
```
contents/ logs/ backups/ temp/ autenticacoes/
```
Reasons:
- `autenticacoes/` contains specific instances (.env, keys, configs)
- `logs/`, `backups/`, `temp/` preserve history and staging
- `contents/` (future) will preserve uploads/dynamic assets

## Statistics
Record in JSON plan and table:
- `stats_removed`: removed entries (unprotected files/folders)
- `stats_copied`: moved items (rename or copy fallback)

Table `atualizacoes_execucoes` (relevant fields):
```
session_id, modo, release_tag, checksum,
env_added, stats_removed, stats_copied,
status (running|success|error), exit_code,
plan_json_path, log_file_path, session_log_path,
started_at, finished_at, created_at, updated_at
```

## Main Flags (CLI)
```
--tag=gestor-vX.Y.Z     # forces specific tag
--local-artifact        # uses local artifact (docker / conn2flow-github)
--only-files | --only-db | --no-db
--dry-run               # does not apply deploy/database
--backup                # full backup before wipe
--no-verify             # ignores SHA256 checksum
--download-only         # generates staging/plan without applying
--logs-retention-days=N # default 14 (0 disables)
--debug                 # DEBUG logs
```

## Merge .env
- Reads template in `autenticacoes.exemplo/dominio/.env` (attempt order: domain, localhost, literal domain)
- Adds new variables at the end with block `# added-by-update YYYY-mm-dd`
- Lists deprecated variables (only in log)

## Database
Unified script allows:
- Passed flags: `--force-all`, `--tables=...`, `--log-diff`, `--dry-run`
- External execution (CLI) or inline (web)
- After success removes `gestor/db/` (migrations + seeds) reducing surface

## Housekeeping
- Temporary directories >24h removed
- Logs / plans beyond retention deleted
- Staging removed at the end (except `--keep-temp` / dry-run preserves by design)

## Persistence & Resilience
- Start records `running` line
- Partials update stats/env_added
- Finalizes with `success` or `error` (exit_code)
- Fallback loads stats from last JSON plan if context lost reference

## Errors & Exit Codes
```
0 OK
1 Generic
2 DownloadException
3 ExtractionException
4 EnvMergeException
5 DatabaseUpdateException
6 (reserved rollback)
7 IntegrityException (checksum)
```

## Security & Permissions
Diagnosed problem: directories/artifacts with `root` owner prevented rename/unlink forcing fallback.
Operational solution: ensure `www-data:www-data` ownership for installation directories and artifacts.
Additional recommendation in deploy: script/infra ensure `chown -R www-data:www-data` after artifact copy.

## Lessons Learned (Debug Sentinel)
- Root cause of non-update was permission, not move logic.
- Instrumentation (sentinel hashes) removed after validation.
- Kept only essential logging (no deep hash).

## Possible Evolutions (Future Phases)
- Transactional rollback (files + database diff dump)
- Post-deploy validation by checksum manifest
- Incremental updates (diff) to reduce downtime
- REST API for remote orchestration
- Digital signature of artifacts (beyond SHA256)
- Distributed execution lock (cluster)

---
Document maintained by GitHub Copilot AI
Last update: 2025-08-27
