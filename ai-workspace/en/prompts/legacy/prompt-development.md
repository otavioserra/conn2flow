````markdown
# Prompt: Feature Development - Conn2Flow

## 🎯 Initial Context
```
You are developing a new feature for Conn2Flow.

IMPORTANT: Read these files first:
- `ai-workspace/docs/CONN2FLOW-KNOWLEDGE-SYSTEM.md`
- `ai-workspace/docs/CONN2FLOW-[AREA]-DETAILED.md` (related to the feature)
```

## 📋 Feature Information
- **Feature Name:** [FEATURE_NAME]
- **Module/Area:** [RESPONSIBLE_MODULE]
- **Priority:** [HIGH/MEDIUM/LOW]
- **Deadline:** [DEADLINE_DATE]

## 🎯 Feature Specification

### Objective
[Clearly describe what the feature should do]

### Functional Requirements
- [ ] [Requirement 1]
- [ ] [Requirement 2]
- [ ] [Requirement 3]

### Technical Requirements
- [ ] PHP 7.4+ compatibility
- [ ] Follow the project's MVC standards
- [ ] Inline documentation in the code
- [ ] Validation tests

### Interface/UX
[Describe how the user will interact with the feature]

## 📁 Expected Files

### New Files
- `manager/modules/[module]/[feature].php`
- `manager/controllers/[feature]Controller.php`
- `assets/interface/[feature]/`

### Files to Modify
- `manager/config.php` (if necessary)
- `manager/manager.php` (route registration)
- `db/migrations/` (if there are database changes)

## 🔧 Useful Commands
```
- "Analyze the structure of similar modules"
- "Show examples of existing controllers"
- "Search for similar implementations in the project"
- "Validate compatibility with existing modules"
```

## ✅ Development Checklist

### Phase 1: Planning
- [ ] Analyze similar modules
- [ ] Define file structure
- [ ] Plan database changes (if necessary)
- [ ] Validate with existing documentation

### Phase 2: Implementation
- [ ] Create basic structure
- [ ] Implement main logic
- [ ] Create user interface
- [ ] Integrate with existing system

### Phase 3: Validation
- [ ] Test basic functionality
- [ ] Validate integration with other modules
- [ ] Check compatibility
- [ ] Document code

### Phase 4: Finalization
- [ ] Update technical documentation
- [ ] Create/update migrations if necessary
- [ ] Prepare release notes
- [ ] Validate in test environment

---
**Date:** $(date)
**Developer:** Otavio Serra
**Type:** Feature Development
**Project:** Conn2Flow v1.4+

````