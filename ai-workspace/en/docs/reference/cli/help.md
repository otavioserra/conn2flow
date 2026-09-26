---
title: "CLI: Commands help"
description: "Reference for help commands registered in the c2f console."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/HelpCommand.php
verified_at: e5b61f8e
---

# CLI: Commands help

Syntax from executable help; check the source before running commands with external effects.

## `help`

Source: `cli/src/Commands/HelpCommand.php`.

```text
╔═══════════════════════════════════╗
║  Conn2Flow Core CLI (c2f) v2.5.0  ║
╚═══════════════════════════════════╝

Modern OOP CLI Subsystem for Conn2Flow Platform.


▶ Available Commands
─────────────────────
┌───────────────────────────────────────────────────────────────────────────────────────────────────────────────┬──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ Command                                                                                                       │ Description                                                                                                                      │
├───────────────────────────────────────────────────────────────────────────────────────────────────────────────┼──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ help (list, --help, -h)                                                                                       │ Display help and list all available Conn2Flow CLI commands.                                                                      │
│ resources:sync (resources, sync:resources)                                                                    │ Compile and synchronize all native Conn2Flow resources (pages, layouts, components, variables, AI modes/prompts) into Data.json. │
│ css:audit (css:auditoria, audit:css)                                                                          │ Audit CSS provenance (stale derived CSS) and class coverage across resource tables.                                              │
│ css:rebuild (css:regenerar, css:regen)                                                                        │ Recompile derived CSS from the HTML stored in the database and stamp its provenance.                                             │
│ assets:vendor (vendor:assets, assets:download)                                                                │ Download registered third-party libraries into gestor/assets/vendor/ (local-first serving).                                      │
│ assets:fonts (fonts:vendor, assets:fontes)                                                                    │ Self-host Google Fonts declared by a project (downloads woff2 and rewrites the CSS).                                             │
│ assets:minify (assets:minificar, minify:assets)                                                               │ Minify the core own JavaScript into .min.js derivatives (build step, never at request time).                                     │
│ assets:publish (assets:publicar, dist:publish)                                                                │ Publish processed static assets into public_html/dist/ so the web server serves them without PHP.                                │
│ db:test (test:db, test:unit)                                                                                  │ Run PHPUnit automated database tests against SQLite/MySQL test harness.                                                          │
│ db:update (db:sync, db:migrate)                                                                               │ Synchronize and update database schema and seeds for development/test environment.                                               │
│ ai:sync (skills:sync, ai:skills)                                                                              │ Synchronize and validate all 37 AI skills, rules and agent instructions across AI kits.                                          │
│ ai:prune-memories (ai:gardening, sdd:prune)                                                                   │ Validate Memory Gardening thresholds (50KB warning, 75KB mandatory ceiling).                                                     │
│ ai:mcp-setup (mcp:setup, mcp:connect)                                                                         │ Run 1-Click Automated Setup for Conn2Flow MCP Hub connectors in Claude Desktop, Cursor, and VS Code.                             │
│ ai:archive-sdd (sdd:archive, ai:archive)                                                                      │ Apply the Rule of 10 to SDD folders, archiving old requests/batches and rewriting markdown links.                                │
│ module:create (make:module, module:new)                                                                       │ Scaffold a canonical CRUD module based on modulos-grupos architecture.                                                           │
│ manager:build (build:manager, build)                                                                          │ Build local manager production assets and bundles.                                                                               │
│ manager:sync-files (sync:files)                                                                               │ Synchronize manager source files to the local test environment via checksum.                                                     │
│ manager:update-all (update:all, manager:update)                                                               │ Run complete manager update pipeline: Resources Sync -> Files Sync -> Database Sync -> CSS rebuild.                              │
│ manager:commit (commit)                                                                                       │ Execute standardized commit routine using ai-workspace commit.sh.                                                                │
│ manager:release (release)                                                                                     │ Execute standardized release routine (patch/minor/major, tag, and changelog).                                                    │
│ plugin:sync (sync:plugin)                                                                                     │ Synchronize active private or public plugin files via checksum.                                                                  │
│ plugin:build (build:plugin)                                                                                   │ Build local manager plugin assets (private or public).                                                                           │
│ plugin:resources (resources:plugin)                                                                           │ Update and compile resource data for active plugin (private or public).                                                          │
│ plugin:commit (commit:plugin)                                                                                 │ Execute standardized commit routine inside active plugin.                                                                        │
│ plugin:release (release:plugin)                                                                               │ Execute standardized release routine on active plugin.                                                                           │
│ project:sync-core (sync:project-core)                                                                         │ Synchronize updated core system files into a project directory.                                                                  │
│ project:sync-resources (sync:project-resources)                                                               │ Compile and synchronize resource data for a specific project.                                                                    │
│ project:sync-files (sync:project-files)                                                                       │ Synchronize project files via checksum with optional contents/ folder.                                                           │
│ project:sync-db (sync:project-db)                                                                             │ Synchronize and update database for a specific project.                                                                          │
│ project:sync-hooks (sync:project-hooks)                                                                       │ Synchronize hook registrations for a specific project.                                                                           │
│ project:update-all (project:update)                                                                           │ Run complete sequential project synchronization: Core -> DB -> Resources -> Files -> DB -> CSS rebuild -> JS minify.             │
│ project:deploy (deploy:project, deploy)                                                                       │ Deploy project to production or staging server via deploy-project-v2.sh.                                                         │
│ project:recover (recover:project, recover)                                                                    │ Recover remote project database, configurations and assets locally.                                                              │
│ project:update-system (update:system)                                                                         │ Update system dependencies, composer packages and migrations in project.                                                         │
│ docs:audit                                                                                                    │ Rank documentation drift against the code (frontmatter, sources, language pairs, coverage).                                      │
│ docs:extract                                                                                                  │ Regenerate the function reference block of library docs from gestor/bibliotecas/*.php.                                           │
│ docs:build                                                                                                    │ Build the Core Markdown docs into a project's system resources (publisher pages, pages, menu, llms.txt).                         │
│ installer:sync (sync:installer)                                                                               │ Synchronize manager installer files and checksums.                                                                               │
│ installer:build (build:installer)                                                                             │ Build local standalone manager installer package.                                                                                │
│ installer:new (new:installation)                                                                              │ Create a new local installation instance via create-new-installation.sh.                                                         │
│ installer:release (release:installer)                                                                         │ Execute standardized release routine for standalone installer package.                                                           │
│ docker:status (docker:ps)                                                                                     │ Check the status of Docker containers in the dev environment.                                                                    │
│ docker:php-version (docker:php, php:version)                                                                  │ Check the active PHP version inside the conn2flow-app Docker container.                                                          │
│ docker:logs (docker:log)                                                                                      │ Show recent PHP / Apache error logs from the conn2flow-app container.                                                            │
│ docker:truncate-logs (docker:clean-logs)                                                                      │ Truncate /var/log/php_errors.log inside the container.                                                                           │
│ tailwind:fix-spacing (tailwind:fix)                                                                           │ Fix and suggest canonical classes using fix-tailwind-spacing.js.                                                                 │
│ env:status (env:get)                                                                                          │ Display current environment mode (development/production) and active flags.                                                      │
│ env:set (dev:on, dev:off, env:toggle)                                                                         │ Set environment mode (development/production) in .env file.                                                                      │
│ auth:cookie (auth:generate)                                                                                   │ Generate authentication cookies (JWT + session) for automated access to authenticated routes.                                    │
│ page:inspect (inspect)                                                                                        │ Inspect a page headlessly via Playwright: computed styles, console errors, screenshots.                                          │
│ motion:status (motion:get, anim:status, motion:on, anim:on, motion:off, anim:off, motion:toggle, anim:toggle) │ Inspect or change the operating-system animation preference used by prefers-reduced-motion.                                      │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┴──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘

Run 'c2f help <command>' or 'c2f <command> --help' for command-specific instructions.
```
