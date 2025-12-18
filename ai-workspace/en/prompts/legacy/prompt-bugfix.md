````markdown
# Prompt: Bug Fix - Conn2Flow

## 🐛 Bug Context
```
You are fixing a bug in the Conn2Flow system.

IMPORTANT: Read first:
- `ai-workspace/docs/CONN2FLOW-KNOWLEDGE-SYSTEM.md`
- `ai-workspace/docs/CONN2FLOW-[AREA]-DETAILED.md` (bug area)
```

## 📋 Bug Information

### Identification
- **ID/Ticket:** [TICKET_NUMBER]
- **Title:** [BUG_TITLE]
- **Severity:** [CRITICAL/HIGH/MEDIUM/LOW]
- **Affected Area:** [MODULE/COMPONENT]

### Problem Description
[Describe in detail the incorrect behavior]

### Bug Reproduction
1. [Step 1 to reproduce]
2. [Step 2 to reproduce]
3. [Step 3 to reproduce]
4. **Result:** [What goes wrong]

### Expected Behavior
[Describe how it should work correctly]

### Environment Information
- **PHP:** [version]
- **MySQL:** [version]
- **Server:** [Apache/Nginx]
- **OS:** [Windows/Linux]
- **Browser:** [if applicable]

## 🔍 Initial Investigation

### Error Logs
```
[Paste relevant logs here]
```

### Suspicious Files
- `[file1.php]` - [reason for suspicion]
- `[file2.php]` - [reason for suspicion]

### Related Code
[Point out functions, classes, or modules that may be involved]

## 📁 Relevant Files

### For Analysis
- `manager/libraries/[library].php`
- `manager/modules/[module]/[file].php`
- `manager/controllers/[controller].php`
- `manager/config.php`

### Logs and Debug
- `manager-installer/installer.log`
- `manager/logs/` (if it exists)
- Browser console (F12)

## 🔧 Useful Commands
```
- "Search for [error_term] throughout the project"
- "Analyze the function [function_name] in [file]"
- "Show recently modified files"
- "Execute script ai-workspace/scripts/check-installation.php"
```

## 🛠️ Correction Plan

### Phase 1: Diagnosis
- [ ] Reproduce the bug locally
- [ ] Analyze error logs
- [ ] Identify root cause
- [ ] Map affected code

### Phase 2: Solution
- [ ] Develop correction
- [ ] Test correction in isolation
- [ ] Check for side effects
- [ ] Validate in different scenarios

### Phase 3: Validation
- [ ] Test original scenario
- [ ] Test edge cases
- [ ] Check for regressions
- [ ] Validate performance

### Phase 4: Documentation
- [ ] Document root cause
- [ ] Update code with comments
- [ ] Update documentation if necessary
- [ ] Prepare release notes

## ⚠️ Special Considerations

### Compatibility
- [ ] Do not break existing functionalities
- [ ] Maintain compatibility with supported PHP versions
- [ ] Check impact on related modules

### Security
- [ ] Do not introduce vulnerabilities
- [ ] Maintain proper sanitization
- [ ] Preserve existing validations

### Performance
- [ ] Do not degrade performance
- [ ] Optimize if possible
- [ ] Consider impact on database queries

---
**Date:** $(date)
**Developer:** Otavio Serra
**Type:** Bug Fix
**Project:** Conn2Flow v1.4+

````