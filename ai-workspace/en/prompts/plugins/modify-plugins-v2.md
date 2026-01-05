```markdown
# Plugin System – Phase 2+ Planning

Complementary document to MVP (Phase 1). Do not implement before stabilizing at least 1 plugin in test environment.

## Advanced Security
- Table `plugins_credenciais` (id, type, alias, encrypted_secret, created_at, updated_at)
- AES-256-GCM encryption (key via ENV) or libsodium
- MIME validation (finfo) + configurable size limit
- Optional signature (GPG) – package.zip + package.zip.sig
- Blacklist: `.env`, root `composer.json`, sensitive files

## Data / Model
Additional fields in `plugins`:
- origin_token_ref
- last_execution_log
- last_verification
- removed_at (soft delete)
 - description (optional future)
 - composite index (origin_type, origin_reference)

Optional table `plugins_logs` for audit granularity.

## plugin in Resources
Add `plugin` (nullable) to tables: layouts, paginas, componentes, variaveis.
Reasons: clean uninstallation, rollback, filtering.
Recommended indices: (plugin), (plugin, id).

## Shared Resources Refactoring
Create `bibliotecas/recursos.php` with reusable functions (checksum calculation, version increment, Data.json export).
Providers: CoreResourceProvider / PluginResourceProvider.

## Automatic Updates
- Cron/Scheduler to check new tags (GitHub API)
- Policies: auto | notify | manual
- Last check record (`last_verification`).

## Rollback
- Store ZIPs by version in `backups/plugins/<slug>/<version>.zip`
- Command: `atualizacao-plugin.php --rollback --plugin=<slug> --version=X`
- Verify checksum before applying rollback.

## Dependencies Between Plugins
- Manifest: `dependencies: [{ id, min, max }]`
- Resolve graph (topological sort) before batch install.
- Block installation if dependency missing or version incompatible.

## Metrics / Telemetry
- Events: install_success, install_fail, update_nochange, update_changed, rollback_exec
- Persist in table or export periodic JSON.

## Complete Uninstallation
Flow:
1. Mark plugin as removing
2. Backup directory
3. Remove records with plugin
4. Delete directory
5. Register final log

## Post-Installation Scripts
- Execute `post_installation_scripts` sequentially with timeout and configurable stop-on-error.

## Specific Plugin Migrations
- Directory: `plugin/db/migrations/`
- Prefix: `<slug>_<timestamp>_<description>.php`
- Suggested separate table: `phinxlog_plugins` for organization.

## Versioned Assets
- Optional structure: `assets/<version>/...` for cache busting.

## Future CLI Extensions
- `--verify` integrity
- `--list` installed plugins
- `--diff` installed manifest vs new package
- `--prune` cleanup of old backups

## Scenario Matrix (P2+)
| Scenario | Result |
|----------|--------|
| Update without change | Skip synchronization |
| Downgrade blocked | Require --force |
| Missing dependency | Abort |
| Rollback success | Restore previous version |

## Risks & Mitigations
- ID collision → mandatory prefix policy
- Token leakage → encryption + masking
- Partial corruption → atomic staging + checksums
 - Inconsistent target directory → consolidate convention (`gestor/plugins/`)

## Pending Items Before Starting P2
1. Confirm dependency policy
2. Choose default encryption mechanism
3. Decide on GPG signature adoption
4. Prioritization: security vs rollback vs dependencies

---
Generated on 09/02/2025.
```