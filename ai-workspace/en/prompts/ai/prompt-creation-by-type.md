````markdown
# 📝 Prompt Creation Guide by Type - Conn2Flow

## 🎯 Overview

This document serves as the **main recipe** for the systematic creation of ready-made prompts in the Conn2Flow ecosystem. It guides AI agents in building prompt examples that users can use as a basis for their specific needs.

### 🎨 Purpose
- **Standardization**: Ensure consistency in prompt creation
- **Efficiency**: Accelerate the development of new prompts
- **Quality**: Maintain high standard of usability and completeness
- **Scalability**: Facilitate expansion to new types of prompts

---

## 🏗️ General Prompt Structure

### 📋 Essential Components

Each prompt must mandatorily contain:

#### 1. **Identifier Header**
```markdown
# 🎯 [Prompt Type] - [Descriptive Name]

**Version:** 1.0.0
**Date:** YYYY-MM-DD
**Author:** [Name/Author]
**Tags:** [tag1, tag2, tag3]
```

#### 2. **Executive Description**
- **Objective**: What the prompt does (1-2 sentences)
- **Context**: When and why to use it
- **Expected Result**: What will be generated

#### 3. **Input Parameters**
- **Mandatory**: Essential fields
- **Optional**: Complementary fields
- **Validations**: Business rules

#### 4. **Prompt Structure**
- **Instructions**: Clear and sequential steps
- **Examples**: Practical cases
- **Templates**: Reusable structures

#### 5. **Technical Metadata**
- **Dependencies**: Necessary resources
- **Limitations**: Known restrictions
- **Tests**: Validation scenarios

---

## 📂 Organization by Categories

### 🎨 **Interface and UX**
- Responsive layouts
- Interactive components
- Themes and styles
- Navigation and menus

### 📄 **Content and Pages**
- Static pages
- Dynamic forms
- Landing pages
- Administrative dashboards

### 🔧 **Functionalities**
- Business modules
- APIs and integrations
- Automated processes
- Validations and rules

### 📦 **Plugins and Extensions**
- Custom plugins
- External integrations
- Advanced functionalities
- Specific customizations

### 🤖 **Automation and AI**
- Content generation
- Intelligent processes
- Data analysis
- Automatic recommendations

---

## 🔄 Creation Flow

### 📝 **Phase 1: Planning**
1. **Identify Need**: Analyze user demand
2. **Categorize Type**: Classify in the structure above
3. **Define Scope**: Delimit functionalities

### ✍️ **Phase 2: Development**
1. **Structure Base**: Follow standard template
2. **Add Examples**: Include real cases
3. **Document Parameters**: Detail inputs/outputs

### ✅ **Phase 3: Validation**
1. **Test Functioning**: Execute complete prompt
2. **Verify Consistency**: Validate with standards
3. **Document Limitations**: Register restrictions

### 🚀 **Phase 4: Publication**
1. **Version File**: Apply version control
2. **Index in System**: Add to repositories
3. **Communicate Availability**: Notify users

---

## 🗂️ File Structure in Conn2Flow

### 📁 **Prompt Location**
Each module can create prompts organized in the structure:
```
gestor/modulos/{module}/resources/{lang}/ai_prompts/
```

**Practical example:**
```
gestor/modulos/admin-paginas/resources/pt-br/ai_prompts/
```

### 📂 **Hierarchical Organization**
1. **Type Folder**: Resource identifier
   ```
   ai_prompts/paginas/
   ```

2. **Prompt File**: File name = folder name
   ```
   ai_prompts/paginas/paginas.md
   ```

3. **Metadata**: Record in the main module JSON
   ```
   resources.{lang}.ai_prompts[]
   ```

### 📋 **JSON Metadata Structure**
```json
{
    "id": "paginas",
    "name": "Pages",
    "target": "paginas",
    "default": true,
    "version": "1.2",
    "checksum": {
        "md": "f46d272c361f52f77f7033eaf3780cd7"
    }
}
```

