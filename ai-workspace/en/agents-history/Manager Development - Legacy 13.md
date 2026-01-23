# Manager Development - Legacy 13 (January 2026)

## HTML Editor Refinement and Variable Architecture Standardization

## Context and Objectives
This development session focused on two main fronts: improving the developer experience (DX) in the custom visual HTML editor and architecturally restructuring how "System Variables" are handled between the Frontend and Backend. The primary goal was to eliminate the visual complexity of the `@` control character for the end-user while keeping it strictly for security and processing in the backend.

## Detailed Scope Realized

### 1. Indentation Algorithm Refactoring (Beautify)
The HTML editor had an automatic formatting algorithm (`cleanCodeString`) that applied incorrect indentation to tag attributes.
- **Problem**: Attributes of container tags (e.g., `<div>`) were indented an extra level, creating a visually disconnected structure ("staircase" style).
- **Technical Solution**: 
  - Rewrote the tokenization logic in `gestor/assets/interface/html-editor-interface.js`.
  - Implemented a clear distinction between **Void Tags** (self-closing, e.g., `img`, `input`, `br`) and **Container Tags**.
  - **New Logic**:
    - If the tag is a *Container*: Attributes align vertically with the opening tag.
    - If the tag is *Void*: Nested indentation (+1 tab) is maintained for attributes, following the VS Code visual standard.

### 2. Editor Productivity Tools
Implementation of UI features to streamline the administrators' workflow:
- **Copy to Clipboard (One-Click)**: 
  - Added global listener in `html-editor-interface.js` and `publisher.js`.
  - Clicking on any variable in the "Available Fields" or "Linked" lists automatically copies the variable string (`[[publisher#...]]`) to the clipboard.
- **Remove All Variables**: 
  - Created "Remove Variables" button in the control bar (`html-editor-publisher-controls.html`).
  - JS function that scans the editor content via Global Regex and removes all instances of dynamic variables, useful for quick template cleaning.

### 3. Syntax Architectural Standardization (Frontend vs Backend)
This was the most critical and complex change. There was an inconsistency where the user had to deal with the `@[[...]]@` syntax (internal system format), which generated confusion and typing errors.

#### A. Frontend Layer (Clean Visualization)
All visual references and manipulation in JavaScript were migrated to the format **without** `@`.
- **Modified Files**: 
  - `gestor/modulos/publisher/publisher.js`
  - `gestor/assets/interface/html-editor-interface.js`
- **Specific Changes**:
  - Detection Regex Update: From `/@?\[\[/` (optional) to `/\[\[/` (strict without at-sign).
  - JS Template Literals: Updated to generate strings in `[[publisher#type#id]]` format in selection lists and dropdowns.
  - The user now sees, copies, and pastes only `[[...]]`.

#### B. Backend Layer (Security and Persistence)
To ensure the system's template processor (which relies on `@` to identify variables) continued working without core changes, we implemented a **Transformation Middleware** in the module controller.
- **Modified File**: `gestor/modulos/publisher/publisher.php`
- **Implemented Logic**:
  - Created private function `publisher_normalize_array($array, $direction)`.
  - **`to_db` Direction (Save)**: Intercepts data coming from POST and applies `preg_replace` to wrap variables with `@`.
    - Regex: Transforms `[[...]]` into `@[[...]]@` (avoiding duplication if it already exists).
  - **`from_db` Direction (Load)**: Intercepts data coming from the Database before sending to View/JSON.
    - Operation: Removes external `@` (`str_replace`), delivering the clean format to Javascript.

## Modified Files and Directories

### Backend (PHP)
- `gestor/modulos/publisher/publisher.php`:
  - Added `publisher_normalize_array` method.
  - Applied method in `publisher_adicionar` (before saving).
  - Applied method in `publisher_editar` (before returning data to form).

### Frontend Logic (JS)
- `gestor/assets/interface/html-editor-interface.js`:
  - `cleanCodeString` Logic (Indentation).
  - `copy-to-clipboard` and `remove-all-variables` listeners.
  - Regex refactoring for clean syntax.
- `gestor/modulos/publisher/publisher.js`:
  - List rendering functions (`mountAvailableFieldsList`, etc.).
  - Search and autocomplete logic updated to ignore `@`.

### Frontend View (HTML/CSS)
- `gestor/resources/pt-br/components/html-editor-publisher-controls/html-editor-publisher-controls.html`:
  - Added bulk remove button.
  - Adjusted interface labels to reflect `[[...]]` pattern.

## Lessons Learned and Attention Points
- **Regex Lookbehind/Lookahead**: Using `(?<!@)` and `(?!@)` in PHP was essential to ensure the "save" process was idempotent (not adding `@` if it already existed).
- **Separation of Concerns**: The architecture now clearly separates "Display Format" from "Storage Format", reducing cognitive load on the user.

## Suggested Next Steps
1. **Module Audit**: Check other modules (like `admin-templates` or `paginas`) that might be exposing `@[[...]]@` directly and apply the same middleware pattern.
2. **Rendering Validation**: Ensure the central system parser (`gestor.php`) continues to correctly process `@[[...]]@` variables coming from the database, confirming that the frontend change didn't break final page rendering.
3. **Integration Testing**: Test the full cycle: Create variable -> Insert in Editor (clean format) -> Save (dirty format in DB) -> Load (clean format) -> Render on Site (value substitution).

## Current System State
- ✅ **HTML Indentation**: VS Code Standard.
- ✅ **Variable UX**: Clean (`[[...]]`) and with productivity tools.
- ✅ **Data Integrity**: Preserved via middleware (`@[[...]]@`).

_Detailed Session - Reference for Future Agent (Legacy 13)_
