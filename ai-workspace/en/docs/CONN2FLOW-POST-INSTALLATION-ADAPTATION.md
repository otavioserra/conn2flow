# CONN2FLOW - POST-INSTALLATION PHASE: MANAGER ADAPTATION

## 📋 CONTEXT

After the complete implementation of the **hybrid multilingual system** and the first release v1.8.5+, it will be necessary to adapt the manager code to work with the new multilingual database structure.

## ⚠️ BREAKING CHANGES IMPLEMENTED

### Updated Database Structure

**BEFORE (Old Tables - REMOVED):**
- `layouts` (field: `id_layouts`)
- `paginas` (field: `id_paginas`) 
- `componentes` (field: `id_componentes`)

**AFTER (Multilingual Tables - IMPLEMENTED):**
- `layouts` (field: `layout_id` + `language`)
- `pages` (field: `page_id` + `language`)
- `components` (field: `component_id` + `language`)

### New Mandatory Fields
- `language` - Resource language (e.g., 'pt-br', 'en', 'es')
- Hybrid fields: `html_modified`, `html_version`, `css_modified`, `css_version`

## 🎯 TASKS FOR COMPLETE ADAPTATION

### Phase 1: Mapping and Analysis 🔍
- [ ] **Map all SQL queries** referencing old tables
- [ ] **Identify PHP files** using `paginas`, `layouts`, `componentes`
- [ ] **List administrative interfaces** that need updating
- [ ] **Catalog relationships** between affected tables

### Phase 2: SQL Query Updates 🗄️
- [ ] **Update SELECT queries** to include filter `WHERE language = 'pt-br'`
- [ ] **Modify INSERT statements** to include `language` field
- [ ] **Fix UPDATE statements** to work with new IDs
- [ ] **Adjust DELETE operations** for multilingual structure
- [ ] **Review JOINs** between related tables

### Phase 3: Interface Adaptation 🖥️
- [ ] **Admin Layouts**: Adapt listing and forms
- [ ] **Admin Pages**: Update complete CRUD
- [ ] **Admin Components**: Modify component management
- [ ] **Administrative menus**: Adjust navigation
- [ ] **Resource selectors**: Update dropdowns/selects

### Phase 4: Libraries and Helper Functions ⚙️
- [ ] **Function `gestor_layout()`**: Adapt for multilingual
- [ ] **Function `gestor_pagina()`**: Update references
- [ ] **Function `gestor_componente()`**: Modify for new structure
- [ ] **Resource cache**: Implement for multilingual
- [ ] **Reference validation**: Ensure integrity

### Phase 5: Tests and Validation ✅
- [ ] **Interface tests**: Verify all administrative screens
- [ ] **Functionality tests**: Create, edit, delete resources
- [ ] **Performance tests**: Validate multilingual queries
- [ ] **Integrity tests**: Verify relationships
- [ ] **Regression tests**: Ensure existing functionalities

## 🔧 CRITICAL FILES TO REVIEW

### Core Libraries
```
gestor/bibliotecas/gestor.php
gestor/bibliotecas/interface.php
gestor/bibliotecas/banco.php
gestor/bibliotecas/modelo.php
```

### Administrative Modules
```
gestor/modulos/admin-layouts/
gestor/modulos/admin-paginas/
gestor/modulos/admin-componentes/
gestor/modulos/admin-templates/
```

### Controllers
```
gestor/controladores/
```

### Configurations
```
gestor/configuracoes/
```

## 📝 MIGRATION PATTERNS

### Example: Old Query → New

**BEFORE:**
```sql
SELECT * FROM paginas WHERE status = 'A'
```

**AFTER:**
```sql
SELECT * FROM pages WHERE status = 'A' AND language = 'pt-br'
```

### Example: Helper Function

**BEFORE:**
```php
function buscar_pagina($id) {
    return banco_select("SELECT * FROM paginas WHERE id_paginas = $id");
}
```

**AFTER:**
```php
function buscar_pagina($id, $language = 'pt-br') {
    return banco_select("SELECT * FROM pages WHERE page_id = $id AND language = '$language'");
}
```

## 🚨 ATTENTION POINTS

### Critical Relationships
- **Pages → Layouts**: Now uses `layout` (string) instead of `id_layouts` (int)
- **Components → Modules**: Maintain compatibility with module structure
- **Menus → Pages**: Adapt references for new IDs

### Performance
- **Indexes**: Verify if multilingual indexes are optimized
- **Cache**: Implement cache by language
- **N+1 Queries**: Avoid unnecessary queries by language

### Compatibility
- **Existing data**: Ensure correct migration
- **Customizations**: Preserve user modifications
- **Plugins**: Verify compatibility with multilingual system

## 🔍 AUTOMATIC DETECTION SCRIPT

### Search Old References
```bash
# Search references to old tables
cd gestor/
grep -r "paginas" --include="*.php" .
grep -r "id_paginas" --include="*.php" .
grep -r "layouts" --include="*.php" . | grep -v "admin-layouts"
grep -r "id_layouts" --include="*.php" .
grep -r "componentes" --include="*.php" . | grep -v "admin-componentes"
grep -r "id_componentes" --include="*.php" .
```

### Verify SQL Queries
```bash
# Search SQL queries that need updating
cd gestor/
grep -r "SELECT.*FROM paginas" --include="*.php" .
grep -r "INSERT INTO paginas" --include="*.php" .
grep -r "UPDATE paginas" --include="*.php" .
grep -r "DELETE FROM paginas" --include="*.php" .
```

## 📊 PROGRESS METRICS

### Validation Checklist
- [ ] **0 references** to old tables found
- [ ] **100% of administrative interfaces** functional
- [ ] **All CRUDs** operational with multilingual
- [ ] **Performance maintained** or improved
- [ ] **Automated tests** passing

### Success KPIs
- **Load time**: ≤ 500ms for main interfaces
- **SQL Queries**: Optimized with language filter
- **Compatibility**: 100% with existing functionalities
- **Stability**: 0 critical errors after migration

## 🎯 SUGGESTED SCHEDULE

### Week 1: Analysis and Mapping
- Identify all old references
- Map affected interfaces
- Plan migration order

### Week 2: Core Adaptation
- Update main libraries
- Modify helper functions
- Implement multilingual filters

### Week 3: Administrative Interfaces
- Adapt admin-* modules
- Update forms and listings
- Test basic functionalities

### Week 4: Tests and Optimization
- Complete system tests
- Performance optimization
- Documentation of changes

## 📞 POST-MIGRATION SUPPORT

### Debug Logs
```php
// Activate detailed logs
$_GESTOR['debug']['multilingual'] = true;
$_GESTOR['debug']['sql_queries'] = true;
```

### Integrity Verification
```bash
cd gestor/resources
php validate.pre.release.php --check-references
```

### Monitoring
- PHP error logs
- MySQL slow query log
- Administrative interface performance

---

**Document created**: August 8, 2025
**System version**: v1.8.5+ (Multilingual System)
**Next review**: After first installation test
