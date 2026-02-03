# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Detailed technical documentation in `ai-workspace/docs/`
- Complete change history in `CONN2FLOW-CHANGELOG-HISTORY.md`
- Industry-standard CHANGELOG.md following Keep a Changelog format

## [2.6.3] - 2026-02-03

### Added
- **Responsive Admin Menu**: Complete menu redesign with floating toggle button, resizable width, and localStorage persistence
- **Mobile/Tablet Sidebar Overlay**: Unified sidebar overlay behavior for devices up to 1024px width
- **Resizable Menu**: Drag handle to adjust menu width (200-450px) with real-time persistence
- **Keyboard Shortcut**: Ctrl/Cmd+B to toggle menu visibility
- **Tablet-Optimized Dashboard**: 2-column card layout on tablets for better usability
- **Mobile Overlay with Backdrop**: Dark background when mobile/tablet menu is open
- **Double-Click to Reset**: Double-click on resize handle resets default width (250px)

### Changed
- **Mobile/Tablet Breakpoint**: Changed from 770px to 1024px to include tablets in sidebar overlay behavior
- **Smooth CSS Transitions**: Fluid animations with animation-free initialization to avoid visual flashes
- **Menu State Persistence**: Width and closed state now saved in localStorage

### Technical
- New CSS class structure for menu state control: menu-closed, menu-mobile-open, menu-no-transition
- Floating toggle button (#menu-toggle-btn) appears when menu is closed
- Resize handle (#menu-resize-handle) with mouse and touch support
- Dark overlay (.menu-mobile-overlay) for mobile/tablet
- Reorganized media queries: mobile/tablet (<=1024px) and desktop (>=1025px)
- Dashboard cards with responsive grid: 1 column (<=767px), 2 columns (768-1024px), 4 columns (>=1025px)

## [2.6.0] - 2025-12-18

### Added
- **Complete Publisher Module**: New content publishing module with complete CRUD for publishers and pages
- **Quill WYSIWYG Editor**: Quill editor integration for rich content editing in the publisher module
- **Dynamic Fields System**: Configurable dynamic fields for publisher templates with various types
- **News Abstract Templates**: Ready-to-use templates for the publisher module
- **Cloning Functionality**: Clone for admin pages, admin templates, and publisher pages
- **Image Picker in HTML Editor**: Visual image selector integrated with HTML editor with preview
- **Design Simulation Mode**: Dropdown to simulate different design modes in the HTML editor
- **Editor Button Tooltips**: Informative tooltips on editor template and field buttons
- **Section Modification**: Advanced section modification features in the visual HTML editor
- **Global Variables Glossary**: Global variable documentation for AI components

### Changed
- **Fomantic-UI v2.9.4**: Update to the latest Fomantic-UI version
- **Updated Gemini Models**: Updated Gemini model versions in AI prompts
- **Enhanced Language Detection**: Fixed language preference logic to prioritize browser detection
- **Multiple Modal System**: Stacked modals support with allowMultiple: true
- **Refined AI Prompts**: Updated HTML template generation prompts and variable descriptions
- **Modernized Fomantic-UI Fields**: Migration from empty to notEmpty fields (future deprecation)

### Technical
- New publisher field structure linked with templates
- AJAX snippets created for agent context integration
- Virtual modules without backend for simplified access control
- Form field labels for publisher in pt-br and en
- AI server components and global variables glossary templates
- postMessage integration for iframe - parent communication (image picker)
- CSS aspect-ratio for image thumbnails in editor

## [2.5.0] - 2025-11-12

### Added
- **Centralized HTML Editor Library**: New html-editor.php library with reusable editing functionality between modules
- **Visual Template Selection System**: Fomantic UI cards interface for page template selection
- **Modular HTML Editor**: Unified editing system for pages, templates, and components
- **Multilingual Template System**: Templates with language prioritization and target filtering support
- **Advanced Template Management**: Templates with thumbnails, metadata, and CodeMirror integration
- **Reusable Components**: HTML editor components shared between admin-pages and admin-templates
- **Enhanced AI Integration**: Prompts system with session management and positional insertion
- **Component-Based Architecture**: Better maintenance and code reuse

### Changed
- **Template Selection Interface**: Migration from accordion to Fomantic UI cards for better visualization
- **Editing Architecture**: Centralization of editing functionality in shared library
- **User Experience**: Unified editing interface in all admin modules
- **Template Performance**: AJAX loading with pagination for better performance

### Technical
- New html_editor_componente() function for editing component rendering
- Unified CodeMirror integration with consistent configuration
- Template system with multiple target and language support
- Components translated to English maintaining compatibility

## [2.4.0] - 2025-11-06

### Added
- **Complete Project Deploy System via OAuth API**: Complete automated system for project deploy with OAuth 2.0 authentication
- **Complete OAuth 2.0 Server**: Complete OAuth 2.0 server implementation with JWT validation and automatic token renewal
- **Project Deploy API**: /_api/project/update endpoint for automated deploy via API
- **Automatic Token Renewal System**: Automatic 401 error detection and transparent retry with renewed tokens
- **One-Click Deploy**: Automated workflow (update - compression - deploy) with a single command
- **Robust ZIP Validation**: Size (100MB max.), type, and ZIP file security verification
- **Inline Execution**: Database update without shell_exec for production environments
- **Automatic ZIP Structure Detection**: Support for projects with/without root directory
- **Integration Test Script**: Complete suite with 6/6 passing tests (config, resources, deploy, OAuth, API)
- **Comprehensive Documentation**: Complete system documented in CONN2FLOW-PROJECTS-SYSTEM.md

### Changed
- **Enhanced Security**: Mandatory OAuth authentication on API endpoints
- **Deploy Architecture**: Clear separation of responsibilities and one-click flow
- **Optimized Performance**: ZIP size reduction from 28KB to 25KB (automatic resources folder exclusion)
- **Production Compatibility**: Inline execution for secure environments

### Technical Details
- **New Scripts**: deploy-project.sh, renew-token.sh, integration-test.sh
- **New API Endpoint**: POST /_api/project/update with mandatory OAuth authentication
- **New Tables**: None (reuse of existing infrastructure)
- **New Libraries**: None (existing system extensions)
- **Database Migrations**: None required (backward compatibility)
- **Security Features**: OAuth 2.0, ZIP validation, inline execution, automatic rollback

## [2.3.0] - 2025-10-17

### Added
- **Complete Integrated AI System**: Assisted content generation in admin-pages via Gemini API
- **Dual Prompt System**: Structured technical modes + flexible user prompts
- **Advanced CodeMirror Interface**: Enhanced editing with AI-generated content insertion
- **Intelligent Session Management**: Generated content handling and positional insertion
- **Multiple AI Model Support**: Dynamic server and model configuration
- **Robust Error Validation**: Complete error handling for external API communication
- **New ia.php Library**: Complete functions for prompt rendering and Gemini API communication
- **New Database Tables**: ai_servers, ai_modes, ai_prompts for AI system management
- **Advanced JavaScript Interface**: AI controls and content generation with CodeMirror

### Changed
- **admin-pages Module**: Complete integration with AI system for assisted generation
- **AI Architecture**: Dual prompt system (technical + flexible) implemented
- **Editing Interface**: Enhanced CodeMirror with AI content positional insertion

### Technical Details
- **New Tables**: ai_servers, ai_modes, ai_prompts
- **New Library**: ia.php with complete AI functions
- **API Integration**: Google Gemini API with error handling and validation

## [2.2.2] - 2025-09-26

### Added
- **Complete Multilingual System**: Full pt-br/en support with administrative interface
- **Administrative Language Selector**: New tab in admin-environment for dynamic language change
- **Plugins V2 System**: Completely refactored architecture with dynamic detection
- **Multilingual Installer**: Language selection support during installation

### Changed
- **Multilingual Configuration**: Intuitive interface for dynamic language change (pt-br/en)
- **Settings Persistence**: Automatic saving to .env file

### Fixed
- **.env Template Fix**: Default pt-br value for LANGUAGE_DEFAULT

## [2.1.0] - 2025-09-18

### Added
- **html_extra_head Field**: Allows including extra HTML in the HEAD section
- **css_compiled Field**: CSS compiled support for pages, components, and layouts
- **CodeMirror Editor**: Advanced interface for HTML and CSS editing

### Changed
- **System Core**: gestor.php files and libraries updated for new fields
- **Admin Modules**: admin-pages and admin-components fully compatible

## [2.0.21] - 2025-09-18

### Fixed
- **format_url Function Fixed**: Always adds slash at the end of URL

## [1.16.0] - 2025-09-02

### Added
- TailwindCSS/FomanticUI Preview System with CodeMirror
- Multiple CSS framework support

## [1.8.0] - 2025-07-25

### Added
- Hybrid migration system with Phinx
- Complete automatic installer

## [1.0.0] - 2025-02-01

### Added
- Initial Conn2Flow system version
- Content management system
- Basic modular structure
- Authentication system

---

## Types of Changes

- Added for new features
- Changed for changes in existing features
- Deprecated for features that will be removed soon
- Removed for removed features
- Fixed for bug fixes
- Security for security improvements
