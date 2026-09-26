---
title: "Use Conn2Flow Dev Tools in VS Code"
description: "VS Code extension panel for SDD scope, projects, diagnostics and Core operations."
section: guides
sources:
  - .vscode/tasks.json
  - cli/src/Console/Application.php
  - cli/src/Commands/ModuleCreateCommand.php
verified_at: e5b61f8e
---

# Use Conn2Flow Dev Tools in VS Code

**Conn2Flow Dev Tools** is the `conn2flow-tools` extension in `conn2flow-ai-workspace/vscode-extension/`. Open its activity bar icon in VS Code. The current extension tree (see `src/providers/conn2flowTreeProvider.ts` in the extension repository, commit `a537050`) has Overview, SDD, Core, Projects, Diagnostics and Agents sections; Custom Actions appear when an actions manifest exists.

## Choose scope and target

In **Overview**, select SDD scope, project target, language, topology and autonomy. The SDD section opens `CURRENT.md`, SPEC, checklist, requests, batches, decisions and handoffs for the selected scope. The Projects section requires an explicit target for update, Core/file sync and deploy shortcuts. The panel also lets you select another project for one operation.

## Run and monitor

Under **Core**, Update All starts the manager pipeline; resources, CSS audit and CSS rebuild have separate actions. Manager and installer releases have permission checks and guided execution. **Diagnostics** offers Docker status, Apache/PHP logs and log truncation for a Docker target; a VM target shows PHP/Nginx logs instead. **Agents** has documentation search/opening and a skills catalog.

The extension runs commands through tasks/terminals and uses forms for parameterized operations; review target and action before confirming deployment. Command execution is limited in untrusted workspaces. The [Core CLI](../reference/cli/index.md) is the terminal alternative; the extension tree and `.vscode/tasks.json` are separate interfaces.

## Install the extension locally

In `conn2flow-ai-workspace/vscode-extension/`, the package declares VS Code `^1.85.0`. For extension development, run `npm install`, `npm run compile` and `npm run package` there; install the generated VSIX through VS Code. Check the generated file name and version before installation.
