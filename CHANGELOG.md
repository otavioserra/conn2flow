# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [2.10.4] - 2026-09-02

### Fixed
- Cross-Platform Resource MD5 Checksums: Normalized LF line endings during resource compilation and updated `AdminCronReq032Test::testChecksumHtmlCoincideComMd5DoArquivo` to assert checksums tolerantly across Windows (CRLF) and Linux CI (LF) environments (`req-155` / `BATCH-157`).

## [2.10.3] - 2026-09-02

### Added
- Automated Tailwind template integrity test suite (`TemplatesTailwindIntegrityTest`) covering HTML, sidecars, and essential utility classes across all 72 templates (`req-154` / `BATCH-156`).

### Fixed
- Tailwind Live Editor Preview Cascading: Isolated unlayered Fomantic CSS stylesheets inside Tailwind template previews and preserved page baseline utility styles when inserting sections (`req-154` / `BATCH-156`).
- Resource Checksum Integrity Gate: Cleared manual checksum entries in `admin-cron` metadata to enable deterministic build calculation (`req-153` / `BATCH-155`).

## [2.10.2] - 2026-09-02

### Added
- Cron Tasks Engine & Module (`admin-cron`): Implemented the automated background task engine (`cron.php`), database migration (`cron_tarefas` table), and admin CRUD interfaces (REQ-152 / BATCH-154 e REQ-032 / BATCH-026).

### Fixed
- Windows TLS/SSL Compatibility: Added `--ssl-no-revoke` flag for curl operations to prevent Schannel certificate revocation check failures during deployment and token renewal on Windows systems.

## [2.10.1] - 2026-08-31

### Added
- Zero-prompt agent initialization and explicit repository identification for clearer multi-repository development handoffs.

### Changed
- Streamlined root README and changelog documentation, moving detailed development-environment guidance and legacy release notes into dedicated AI workspace documents.

## [2.10.0] - 2026-08-31

### Added
- Automatic Tailwind system dependencies: System runtime modals (`interface_alerta`, dimmer, confirmation modal) are now automatically included as dependencies for all Tailwind pages without manual configuration.
- Local external assets library: Self-hosted SortableJS, DataTables, and jQuery eliminating public CDN dependencies for enhanced privacy and supply-chain resilience.
- Build-time minification pipeline: Native JS/CSS minification at build time reducing asset payloads by over 50%.
- Compatibility with Web Installer v2.0.0: Fully integrated with installer locks, timeout resumption, and refined Nginx/Apache detection.

### Changed
- Responsive admin data tables: Horizontal scroll container on screens >=1200px preventing unwanted vertical traps.
- Standardized text editor field types from legacy `tinymce` to `editor-texto`.

### Fixed
- Modal styling in login and public Tailwind pages when system alerts are triggered.
- Fixed template resolution in gallery modules ensuring changes reach live pages.

## [2.9.51] - 2026-08-26

### Added
- Batch selection in the embedded file picker, responsive gallery density controls, and quick editing for captions and links.
- Vertical image positioning (`top`, `center`, or `bottom`) across gallery administration, templates, and public widgets.

### Changed
- Gallery grids now support compact 6-column and 10-column layouts with fixed, predictable thumbnail proportions.
- Release cleanup moved from local scripts to post-success GitHub Actions jobs with atomic branch-and-tag pushes.

### Fixed
- MIME normalization and image validation in the administrative interface.
- Live Editor delivery for empty widgets and trusted administrator HTML.

## [2.9.39] - 2026-08-21

### Added
- **Live Editor & Site Toolbar (Editbar)**: Contextual floating toolbar on the published website enabling in-place editing with reactive live-DOM node mapping, widget locks, event isolation, and accidental click shield.
- **Floating Panels & Modals in Live Editor**:
  - Element Insertion ("+"): Contextual insertion of structural blocks, widgets, forms, and embeds.
  - Session Templates & Backups: Instant snapshot creation and revision restore directly in the live editor.
  - AI Assistant with CodeMirror: Prompt interface with embedded CodeMirror code editor and live streaming preview.
  - Custom Code Panel: Direct editing of HTML, CSS (with live debounce), JavaScript, and Extra Head.
