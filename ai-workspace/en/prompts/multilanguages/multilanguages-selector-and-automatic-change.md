```markdown
# Project: Language Selector and Automatic Detection

## 🎯 Initial Context
The `conn2flow` system already supports routes with language identification (e.g., `/en/page/`), where the backend detects the language code in the URL, sets the environment variable, and adjusts the request path.
The goal now is to implement the interface for the end user (frontend) and administrative controls (backend) to manage this functionality.

## 📝 Technical Specifications

### 1. Configuration (Admin Environment)
**Objective:** Allow enabling/disabling the widget and automatic detection via panel.

*   **Files:**
    *   `gestor/modulos/admin-environment/resources/pt-br/pages/admin-environment/admin-environment.html`
    *   `gestor/modulos/admin-environment/admin-environment.js`
    *   `gestor/modulos/admin-environment/admin-environment.php`
*   **New Environment Variables (.env):**
    *   `LANGUAGE_WIDGET_ACTIVE` (true/false)
    *   `LANGUAGE_AUTO_DETECT` (true/false)
*   **Implementation:**
    *   Add checkboxes in the "Language Settings" tab of the HTML.
    *   Update JS to send these new fields in the save AJAX.
    *   Update PHP to read/write these variables in the `.env` file.

### 2. Backend Signaling (Gestor Core)
**Objective:** Inform the frontend if the widget should be rendered and pass context data.

*   **File:** `gestor/gestor.php`
*   **Logic:**
    *   Check if `$_ENV['LANGUAGE_WIDGET_ACTIVE']` is `true`.
    *   If yes, populate `$_GESTOR['javascript-vars']['languages']` with an array containing:
        *   `active`: bool (widget state)
        *   `auto_detect`: bool (detection state)
        *   `current`: string (current language code, e.g., 'pt-br')
        *   `list`: array (list of available languages in `$_GESTOR['languages']`)
        *   `default`: string (default language)

### 3. Frontend - Widget and Logic (Global JS)
**Objective:** Render the selector and manage language detection/switching.

*   **File:** `gestor/assets/global/global.js`
*   **Main Logic:**
    *   Check existence of `gestor.languages`.
*   **Functionality A: Selection Widget**
    *   Dynamically create an HTML element (e.g., floating button or menu item) if `gestor.languages.active` is true.
    *   On click, show options based on `gestor.languages.list`.
    *   **Switch Action:** When selecting a language:
        1.  Generate new URL.
        2.  URL Logic:
            *   If current URL already has language code (e.g., `/en/...`), replace with new one.
            *   If not, add the new code right after the installation root.
        3.  Save cookie `language_code`.
        4.  Redirect `window.location.href`.
*   **Functionality B: Automatic Detection**
    *   Check if cookie `language_code` does **NOT** exist.
    *   If not exists:
        1.  Read `navigator.language` or `navigator.userLanguage`.
        2.  Check if detected language exists in allowed list.
        3.  Save cookie `language_code` with detected language (or default if not supported).
        4.  If `gestor.languages.auto_detect` is `true` AND detected language is different from current URL:
            *   Redirect to URL with correct language.

## ✅ Implementation Progress

### Phase 1: Configuration (Admin Environment)
- [ ] 1.1 - Add HTML fields in `admin-environment.html` (Widget Active, Auto Detect).
- [ ] 1.2 - Update `admin-environment.js` to capture and send new data.
- [ ] 1.3 - Update `admin-environment.php` to process read/write in .env.

### Phase 2: Backend (Gestor)
- [ ] 2.1 - Implement logic in `gestor.php` to inject `languages` into `javascript-vars`.

### Phase 3: Frontend (Global JS)
- [ ] 3.1 - Implement automatic detection and cookie creation.
- [ ] 3.2 - Implement Widget interface construction (HTML/CSS via JS).
- [ ] 3.3 - Implement URL switching logic (Redirect).

---
**Date:** 11/25/2025
**Developer:** GitHub Copilot
**Project:** Conn2Flow v2.5.x
```