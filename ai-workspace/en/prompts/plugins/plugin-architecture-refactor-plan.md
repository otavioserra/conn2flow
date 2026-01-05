```markdown
- [x] **Complete Architectural Analysis**
  - [x] Read and analyze `atualizacao-plugin.php` (orchestrator)
  - [x] Read and analyze `plugins-installer.php` (installer with duplicated logic)
  - [x] Read and analyze `atualizacoes-banco-de-dados.php` (robust DB system)
  - [x] Identify database logic duplication
  - [x] Confirm that `atualizacoes-banco-de-dados.php` has full support for natural keys

- [x] **Refactoring plugins-installer.php**
  - [x] Remove duplicated upsert functions (`plugin_upsert_*`)
  - [x] Replace upsert calls with delegation to `atualizacoes-banco-de-dados.php`
  - [x] Keep only file processing and migration logic
  - [x] Create wrapper function to call the database system
  - [x] Modify `plugin_sync_datajson_multi` to use delegation

- [x] **Integration with atualizacoes-banco-de-dados.php**
  - [x] Modify `plugin_sync_datajson_multi` to delegate DB operations
  - [x] Create communication interface between systems
  - [x] Ensure checksums and logs are preserved
  - [x] **FIX**: Function `plugin_delegate_database_operations` was missing - added and tested successfully

- [ ] **Tests and Validation**
  - [x] Test plugin installation with natural key (`usuarios_perfis_modulos`)
  - [x] Verify that DB operations work correctly
  - [x] Validate logs and installation reports
  - [x] Test update vs new installation scenarios

- [x] **Documentation and Cleanup**
  - [x] Update comments explaining the new architecture
  - [x] Remove obsolete and duplicated code
  - [x] Document interface between systems

## 🔧 Identified and Fixed Problems

### ❌ Problem: Missing Function `plugin_delegate_database_operations`
- **Symptoms**: Process crashed with fatal error "Call to undefined function"
- **Cause**: Function was created but not saved correctly in the file
- **Solution**: Added the complete function with delegation to the robust system
- **Result**: Installation now works correctly

### ✅ Final Verification
- **Successful Test**: Plugin installed with code 0 (OK)
- **Delegation working**: Robust DB system was called correctly
- **Logs preserved**: All traceability maintained
- **Processed tables**: components, layouts, modules, pages, usuarios_perfis_modulos, variables
```