- **Persistent Clipboard**: LocalStorage-backed clipboard allowing copying structural blocks and elements on one page and pasting or replacing them on another page or browser tab with automatic widget ID remapping.
- **Visual Editor View Options Panel**:
  - CSS Sidebar: Variant-grouped Tailwind classes, custom classes, inline CodeMirror CSS editor, and `getComputedStyle()` live inspector.
  - Element Navbar: Navigation breadcrumb bar with selectable child node hierarchy.
- **Tailwind CSS v4 Layouts Modernization**:
  - New `layout-administrativo-tailwind`: Responsive sidebar with Lucide Icons, dynamic resizing (220–450px) persisted in `localStorage`, instant unaccented search filter, and full keyboard navigation (ArrowUp/ArrowDown/Esc).
  - New `layout-pagina-sem-permissao-tailwind`: Public layout and migration of all 15 identity/authentication screens to pure Tailwind CSS using Conn2Flow blue (`sky`) palette.
  - New User Profile Panel: Modern tabbed interface with active session management, remote session revocation, and detailed access history.
- **Declarative Synchronization & Pull System (Reverse Engineering)**:
  - Declarative sync engine configured via `tables_config.json` and `project_tables_config.json` supporting `sync_resources`, special field types (`json`, `file:<ext>`), `forcar_atualizacao`, `deletar`, and MD5 integrity checks.
  - Reverse Pull Engine: Endpoint `/_api/project/recover`, script `recover-project.sh`, and resource decompiler supporting structured subdirectories (`<table_name>/<id>/<id>.<ext>`) and conflict resolution in `contents/`.
- **Embedded Media, Streaming & Hybrid PDF Viewer**:
  - Atomic embed wrapper (`.conn2flow-embed-wrapper`) with resize handles and click protection.
  - Hybrid PDF viewer with native PDF.js runtime (`pdf-viewer.js` with canvas, zoom, and toolbar), Google Viewer, and `<object>` native fallback.
  - Media streaming via HTTP Range headers (206 Partial Content) for Safari/iOS compatibility, space-in-filename sanitization, and responsive auto-sizing.
- **Physical File Manager (Admin-Arquivos)**:
  - Transition to physical filesystem folder hierarchy (full directory and subdirectory CRUD).
  - Unlocked file uploads and folder creation inside picker modals (iframe mode) with persisted last-directory navigation.
- **New Modules, Widgets & Features**:
  - `forms-search` Module: Public search forms with optimized AJAX autocomplete and lens themes.
  - `pages-index` Module: Index listing pages with highlights, filters, URL synchronization, and dynamic pagination.
  - Transfer publications between publishers in `publisher-pages` with automated URL adjustments and 301 redirect registration.
  - Admin module label and order customization in `modulos-grupos` with project-level component override support.
  - Fomantic and Lucide icon pairs mapping for derived project modules via Phinx migration `20260821100000_alter_modulos_update_icones_projetos`.
- **Payment Gateway Integrations**:
  - PayPal Library 3.1.0: Native transparent checkout with Card Fields and Hosted Fields.
  - Stripe Core Library: Complete Stripe integration (Payment Element, Billing, Subscriptions, HMAC Webhooks, and Product/Price catalog management).
- **Security & Personal Access Tokens (PAT)**:
  - Generation and validation of `c2f_pat_` tokens with SHA-256 hashing and 2FA recovery codes.
  - Schema drift tolerance with graceful degradation gates (`gestor_schema_tabela_existe` and `gestor_schema_campo_existe`).
- **SEO, Sitemap XML & Robots**:
  - Dedicated SEO and Open Graph metadata per page/publication (`imagem_destaque`, `og_titulo`, `og_descricao`, `meta_descricao`, `meta_keywords`) with SEO tab in HTML Editor and Editbar.
  - Dynamic sitemap generator delivering `assets/sitemap.xml` with non-indexable route filters and automatic 301 cleanup.
  - Automated `assets/robots.txt` generation.
- **Modern CLI Subsystem**: Object-oriented CLI in `/cli` and `c2f` binary with a complete command catalog.

### Changed
- **Resources and Variables Architecture**: Extracted HTML markup into `resources/` components and presentation utilities into system variables (`@[[classe-...]]@`).
- **AI Autonomy Governance**: Formalized 3-Tier Autonomy Spectrum (Supervised, Monitored Autonomous, Headless Autonomous) and repo-wide Conn2Flow Skills catalog.
- **Menu Button Toggling**: Contextual open/close button visibility toggling in Tailwind administrative layout and `admin-tailwind.js`.

