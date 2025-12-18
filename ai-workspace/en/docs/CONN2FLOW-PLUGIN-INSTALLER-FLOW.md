# Plugin Installer Flow (Phase 1)

## Objective
Manage plugin installation/update from local ZIP artifact (`upload` origin), public/private GitHub or local path, completely replacing plugin files and dynamically synchronizing declared resources.

## Phase 1 Scope
- Supported origins: `upload` (local ZIP), `github_publico`, `github_privado`, `local_path`.
- Automatic detection of all `*Data.json` files in the plugin's `db/data/` directory.
- Support for any table via `*Data.json` files (not limited to hardcoded list).
- Automatic backup of previous version before overwriting.
- Checksum calculation (SHA-256) for HTML/CSS resources when absent.
- Support for Data.json + modules (module-id.json).
- Automatic cleanup of `db/` folder after processing.
- Automatic file permission correction.

## Locations
- Main code: `gestor/bibliotecas/plugins-installer.php`
- CLI Orchestration: `gestor/controladores/plugins/atualizacao-plugin.php`
- Logs: `gestor/logs/plugins/installer.log`
- Backups: `gestor/plugins/_backups/<slug>-YYYYMMDD-HHMMSS.zip`
- Staging: `gestor/temp/plugins/<slug>/`

## Updated Pipeline
1. Parameter / origin validation.
2. Download/copy package to staging (`temp/plugins/<slug>/`).
3. ZIP extraction in staging.
4. Validation of manifest.json and structure.
5. Backup of previous installation (if exists).
6. Moving files to final directory (`plugins/<slug>/`).
7. Migration execution (if enabled).
8. **Automatic detection of all `*Data.json`** in `db/data/` directory.
9. Granular resource synchronization for each detected file.
10. Module synchronization (searches `modules/*/module-id.json`).
11. **Cleanup of `db/` folder of installed plugin**.
12. **Permission correction (recursive chown)**.
13. Persistence / metadata update in `plugins` table.
14. Final logging and exit code.

## Dynamic Data.json Detection
- **Before**: Hardcoded list of specific files.
- **Now**: Use of `glob('*Data.json')` to automatically detect all files.
- **Conversion**: Function `tabelaFromDataFile()` converts filename to table name.
- **Examples**:
  - `ModulosData.json` → table `modulos`
  - `ExampleTableData.json` → table `example_table`
  - `HostsConfiguracoesData.json` → table `hosts_configuracoes`

## Resources and Checksums
For layouts, pages, and components:
- Tried paths for each ID:
  - `<type>/<id>.html` and `<type>/<id>.css`
  - `<type>/<id>/index.html` and `<type>/<id>/index.css`
- If found, generates:
```
checksum: {
  html: <sha256 html or null>,
  css: <sha256 css or null>,
  combined: sha256(html_hash + ':' + css_hash)
}
```
- If `checksum` already exists in Data.json, it remains.

## Post-Installation Cleanup
After complete processing:
- **Removal of `db/` folder**: Avoids garbage in installed plugin, as Data.json have already been processed.
- **Permission correction**: Recursive `chown -R` using owner/group of parent folder (`plugins/`).

## Adoption (Orphan Adoption)
On insertion:
- If existing record for the same natural key (initially ignoring `plugin` and sometimes `module`), and is without `plugin`, the record is "adopted" (updates `plugin` + fields).
- Avoids duplications in reprocessings.

## Important Decisions
- DO NOT remove database records in this phase (future cleanup: Phase 2).
- Backup always before replacing, ensuring simple manual rollback.
- Dynamic detection allows plugins to update any table.
- Automatic cleanup avoids accumulation of unnecessary files.
- Permission correction ensures consistency in the environment.

## Main Logs
Examples:
- `[ok] backup created at <path>`
- `[ok] Detected multi-file data mode (layouts, paginas, componentes, variaveis, modulos)`
- `[ok] sync modules plugin=<slug> modules=1 inserts=0 updates=1 skipped=0`
- `[ok] multi-data synchronized plugin=<slug> inserts=3 updates=12 skipped=0`
- `[ok] folder db/ removed from installed plugin`
- `[ok] permissions corrected to www-data:www-data`