### 🌍 **Multilingual Support**
- **Structure by language**: `resources/{lang}/ai_prompts/`
- **Separate metadata**: `resources.{lang}.ai_prompts[]`
- **Supported languages**: pt-br, en, etc.

**Complete example:**
```
gestor/modulos/admin-paginas/
├── admin-paginas.json (metadata)
└── resources/
    ├── pt-br/
    │   └── ai_prompts/
    │       └── paginas/
    │           └── paginas.md
    └── en/
        └── ai_prompts/
            └── paginas/
                └── paginas.md
```

---

## ⚠️ Metadata Conventions

### 🔧 **"default" Field Handling**
**IMPORTANT**: The database update system (`atualizacoes-banco-de-dados.php`) automatically handles the "default" field for the `prompts_ia` table:

#### ✅ **When it is default (default: true)**
```json
{
    "id": "paginas",
    "name": "Pages",
    "target": "paginas",
    "default": true,
    "version": "1.2",
    "checksum": {
        "md": "f46d272c361f52f77f7033eaf3780cd7"
    }
}
```

#### ✅ **When it is NOT default (default: false or omitted)**
**Both forms are valid and equivalent:**
```json
// Option 1: Explicit field
{
    "id": "simple-page-one-session",
    "name": "Simple Page - One Session",
    "target": "paginas",
    "default": false,
    "version": "1.0",
    "checksum": {
        "md": "a1b2c3d4e5f67890123456789012345"
    }
}

// Option 2: Omitted field (recommended)
{
    "id": "simple-page-one-session",
    "name": "Simple Page - One Session",
    "target": "paginas",
    "version": "1.0",
    "checksum": {
        "md": "a1b2c3d4e5f67890123456789012345"
    }
}
```

#### 🤖 **Automatic Processing**
- **System**: Automatically converts `true` → `1` and `false` → `0` for MySQL compatibility
- **Absence**: When the `default` field is not present, the system automatically sets it to `false` (0)
- **Upsert**: Allows both updating existing records and inserting new ones

#### 🚨 **Implementation Reason**
- **Compatibility**: Avoids errors `SQLSTATE[HY000]: General error: 1366 Incorrect integer value`
- **Flexibility**: Allows omission of the field to reduce JSON verbosity
- **Reliability**: Ensures that all records have a valid value for the `padrao` column

---

## 📊 Quality Metrics

### ✅ **Acceptance Criteria**
- [ ] Prompt executes without errors
- [ ] Result meets expectation
- [ ] Complete documentation
- [ ] Functional examples
- [ ] Validated parameters

### 📈 **Performance Indicators**
- **Success Rate**: % of successful executions
- **Average Time**: Typical execution duration
- **Satisfaction**: User feedback
- **Reuse**: Frequency of use

---

## 🔧 Maintenance and Evolution

### 📅 **Periodic Reviews**
- **Monthly**: Check metrics and feedback
- **Quarterly**: Update with new functionalities
- **Semiannual**: Review general structure

### 🔄 **Update Process**
1. **Collect Feedback**: Analysis of real usage
2. **Identify Improvements**: Optimization points
3. **Implement Changes**: Update version
4. **Test Changes**: Validate compatibility

---

## 📚 Technical References

### 🔗 **Related Resources**
- [Conn2Flow Documentation](./CONN2FLOW-MANAGER-DETAILS.md)
- [Plugin Structure](./CONN2FLOW-PLUGIN-INSTALLER-FLOW.md)
- [Template System](./CONN2FLOW-LAYOUTS-PAGES-COMPONENTS.md)

### 🏷️ **Naming Conventions**
- **Files**: `type-functionality-version.md`
- **Tags**: `category, subtype, functionality`
- **Versions**: `MAJOR.MINOR.PATCH`

---

## 🎯 Next Steps

This document will be expanded with:
- **Specific templates** by category
- **Practical examples** of implementation
- **Real use cases** of Conn2Flow
- **Advanced guidelines** for optimization

*Living document - Updated according to system evolution*
````