### Fixed
- **Clean Reload on CSRF / Expired Session**: `gestor_csrf_resposta_invalida()` forcing clean reload / `location.replace` when returning to `/signin/`, eliminating bfcache token expiration loops.
- **Lucide Icon Console Warnings Elimination**: Two-layer sanitization in PHP and JS preventing legacy multi-word Fomantic names from reaching `data-lucide`.
- **Search Crawler & Bot Cookie Loop Elimination**: Definite resolution of infinite redirect loops for search bots (`gestor_cookie_verificacao_desfecho`) and anti-indexing headers on system routes.
- **PHP 8.5 Decoupling**: Elimination of false 429 errors during deploy by decoupling the 2.x line from PHP 8.5-exclusive syntax.

## [2.9.0] - 2026-06-16

### Added
- **Two-Factor Authentication (2FA)**: Native support for TOTP (authenticator apps) and email-based two-factor authentication inside user profiles.
- **Passwordless OTP Login**: Passwordless email-based authentication via secure one-time password (OTP) codes.
- **API Keys Management**: Dedicated API keys configuration in environment settings, supporting access profiles and optional 2FA protection.
- **OAuth Integration Helper**: Interactive step-by-step setup guides for Google, Facebook, Apple, and GitHub OAuth integrations.
- **Visual HTML Editor Controls**: Advanced visual styler panel (20 formatting groups across Text, Layout, Box, and Appearance sections) and circular color palettes.
- **Interactive Drag & Drop**: Flashing visual placeholders indicating element drop targets and a ghost follower showing the exact shape of the element being inserted.
- **Internal Clipboard**: Copier and Paste buttons supporting standard `Ctrl+C` and `Ctrl+V` keyboard shortcuts.
- **Wrap Tool**: Added the "Embrulhar" (Wrap) element feature, allowing nesting of selected elements inside structural tags (div, section, article, etc.).
- **Dynamic Widget Skeletons**: Realistic widget placeholders rendered via the `html-editor-widget-render` endpoint to display layout structures in the visual editor.
- **Manual Curation in publisher-index**: Support for curating and sorting manual lists of publications inside the admin CRUD interface.
- **Metric Counters**: Dynamic counters displaying "Showing X of Y publications" on AJAX load in `publisher-index`.

### Changed
- **HTML Editor Styler layout**: Inverted columns to position visual controls on the left and CodeMirror tags on the right, and flippable toolbar positioning when space is tight.
- **Visual Editor Code Refactoring**: Extracted 26 simulation functions to `html-editor-modules.js` to simplify `html-editor-interface.js`.
- **Temporal Dead Zone (TDZ) Fixes**: Moved `contentPageTabHandler()` and the Fomantic UI `.tab()` initializations to the end of the `$(document).ready` scope.
- **Surgical Variables-to-Comments Replacement**: Replaced the destructive `body.innerHTML` write with a text-node-only TreeWalker replacement when parsing `[[widgets#...]]` variables in the visual editor.

### Fixed
- **Unicode Search in publisher-index**: Live search filtering of accent marks and special characters in publication titles.
- **Duplicate Pagination in publisher-index**: Removed index and duplicate pages from search results.
- **Widget Special Characters Escape**: Prevented `->` and json variables in `[[widgets#...]]` from escaping to `&gt;` and losing structure on save.
- **Dynamic Image URL Prefixing**: Automated detection of `image` type fields in `publisher-index` and `publisher-highlights` to dynamically prefix them with `$_GESTOR['url-raiz']`.

---

## Historical Archives

Older release notes are organized and preserved in dedicated archive documents:
- [v2 Legacy Releases (v2.0.21 to v2.8.4)](ai-workspace/en/docs/changelogs/CHANGELOG-archive-v2-legacy.md)
- [v1 Releases (v1.0.0 to v1.16.0)](ai-workspace/en/docs/changelogs/CHANGELOG-archive-v1.md)
- [Comprehensive Commit Evolution](ai-workspace/en/docs/CONN2FLOW-CHANGELOG-HISTORY.md)