## Possible Next Phases
1. Cleanup of orphan / old duplicate records.
2. Enforcing UNIQUE constraints after sanitation.
3. Strategy for removal or archiving of resources no longer present in the package.
4. Detailed diff reports (similar to system update scripts).
5. Support for dependencies between plugins.

## GitHub Release Download (Updated)

### Private Repository Support
The system now supports downloading releases from private GitHub repositories via token authentication.

#### Updated Download Flow
1. **Release Discovery**: Searches for the latest plugin tag in the GitHub repository
2. **Asset Detection**: Searches for `gestor-plugin.zip` file in release assets
3. **Secure Download**: 
   - **Public**: Uses direct download URL (`/releases/download/`)
   - **Private**: Uses assets REST API (`/releases/assets/{id}`) with `Accept: application/octet-stream`

#### SHA256 Integrity Verification (Corrected)
The system now supports **mandatory integrity verification** for downloads from private repositories via SHA256 files.

##### Downloaded Files for Private Repositories
- **`gestor-plugin.zip`** - Main plugin ZIP file
- **`gestor-plugin.zip.sha256`** - SHA256 checksum file for integrity verification

##### Verification Process
1. **ZIP Download**: Uses assets REST API (`/releases/assets/{id}`) with authentication
2. **SHA256 Download**: Constructs URL based on ZIP asset
3. **Checksum Calculation**: Computes SHA256 of downloaded file
4. **Comparison**: Verifies if calculated checksum matches expected
5. **Validation**: Only proceeds if checksums match, otherwise aborts

##### Corrected Download URLs
- **Asset API (Private)**: `https://api.github.com/repos/{owner}/{repo}/releases/assets/{asset_id}`
- **Direct Download (Private)**: `https://github.com/{owner}/{repo}/releases/download/{tag}/gestor-plugin.zip.sha256`
- **Public**: `https://github.com/{owner}/{repo}/releases/download/{tag}/gestor-plugin.zip`

##### Headers for Private Assets
```http
Authorization: token YOUR_TOKEN
Accept: application/octet-stream
User-Agent: Conn2Flow-Plugin-Manager/1.0
```

##### Verification Logs
```
[DOWNLOAD] Private repository detected - downloading both files (ZIP + SHA256)
[DOWNLOAD] URLs based on API asset: ZIP and SHA256
[DOWNLOAD] Downloading ZIP file...
[DOWNLOAD] Downloading SHA256 file...
[CHECKSUM] Expected checksum: [hash]
[CHECKSUM] Calculated checksum: [hash]
[CHECKSUM] ✓ Checksums match
[DOWNLOAD] ✓ Checksum verified successfully
```

##### Implemented Security
- **Man-in-the-Middle Protection**: Mandatory integrity verification
- **Automatic Abort**: Download cancelled if checksum does not match
- **Detailed Logs**: Complete tracking of verification process
- **Compatibility**: Maintains functionality for public repositories

### Download Logs
```
[PLUGIN:plugin-id] [DISCOVERY] Searching releases in https://github.com/owner/repo
[PLUGIN:plugin-id] [DISCOVERY] Tag found: plugin-name-v1.0.0
[PLUGIN:plugin-id] [DISCOVERY] Asset found: gestor-plugin.zip (ID: 123456)
[PLUGIN:plugin-id] [DOWNLOAD] Starting download via assets API
[PLUGIN:plugin-id] [DOWNLOAD] Download completed: 2.5 MB
[PLUGIN:plugin-id] [DOWNLOAD] File saved at: /temp/plugin.zip
```

## Tests
Utility script: `gestor/tests/plugin-counts.php` for resource counting per plugin.
Used flow:
```
# Complete test with dynamic detection:
php gestor/controladores/plugins/atualizacao-plugin.php --id=test-plugin --origem_tipo=local_path --local_path=/var/www/sites/localhost/conn2flow-gestor/plugins/test-plugin
```

## Notes
- If ZIP does not contain `Data.json`, installation continues but without resource synchronization.
- Backups are cumulative; retention policy may be applied in the future.
- System now supports installation of plugins that update any database table via `*Data.json`.
- Automatic cleanup of `db/` folder avoids unnecessary files in installed plugin.
- Permission correction ensures files have correct owner/group of the environment.
